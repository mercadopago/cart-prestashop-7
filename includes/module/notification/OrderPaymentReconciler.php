<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Reconciles one authorized reference inside the caller's lock and transaction. */
class OrderPaymentReconciler
{
    public static function assertPersistenceSucceeded($result, string $operation): void
    {
        if ($result !== true && $result !== 1) {
            throw new RuntimeException('Payment reconciliation persistence failed: ' . $operation);
        }
    }

    /** Core creates one order per package. A null snapshot denotes payment initiation. */
    public static function assertNativeCreationSupported($cart, ?array $snapshot = null): void
    {
        $positive = $snapshot === null;
        foreach ($snapshot['receipts'] ?? [] as $receipt) {
            $positive = $positive || PaymentReconciliationSnapshot::units($receipt['amount'], $snapshot['precision']) > 0;
        }
        if (!$positive) {
            return;
        }
        $packages = 0;
        foreach ($cart->getPackageList(true) as $addressPackages) {
            $packages += count($addressPackages);
        }
        if ($packages > 1) {
            throw new RuntimeException('Native split-order invoice allocation requires individual review before order creation');
        }
    }

    public function reconcile(array $orders, array $snapshot): void
    {
        $orders = $this->validateGroup($orders);
        $first = reset($orders);
        $db = Db::getInstance();
        $ids = implode(',', array_keys($orders));
        $currency = new Currency((int) $first->id_currency);
        foreach ($snapshot['attempts'] as $attempt) {
            if ($attempt['currency'] !== $currency->iso_code) {
                throw new RuntimeException('Attempt currency does not match reference');
            }
        }
        $foreignLinks = $this->rows('SELECT oip.id_order_payment FROM `' . _DB_PREFIX_ . 'order_invoice_payment` oip'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_invoice` oi ON oi.id_order_invoice=oip.id_order_invoice'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_payment` op ON op.id_order_payment=oip.id_order_payment'
            . ' WHERE oip.id_order IN (' . $ids . ') AND ((oi.id_order IS NOT NULL AND oi.id_order<>oip.id_order)'
            . ' OR (op.id_order_payment IS NOT NULL AND (op.order_reference IS NULL OR op.order_reference<>\'' . pSQL($first->reference) . '\')))');
        if ($foreignLinks) {
            throw new RuntimeException('Invoice payment link escapes authorized reference');
        }
        $rows = $this->payments($first->reference);
        $expectedReceipts = [];
        foreach ($snapshot['receipts'] as $id => $receipt) {
            if (PaymentReconciliationSnapshot::units($receipt['amount'], $snapshot['precision']) > 0) {
                $expectedReceipts['mp:' . $id] = true;
            }
        }
        foreach ($rows as $row) {
            if (PaymentReconciliationSnapshot::units($row['amount'], $snapshot['precision']) === 0) {
                continue;
            }
            $id = (string) $row['transaction_id'];
            $unconfirmedNative = in_array((int) $row['id_order_payment'], $snapshot['native_payment_ids'] ?? [], true)
                && !in_array((int) $row['id_order_payment'], $snapshot['confirmed_native_payment_ids'] ?? [], true);
            if ($row['payment_method'] !== 'Mercado Pago') {
                $expectedReceipts['external:' . $row['id_order_payment']] = true;
            } elseif (!isset($snapshot['attempts'][$id])
                || isset($snapshot['receipts'][$id])
                || (!$unconfirmedNative && in_array($snapshot['attempts'][$id]['status'], ['refunded', 'charged_back', 'in_mediation'], true))
            ) {
                $expectedReceipts['mp:' . $id] = true;
            }
        }
        if (count($orders) > 1 && $expectedReceipts) {
            // Native invoice links carry no allocation amount. Linking the same
            // nominal credit to each order would multiply individual funding.
            // Historical reversals may change state without rewriting that graph.
            if ($this->isUnchangedSplitReversal($orders, $snapshot, $rows)) {
                $this->assertConsistent($orders, $snapshot);

                return;
            }
            throw new RuntimeException('Native split-order invoice allocation requires individual review');
        }
        if (count($expectedReceipts) > 1) {
            $invoices = $this->rows('SELECT id_order FROM `' . _DB_PREFIX_ . 'order_invoice` WHERE id_order IN (' . $ids . ')');
            $counts = array_count_values(array_column($invoices, 'id_order'));
            foreach ($counts as $count) {
                if ($count > 1) {
                    throw new RuntimeException('Native multiple-invoice allocation requires individual review');
                }
            }
        }
        $keep = [];
        $receipts = $snapshot['receipts'];
        $precision = $snapshot['precision'];
        foreach ($rows as $row) {
            $id = (string) $row['transaction_id'];
            if ((int) $row['id_currency'] !== (int) $first->id_currency) {
                throw new RuntimeException('Payment reconciliation requires one reference currency');
            }
            if ($id === '') {
                if (!$this->isNativePlaceholder($row, $orders, $snapshot)) {
                    throw new RuntimeException('Ambiguous anonymous payment requires individual review');
                }
                $this->deletePayment($row, $ids);
                continue;
            }
            if (!isset($snapshot['attempts'][$id])) {
                if ($row['payment_method'] === 'Mercado Pago') {
                    throw new RuntimeException('Unverified Mercado Pago receipt');
                }
                continue;
            }
            if ($row['payment_method'] !== 'Mercado Pago') {
                throw new RuntimeException('Payment identity belongs to another payment method');
            }
            $attempt = $snapshot['attempts'][$id];
            if (in_array($attempt['status'], ['refunded', 'charged_back', 'in_mediation'], true)
                && PaymentReconciliationSnapshot::units($row['amount'], $precision) > 0
                && (!in_array((int) $row['id_order_payment'], $snapshot['native_payment_ids'] ?? [], true)
                    || in_array((int) $row['id_order_payment'], $snapshot['confirmed_native_payment_ids'] ?? [], true))
            ) {
                // A reversal cannot create historical credit. An existing credit is preserved.
                $receipts[$id] = ['id' => $id, 'amount' => $row['amount'], 'currency' => $attempt['currency']];
            }
            if (!isset($receipts[$id])) {
                $this->deletePayment($row, $ids);
                continue;
            }
            if (isset($keep[$id])) {
                $links = $this->rows('SELECT * FROM `' . _DB_PREFIX_ . 'order_invoice_payment` WHERE `id_order_payment` = ' . (int) $row['id_order_payment']);
                foreach ($links as $link) {
                    if (!isset($orders[(int) $link['id_order']])) {
                        throw new RuntimeException('Payment link escapes authorized reference');
                    }
                    $this->link((int) $link['id_order_invoice'], (int) $keep[$id], (int) $link['id_order']);
                }
                $this->deletePayment($row, $ids);
                continue;
            }
            $keep[$id] = (int) $row['id_order_payment'];
            if (PaymentReconciliationSnapshot::units($row['amount'], $precision)
                !== PaymentReconciliationSnapshot::units($receipts[$id]['amount'], $precision)
            ) {
                $payment = new OrderPayment((int) $row['id_order_payment']);
                $payment->amount = $receipts[$id]['amount'];
                self::assertPersistenceSucceeded($payment->update(), 'update receipt');
            }
        }
        foreach ($receipts as $id => $receipt) {
            if ($receipt['currency'] !== $currency->iso_code) {
                throw new RuntimeException('Receipt currency does not match order');
            }
            if (!isset($keep[$id])) {
                $payment = new OrderPayment();
                $payment->order_reference = $first->reference;
                $payment->id_currency = $first->id_currency;
                $payment->conversion_rate = $first->conversion_rate;
                $payment->payment_method = 'Mercado Pago';
                $payment->transaction_id = (string) $id;
                $payment->amount = $receipt['amount'];
                self::assertPersistenceSucceeded($payment->add(), 'insert receipt');
                $keep[$id] = (int) $payment->id;
            }
        }
        // Only remove orphan links inside this authorized order group.
        self::assertPersistenceSucceeded($db->execute(
            'DELETE oip FROM `' . _DB_PREFIX_ . 'order_invoice_payment` oip'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_payment` op ON op.id_order_payment=oip.id_order_payment'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_invoice` oi ON oi.id_order_invoice=oip.id_order_invoice'
            . ' WHERE oip.id_order IN (' . $ids . ') AND (op.id_order_payment IS NULL OR oi.id_order_invoice IS NULL)'
        ), 'remove orphan invoice links');
        foreach ($orders as $order) {
            if (!empty($snapshot['invoice_required']) && !$order->hasInvoice()) {
                $order->setInvoice(true);
                if (!(new Order($order->id))->hasInvoice()) {
                    throw new RuntimeException('Could not create required invoice');
                }
            }
            $invoices = $this->rows('SELECT `id_order_invoice` FROM `' . _DB_PREFIX_ . 'order_invoice` WHERE `id_order` = ' . (int) $order->id);
            foreach ($invoices as $invoice) {
                foreach ($keep as $paymentId) {
                    $this->link((int) $invoice['id_order_invoice'], $paymentId, (int) $order->id);
                }
            }
        }
        Cache::clean('order_invoice_paid_*');
        $this->updateTotals($orders, $precision);
        if (!empty($snapshot['paid_transition'])) {
            foreach ($orders as $order) {
                foreach ((new Order($order->id))->getInvoicesCollection() as $invoice) {
                    /** @var OrderInvoice $invoice */
                    if ($invoice->getRestPaid() > 0) {
                        throw new RuntimeException('Native invoice funding remains incomplete');
                    }
                }
            }
        }
        $this->assertConsistent($orders, $snapshot);
    }

    public function assertConsistent(array $orders, array $snapshot): void
    {
        $orders = $this->validateGroup($orders);
        $first = reset($orders);
        $rows = $this->payments($first->reference);
        $seen = [];
        foreach ($rows as $row) {
            $id = (string) $row['transaction_id'];
            if ($id === '') {
                throw new RuntimeException('Anonymous payment appeared during reconciliation');
            }
            if (isset($snapshot['attempts'][$id])) {
                $attempt = $snapshot['attempts'][$id];
                if (isset($seen[$id]) || (!isset($snapshot['receipts'][$id])
                    && !in_array($attempt['status'], ['refunded', 'charged_back', 'in_mediation'], true))
                ) {
                    throw new RuntimeException('Unexpected or duplicate payment receipt');
                }
                if (isset($snapshot['receipts'][$id])
                    && PaymentReconciliationSnapshot::units($row['amount'], $snapshot['precision'])
                    !== PaymentReconciliationSnapshot::units($snapshot['receipts'][$id]['amount'], $snapshot['precision'])
                ) {
                    throw new RuntimeException('Receipt amount read-back failed');
                }
                $seen[$id] = (int) $row['id_order_payment'];
            }
        }
        foreach ($snapshot['receipts'] as $id => $receipt) {
            if (!isset($seen[$id])) {
                throw new RuntimeException('Confirmed receipt is missing');
            }
        }
        $ids = implode(',', array_keys($orders));
        $invoices = $this->rows('SELECT id_order_invoice,id_order FROM `' . _DB_PREFIX_ . 'order_invoice` WHERE id_order IN (' . $ids . ')');
        $unchangedSplitReversal = $this->isUnchangedSplitReversal($orders, $snapshot, $rows);
        foreach ($unchangedSplitReversal ? [] : $invoices as $invoice) {
            foreach ($snapshot['receipts'] as $id => $receipt) {
                $links = $this->rows('SELECT id_order FROM `' . _DB_PREFIX_ . 'order_invoice_payment` WHERE id_order_invoice=' . (int) $invoice['id_order_invoice'] . ' AND id_order_payment=' . (int) $seen[$id]);
                if (count($links) !== 1 || (int) $links[0]['id_order'] !== (int) $invoice['id_order']) {
                    throw new RuntimeException('Confirmed receipt invoice link is missing');
                }
            }
        }
        $bad = $this->rows('SELECT oip.id_order_payment FROM `' . _DB_PREFIX_ . 'order_invoice_payment` oip'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_payment` op ON op.id_order_payment=oip.id_order_payment'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_invoice` oi ON oi.id_order_invoice=oip.id_order_invoice'
            . ' WHERE oip.id_order IN (' . $ids . ') AND (op.id_order_payment IS NULL OR oi.id_order_invoice IS NULL OR oi.id_order<>oip.id_order OR op.order_reference IS NULL OR op.order_reference<>\'' . pSQL($first->reference) . '\')');
        if ($bad) {
            throw new RuntimeException('Invoice payment ownership read-back failed');
        }
        $ledger = 0;
        foreach ($rows as $row) {
            $ledger += PaymentReconciliationSnapshot::units($row['amount'], $snapshot['precision']);
        }
        $totals = $this->rows('SELECT `total_paid_real` FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order IN (' . $ids . ')');
        $total = 0;
        foreach ($totals as $row) {
            $total += PaymentReconciliationSnapshot::units($row['total_paid_real'], $snapshot['precision']);
        }
        if ($ledger !== $total) {
            throw new RuntimeException('Order received total differs from ledger');
        }
    }

    /** A reversal may change native state without repairing historical allocation. */
    private function isUnchangedSplitReversal(array $orders, array $snapshot, array $rows): bool
    {
        if (count($orders) < 2) {
            return false;
        }
        $reversal = false;
        foreach ($snapshot['attempts'] as $attempt) {
            $reversal = $reversal || in_array($attempt['status'], ['refunded', 'charged_back', 'in_mediation'], true);
        }
        if (!$reversal) {
            return false;
        }
        $existing = [];
        $first = reset($orders);
        foreach ($rows as $row) {
            $id = (string) $row['transaction_id'];
            $paymentId = (int) $row['id_order_payment'];
            if ($id === '' || isset($existing[$id]) || (int) $row['id_currency'] !== (int) $first->id_currency
                || PaymentReconciliationSnapshot::units($row['amount'], $snapshot['precision']) < 0
                || (in_array($paymentId, $snapshot['native_payment_ids'] ?? [], true)
                    && !in_array($paymentId, $snapshot['confirmed_native_payment_ids'] ?? [], true))
            ) {
                return false;
            }
            $existing[$id] = $row;
            if ($row['payment_method'] === 'Mercado Pago'
                && (!isset($snapshot['attempts'][$id])
                    || (!isset($snapshot['receipts'][$id])
                        && !in_array($snapshot['attempts'][$id]['status'], ['refunded', 'charged_back', 'in_mediation'], true)))
            ) {
                return false;
            }
        }
        foreach ($snapshot['receipts'] as $id => $receipt) {
            if (!isset($existing[$id]) || $existing[$id]['payment_method'] !== 'Mercado Pago'
                || PaymentReconciliationSnapshot::units($existing[$id]['amount'], $snapshot['precision'])
                    !== PaymentReconciliationSnapshot::units($receipt['amount'], $snapshot['precision'])
            ) {
                return false;
            }
        }

        return true;
    }

    private function validateGroup(array $orders): array
    {
        $group = [];
        $reference = null;
        $currency = null;
        foreach ($orders as $order) {
            if (!$order->id || $order->module !== 'mercadopago' || !$order->reference
                || ($reference !== null && $reference !== $order->reference)
                || ($currency !== null && $currency !== (int) $order->id_currency)
            ) {
                throw new RuntimeException('Invalid payment reconciliation group');
            }
            $reference = $order->reference;
            $currency = (int) $order->id_currency;
            $group[(int) $order->id] = $order;
        }
        if (!$group) {
            throw new RuntimeException('Empty payment reconciliation group');
        }
        ksort($group);

        return $group;
    }

    private function payments(string $reference): array
    {
        return $this->rows('SELECT * FROM `' . _DB_PREFIX_ . 'order_payment` WHERE `order_reference` = \'' . pSQL($reference) . '\' ORDER BY `id_order_payment`');
    }

    private function rows(string $sql): array
    {
        $rows = Db::getInstance()->executeS($sql . ' FOR UPDATE', true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Could not read payment reconciliation data');
        }

        return $rows;
    }

    private function deletePayment(array $row, string $ids): void
    {
        $paymentId = (int) $row['id_order_payment'];
        $outside = $this->rows('SELECT id_order FROM `' . _DB_PREFIX_ . 'order_invoice_payment` WHERE id_order_payment=' . $paymentId . ' AND id_order NOT IN (' . $ids . ')');
        if ($outside) {
            throw new RuntimeException('Payment link escapes authorized reference');
        }
        self::assertPersistenceSucceeded(Db::getInstance()->delete('order_invoice_payment', 'id_order_payment=' . $paymentId . ' AND id_order IN (' . $ids . ')', 0, true), 'unlink receipt');
        self::assertPersistenceSucceeded((new OrderPayment($paymentId))->delete(), 'delete receipt');
        Cache::clean('order_invoice_paid_*');
    }

    private function link(int $invoiceId, int $paymentId, int $orderId): void
    {
        $where = 'id_order_invoice=' . $invoiceId . ' AND id_order_payment=' . $paymentId;
        $links = $this->rows('SELECT id_order FROM `' . _DB_PREFIX_ . 'order_invoice_payment` WHERE ' . $where);
        if ($links) {
            if (count($links) !== 1 || (int) $links[0]['id_order'] !== $orderId) {
                throw new RuntimeException('Ambiguous invoice payment link');
            }

            return;
        }
        self::assertPersistenceSucceeded(Db::getInstance()->insert('order_invoice_payment', [
            'id_order_invoice' => $invoiceId, 'id_order_payment' => $paymentId, 'id_order' => $orderId,
        ], false, true), 'link receipt');
    }

    private function isNativePlaceholder(array $row, array $orders, array $snapshot): bool
    {
        if ($row['payment_method'] !== 'Mercado Pago' || !empty($row['id_employee'])
            || !empty($row['card_number']) || !empty($row['card_holder'])
        ) {
            return false;
        }

        // IDs observed immediately after validateOrder, inside the same unit of work.
        return in_array((int) $row['id_order_payment'], $snapshot['native_payment_ids'] ?? [], true);
    }

    /** floor(paid * weight / total), without floats or an overflowing product. */
    private static function proportionalUnits(int $paid, int $weight, int $total): int
    {
        if ($paid < 0 || $weight < 0 || $total <= 0 || $weight > $total) {
            throw new InvalidArgumentException('Invalid reference allocation');
        }
        $result = intdiv($paid, $total) * $weight;
        $remainder = $paid % $total;
        if ($remainder === 0) {
            return $result;
        }
        $fraction = 0;
        $whole = 0;
        while ($weight > 0) {
            if (($weight & 1) !== 0) {
                $result += $whole;
                if ($fraction >= $total - $remainder) {
                    $fraction -= $total - $remainder;
                    ++$result;
                } else {
                    $fraction += $remainder;
                }
            }
            $weight = intdiv($weight, 2);
            if ($weight > 0) {
                $whole += $whole;
                if ($remainder >= $total - $remainder) {
                    $remainder -= $total - $remainder;
                    ++$whole;
                } else {
                    $remainder += $remainder;
                }
            }
        }

        return $result;
    }

    private function updateTotals(array $orders, int $precision): void
    {
        $first = reset($orders);
        $paid = 0;
        foreach ($this->payments($first->reference) as $row) {
            $paid += PaymentReconciliationSnapshot::units($row['amount'], $precision);
        }
        $expected = 0;
        foreach ($orders as $order) {
            $expected += PaymentReconciliationSnapshot::units($order->total_paid, $precision);
        }
        if ($expected === 0 && $paid > 0) {
            throw new RuntimeException('Positive credit on a zero-total reference is ambiguous');
        }
        $remaining = $paid;
        $last = array_key_last($orders);
        foreach ($orders as $id => $order) {
            $share = $id === $last ? $remaining : self::proportionalUnits($paid, PaymentReconciliationSnapshot::units($order->total_paid, $precision), max(1, $expected));
            $remaining -= $share;
            $order->total_paid_real = PaymentReconciliationSnapshot::decimal($share, $precision);
            self::assertPersistenceSucceeded($order->update(), 'update received total');
        }
    }
}
