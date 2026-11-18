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

require_once __DIR__ . '/PaymentReconciliationSnapshot.php';
require_once __DIR__ . '/OrderPaymentReconciler.php';
require_once __DIR__ . '/CartPaymentContext.php';
require_once __DIR__ . '/CartPaymentLock.php';

class AbstractNotification
{
    public $snapshot;
    public $preference;
    protected $reconciliationOrders = [];
    protected $financialReconciled = false;
    protected $cartRegistration;
    protected $reconciliationPhase = 'lock';
    protected $auditOnlyAttempts = [];
    protected $recordedPaymentIds = [];
    private $referenceTransactionActive = false;
    private $nativeRecovery;
    private $walletCleanupDone = false;
    private $deferredStates = [];
    private $nativeCreationStarted = false;
    private $nativeCompletionRecorded = false;
    private $lookupKnownIds = [];
    private $lookupAllowsMissingRegistration = false;
    private $lookupRevalidating = false;
    public $total;
    public $module;
    public $status;
    public $amount;
    public $approved;
    public $pending;
    public $order_id;
    public $mercadopago;
    public $order_state;
    public $payments_data;
    public $transaction_id;
    public $mp_transaction;
    public $ps_order_state;
    public $ps_order_state_lang;
    public $order_state_lang;
    public $customer_secure_key;
    public $mpuseful;
    public $checkout;
    public $mp_transaction_amount;
    public $responseMessage;
    public $responseCode;
    public $checkoutOutcome;
    protected $historicalPaymentEvent = false;

    public function __construct($transaction_id)
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
        $this->ps_order_state = new PSOrderState();
        $this->ps_order_state_lang = new PSOrderStateLang();
        $this->transaction_id = $transaction_id;
        $this->mpuseful = MPUseful::getInstance();

        $this->amount = 0;
        $this->pending = 0;
        $this->approved = 0;
    }

    protected function applyPaymentSnapshot(array $snapshot): void
    {
        $this->snapshot = $snapshot;
        $this->approved = (float) $snapshot['approved_amount'];
        $this->pending = (float) $snapshot['pending_amount'];
        $this->amount = 0;
        $this->status = $snapshot['effective_status'];
        $this->transaction_id = $snapshot['primary_payment_id'];
        $this->payments_data = array_fill_keys(['payments_id', 'payments_type', 'payments_method', 'payments_status', 'payments_amount'], []);
        foreach ($snapshot['attempts'] as $attempt) {
            foreach (['id' => 'id', 'type' => 'type', 'method' => 'method', 'status' => 'status', 'amount' => 'nominal_amount'] as $target => $source) {
                $this->payments_data['payments_' . $target][] = $attempt[$source];
            }
        }
    }

    /**
     * Verify if received notification and save on BD
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function verifyWebhook($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update(
            [
                'received_webhook' => true,
            ]
        );
        MPLog::generate('Notification received on cart id ' . $cart->id);
    }

    /**
     * @return mixed
     */
    public function validateOrderState()
    {
        if ($this->status != null) {
            $precision = $this->snapshot['precision'] ?? (in_array(Configuration::get('MERCADOPAGO_SITE_ID'), ['MCO', 'MLC'], true) ? 0 : 2);
            $totalUnits = PaymentReconciliationSnapshot::units($this->total ?? 0, $precision);
            $approvedUnits = PaymentReconciliationSnapshot::units($this->approved ?? 0, $precision);
            $pendingUnits = PaymentReconciliationSnapshot::units($this->pending ?? 0, $precision);
            $this->total = (float) PaymentReconciliationSnapshot::decimal($totalUnits, $precision);
            $this->approved = (float) PaymentReconciliationSnapshot::decimal($approvedUnits, $precision);
            $this->pending = (float) PaymentReconciliationSnapshot::decimal($pendingUnits, $precision);
            if (in_array($this->status, ['refunded', 'charged_back', 'in_mediation'], true)) {
                $this->order_state = $this->getNotificationPaymentState($this->status);

                return $this->order_state;
            }
            if ($totalUnits > 0 && $approvedUnits >= $totalUnits) {
                $this->status = 'approved';
                $this->amount = $this->approved;
                $this->order_state = $this->getNotificationPaymentState('approved');
            } elseif ($totalUnits > 0 && $pendingUnits > 0 && $approvedUnits + $pendingUnits >= $totalUnits) {
                $this->status = 'in_process';
                $this->amount = (float) PaymentReconciliationSnapshot::decimal($approvedUnits + $pendingUnits, $precision);
                $this->order_state = $this->getNotificationPaymentState('in_process');
            } else {
                $this->order_state = $this->getNotificationPaymentState($this->status);
            }

            if ($this->snapshot !== null) {
                $this->snapshot['effective_status'] = $this->status;
            }

            return $this->order_state;
        }
    }

    /**
     * Update order transaction
     *
     * @param mixed $order
     *
     * @return void
     */
    public function updateOrderTransaction($order)
    {
        if ($this->financialReconciled) {
            return;
        }
        if (!is_array($this->snapshot)) {
            throw new RuntimeException('A verified payment snapshot is required');
        }
        $orders = $this->reconciliationOrders ?: [$order];
        $target = new OrderState($this->getTargetOrderState($this->order_state));
        $this->snapshot['invoice_required'] = (bool) $target->invoice;
        $this->snapshot['paid_transition'] = (bool) $target->paid;
        $this->reconciliationPhase = 'ledger';
        (new OrderPaymentReconciler())->reconcile($orders, $this->snapshot);
        $this->financialReconciled = true;
    }

    /**
     * Create order on Prestashop database
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function createOrder($cart)
    {
        $paymentAmount = min((float) $this->total, (float) $this->amount);
        OrderPaymentReconciler::assertNativeCreationSupported($cart, $this->snapshot);
        $recovery = $this->beginNativeCreation($cart);
        $this->reconciliationPhase = 'creation';
        try {
            $this->nativeCreationStarted = true;
            $this->module->validateOrder(
                $cart->id,
                $this->order_state,
                $paymentAmount,
                'Mercado Pago',
                null,
                ['transaction_id' => $this->transaction_id],
                (int) $cart->id_currency,
                false,
                $cart->secure_key
            );
        } finally {
            if ($this->checkout === 'wallet_button') {
                $this->preference->disableCartRule();
                $this->walletCleanupDone = true;
            }
        }
        $this->order_id = $this->findOrderId($cart);
        if (!$this->order_id) {
            throw new RuntimeException('Native checkout did not persist an order');
        }
        $orders = $this->loadOrdersForCart($cart);
        $target = new OrderState((int) $this->order_state);
        foreach ($orders as $order) {
            $actual = (int) $order->current_state;
            $actualState = new OrderState($actual);
            if ($actual !== (int) $this->order_state
                && (!$this->getBackOrderStatus($actual) || (bool) $actualState->paid !== (bool) $target->paid)
            ) {
                throw new RuntimeException('Native checkout state differs from verified payment resolution');
            }
            $history = Db::getInstance()->getRow('SELECT id_order_state FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order=' . (int) $order->id . ' ORDER BY id_order_history DESC', false);
            if (!$history || (int) $history['id_order_state'] !== $actual) {
                throw new RuntimeException('Native checkout history read-back failed');
            }
        }
        $first = reset($orders);
        $recovery['result'] = $this->nativeCreationEvidence($cart, $orders);
        $this->assertNativeCreationTotal($orders, $recovery['intent']);
        $this->persistCreationRecovery($cart, $recovery);
        $this->nativeRecovery = $recovery;
        $this->nativeCompletionRecorded = true;
        $this->startReferenceTransaction();
        $this->prepareProvisionalSnapshot($this->snapshot, $recovery);
        $this->saveCreateOrderData($cart);
        $this->reconciliationOrders = $orders;
        $this->financialReconciled = false;
        $this->updateOrderTransaction($first);
        $this->saveUpdateOrderData($cart);
        $this->clearCreationRecovery($cart);
        $this->setNotificationResponse('The order has been created', 201);
    }

    /**
     * Validate status to update order
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function updateOrder($cart)
    {
        $this->updateOrders($cart, [(object) ['id' => $this->order_id]]);
    }

    /**
     * Update every order in a split reference while holding one per-cart lock.
     *
     * @param mixed $cart
     * @param iterable $orders
     *
     * @return void
     */
    public function updateOrders($cart, $orders)
    {
        $this->withCartPaymentLock($cart, function () use ($cart, $orders) {
            if ($this->snapshot !== null) {
                $this->assertAtomicStorage();
            }
            $this->applyOrdersLocked($cart, $orders);
        });
    }

    /** Shared entrypoint for synchronous checkout, buyer return and callbacks. */
    protected function processCartPayment($cart, bool $allowCreation): void
    {
        $this->withCartPaymentLock($cart, function () use ($cart, $allowCreation) {
            $this->cleanupInterruptedWalletRule($cart);
            $before = $this->captureLookupContext((int) $cart->id, false);
            $cart = new Cart((int) $cart->id);
            $cart->hydrate($before['cart']);
            $cart->clearCache();
            $this->reconciliationPhase = 'snapshot';
            $this->auditOnlyAttempts = [];
            $this->recordedPaymentIds = [];
            $this->historicalPaymentEvent = false;
            $snapshot = $this->loadPaymentSnapshot($cart);
            $knownBefore = $this->lookupKnownIds;
            $this->startReferenceTransaction();
            $this->reconciliationPhase = 'revalidation';
            $current = $this->captureLookupContext((int) $cart->id, true);
            if ($before['fingerprint'] !== $current['fingerprint']) {
                throw new RuntimeException('Payment context changed during API lookup');
            }
            $cart = new Cart((int) $cart->id);
            $cart->hydrate($current['cart']);
            $cart->clearCache();
            $this->lookupRevalidating = true;
            try {
                $knownCurrent = $this->knownPaymentIds($cart, $this->lookupAllowsMissingRegistration);
            } finally {
                $this->lookupRevalidating = false;
            }
            sort($knownBefore, SORT_STRING);
            sort($knownCurrent, SORT_STRING);
            if ($knownBefore !== $knownCurrent) {
                throw new RuntimeException('Known payment identities changed during API lookup');
            }
            if ($this->historicalPaymentEvent) {
                $this->setNotificationResponse('Historical payment closure confirmed without changing the current generation', 200);

                return;
            }
            $orders = $this->loadOrdersForCart($cart);
            if ($this->nativeRecovery !== null) {
                $this->prepareProvisionalSnapshot($snapshot, $this->nativeRecovery);
            }
            $this->applyPaymentSnapshot($this->withRecordedReversals($orders, $snapshot));
            $this->assertAtomicStorage();
            $currency = new Currency((int) $cart->id_currency);
            foreach ($this->snapshot['attempts'] as $attempt) {
                if ($attempt['currency'] !== $currency->iso_code) {
                    throw new RuntimeException('Payment currency differs from cart');
                }
            }
            if ($orders) {
                $this->recoverNativeCreation($cart, $orders);
                $this->total = array_sum(array_map(function ($order) {
                    return (float) $order->total_paid;
                }, $orders));
                $this->validateOrderState();
                $this->applyOrdersLocked($cart, $orders);

                if ($this->nativeRecovery !== null) {
                    if (!$this->financialReconciled) {
                        throw new RuntimeException('Native recovery was blocked by order state');
                    }
                    $this->clearCreationRecovery($cart);
                }

                return;
            }
            if (!empty($this->cartRegistration['creation_recovery'])) {
                throw new RuntimeException('Interrupted native creation requires individual review');
            }
            $this->createOrderFromSnapshot($cart, $allowCreation);
        }, false);
    }

    private function withRecordedReversals(array $orders, array $snapshot): array
    {
        if (!$orders) {
            return $snapshot;
        }
        $first = reset($orders);
        $rows = Db::getInstance()->executeS('SELECT id_order_payment, transaction_id, amount FROM `' . _DB_PREFIX_ . 'order_payment` WHERE order_reference=\'' . pSQL($first->reference) . '\' AND payment_method=\'Mercado Pago\'', true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Could not verify historical receipt reversals');
        }
        $reversals = [];
        foreach ($rows as $row) {
            $id = (string) $row['transaction_id'];
            if (in_array((int) $row['id_order_payment'], $snapshot['native_payment_ids'] ?? [], true)
                && !in_array((int) $row['id_order_payment'], $snapshot['confirmed_native_payment_ids'] ?? [], true)
            ) {
                continue;
            }
            if (isset($snapshot['attempts'][$id]) && (float) $row['amount'] > 0
                && in_array($snapshot['attempts'][$id]['status'], ['refunded', 'charged_back', 'in_mediation'], true)
            ) {
                $reversals[$id] = $snapshot['attempts'][$id];
            }
        }
        if ($reversals) {
            uasort($reversals, function ($a, $b) {
                return ($a['updated_at'] <=> $b['updated_at']) ?: ((strlen($a['id']) <=> strlen($b['id'])) ?: strcmp($a['id'], $b['id']));
            });
            $last = end($reversals);
            $snapshot['effective_status'] = $last['status'];
        }

        return $snapshot;
    }

    /** Calculate the wallet total before its temporary CartRule changes the cart. */
    protected function createOrderFromSnapshot($cart, bool $allowCreation): void
    {
        $this->total = $this->getTotal($cart, $this->checkout);
        $this->validateOrderState();
        if (!$allowCreation || $this->amount < $this->total || $this->total <= 0
            || !in_array($this->status, ['approved', 'pending', 'in_process', 'authorized'], true)
        ) {
            $negative = in_array($this->status, ['rejected', 'cancelled'], true);
            if ($this->cartRegistration !== null) {
                $this->saveUpdateOrderData($cart);
            }
            $this->checkoutOutcome = $this->approved > 0 && $this->approved < $this->total
                ? 'partial_funds' : 'no_order';
            $this->setNotificationResponse('No order created for this notification', $allowCreation || $negative ? 200 : 404);

            return;
        }
        try {
            if ($this->checkout === 'wallet_button') {
                $this->preference->setCartRule($cart, Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT'));
            }
            $this->createOrder($cart);
        } finally {
            if ($this->checkout === 'wallet_button' && !$this->walletCleanupDone) {
                $this->preference->disableCartRule();
            }
        }
    }

    protected function loadPaymentSnapshot($cart): array
    {
        throw new RuntimeException('A checkout-specific payment loader is required');
    }

    /** A synchronous caller needs a verified order, not a callback ACK. */
    protected function getSynchronousOrder($cart): Order
    {
        if ($this->responseCode === null || $this->responseCode >= 400) {
            throw new RuntimeException('Could not complete checkout order reconciliation');
        }
        $id = $this->findOrderId($cart);
        if (!$id) {
            throw new RuntimeException('Synchronous checkout did not create a verified order');
        }
        $order = $this->loadLockedOrder($id);
        $registration = $this->mp_transaction->getReconciliationForCart((int) $cart->id);
        $registered = new Order((int) $registration['order_id']);
        if (!Validate::isLoadedObject($order) || !Validate::isLoadedObject($registered)
            || $order->module !== 'mercadopago' || $registered->module !== 'mercadopago'
            || (int) $order->id_cart !== (int) $cart->id || $registered->reference !== $order->reference
        ) {
            throw new RuntimeException('Synchronous checkout order ownership could not be verified');
        }
        $this->order_id = $id;

        return $order;
    }

    /** A retired generation may leave a new scope only after fresh full refund proof. */
    protected function archiveClosedHistoricalAttempt($cart, array $payment): bool
    {
        if ($this->findOrderId($cart) || ($payment['status'] ?? null) !== 'refunded') {
            return false;
        }
        require_once __DIR__ . '/CartPaymentInitiation.php';
        if (!(new CartPaymentInitiation($this->mercadopago))->canArchiveClosedPayment($cart, $payment)) {
            return false;
        }
        $audit = PaymentReconciliationSnapshot::fromPayments([$payment], in_array($payment['currency_id'], ['CLP', 'COP'], true) ? 0 : 2);
        $this->auditOnlyAttempts = array_replace($this->auditOnlyAttempts, $audit['attempts']);

        return true;
    }

    /** Keep verified negative history outside the current financial scope. */
    protected function archiveNegativeAttempt(array $payment): void
    {
        if (!in_array($payment['status'] ?? null, ['rejected', 'cancelled'], true)) {
            throw new RuntimeException('Only terminal negative attempts can leave the financial scope');
        }
        $precision = in_array($payment['currency_id'] ?? null, ['CLP', 'COP'], true) ? 0 : 2;
        $audit = PaymentReconciliationSnapshot::fromPayments([$payment], $precision);
        $this->auditOnlyAttempts = array_replace($this->auditOnlyAttempts, $audit['attempts']);
    }

    protected function findOrderId($cart): int
    {
        return (int) Db::getInstance()->getValue('SELECT id_order FROM `' . _DB_PREFIX_ . 'orders` WHERE id_cart=' . (int) $cart->id . ' ORDER BY id_order', false);
    }

    private function captureLookupContext(int $cartId, bool $locking): array
    {
        return (new CartPaymentContext())->capture($cartId, $locking);
    }

    private function lookupContextRows(string $query): array
    {
        $rows = Db::getInstance()->executeS($query, true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Payment context could not be read');
        }

        return $rows;
    }

    protected function loadOrdersForCart($cart, bool $locking = true): array
    {
        $orderId = $this->findOrderId($cart);
        if (!$orderId) {
            return [];
        }
        $base = Db::getInstance()->getRow('SELECT reference FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order=' . $orderId, false);
        if (!$base) {
            throw new RuntimeException('Order reference could not be revalidated');
        }
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE reference=\'' . pSQL($base['reference']) . '\' ORDER BY id_order' . ($locking ? ' FOR UPDATE' : ''), true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Could not load order reference');
        }
        $orders = [];
        foreach ($rows as $row) {
            $order = new Order((int) $row['id_order']);
            $order->hydrate($row);
            if ((int) $order->id_cart !== (int) $cart->id) {
                throw new RuntimeException('Order reference crosses cart ownership');
            }
            $orders[] = $order;
        }

        return $orders;
    }

    /** Persist intent before core may announce any order, then leave SQL autocommit on. */
    private function beginNativeCreation($cart): array
    {
        $registration = $this->mp_transaction->getReconciliationForCart((int) $cart->id);
        if (!$this->referenceTransactionActive || !empty($registration['creation_recovery']) || !empty($registration['order_id'])) {
            throw new RuntimeException('Native creation cannot be started safely');
        }
        $attempts = [];
        foreach ($this->snapshot['attempts'] as $attempt) {
            $identity = [];
            foreach (['id', 'currency', 'nominal_amount', 'type', 'method', 'merchant_order_id'] as $field) {
                $identity[$field] = $attempt[$field];
            }
            $attempts[(string) $attempt['id']] = $identity;
        }
        $recovery = ['intent' => [
            'version' => 1, 'cart_id' => (int) $cart->id, 'customer_id' => (int) $cart->id_customer,
            'currency_id' => (int) $cart->id_currency, 'checkout' => $this->checkout,
            'precision' => $this->snapshot['precision'],
            'total' => PaymentReconciliationSnapshot::decimal(PaymentReconciliationSnapshot::units($this->total, $this->snapshot['precision']), $this->snapshot['precision']),
            'attempts' => $attempts, 'approved_receipts' => $this->snapshot['receipts'],
            'wallet_rule_id' => $this->checkout === 'wallet_button' ? (int) ($this->preference->cart_rule ?? 0) : 0,
        ]];
        $this->persistCreationRecovery($cart, $recovery);
        $this->reconciliationPhase = 'creation_intent_commit';
        OrderPaymentReconciler::assertPersistenceSucceeded(Db::getInstance()->execute('COMMIT'), 'commit native creation intent');
        $this->referenceTransactionActive = false;

        return $recovery;
    }

    private function persistCreationRecovery($cart, array $recovery): void
    {
        $encoded = json_encode($recovery, JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Could not serialize native creation evidence');
        }
        $this->mp_transaction->persistReconciliation((int) $cart->id, ['creation_recovery' => $encoded]);
    }

    private function clearCreationRecovery($cart): void
    {
        $this->mp_transaction->persistReconciliation((int) $cart->id, ['creation_recovery' => '']);
        $this->nativeRecovery = null;
    }

    private function nativeCreationEvidence($cart, array $orders): array
    {
        if (!$orders) {
            throw new RuntimeException('Native creation has no order group');
        }
        $first = reset($orders);
        $reference = (string) $first->reference;
        $identities = [];
        foreach ($orders as $order) {
            if ($reference === '' || $order->reference !== $reference || $order->module !== 'mercadopago'
                || (int) $order->id_cart !== (int) $cart->id || (int) $order->id_customer !== (int) $cart->id_customer
                || (int) $order->id_currency !== (int) $cart->id_currency
            ) {
                throw new RuntimeException('Native order group ownership could not be verified');
            }
            $history = Db::getInstance()->getRow('SELECT id_order_history, id_order_state FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order=' . (int) $order->id . ' ORDER BY id_order_history DESC', false);
            if (!$history || (int) $history['id_order_state'] !== (int) $order->current_state) {
                throw new RuntimeException('Native order history could not be verified');
            }
            $identities[] = ['id' => (int) $order->id, 'state' => (int) $order->current_state,
                'history_id' => (int) $history['id_order_history']];
        }
        $payments = Db::getInstance()->executeS('SELECT id_order_payment, transaction_id, amount, id_currency, payment_method FROM `' . _DB_PREFIX_ . 'order_payment` WHERE order_reference=\'' . pSQL($reference) . '\' ORDER BY id_order_payment', true, false);
        if (!is_array($payments)) {
            throw new RuntimeException('Could not read native checkout receipts');
        }

        return ['reference' => $reference, 'orders' => $identities, 'payments' => $payments];
    }

    private function validateNativeRecovery($cart, array $registration, array $orders): array
    {
        $recovery = json_decode($registration['creation_recovery'] ?? '', true);
        if (!is_array($recovery) || !isset($recovery['intent'], $recovery['result'])
            || !is_array($recovery['intent']) || !is_array($recovery['result'])
        ) {
            throw new RuntimeException('Native creation completion requires individual review');
        }
        $intent = $recovery['intent'];
        if (($intent['version'] ?? null) !== 1 || ($intent['cart_id'] ?? null) !== (int) $cart->id
            || ($intent['customer_id'] ?? null) !== (int) $cart->id_customer
            || ($intent['currency_id'] ?? null) !== (int) $cart->id_currency
            || !isset($intent['checkout'], $intent['attempts'], $intent['approved_receipts'], $intent['total'], $intent['precision'])
            || !is_array($intent['attempts']) || !is_array($intent['approved_receipts'])
            || $recovery['result'] !== $this->nativeCreationEvidence($cart, $orders)
        ) {
            throw new RuntimeException('Native creation evidence does not match the cart');
        }
        $this->assertNativeCreationTotal($orders, $intent);

        return $recovery;
    }

    private function assertNativeCreationTotal(array $orders, array $intent): void
    {
        $total = array_sum(array_map(function ($order) {
            return (float) $order->total_paid;
        }, $orders));
        if (PaymentReconciliationSnapshot::units($total, $intent['precision']) !== PaymentReconciliationSnapshot::units($intent['total'], $intent['precision'])) {
            throw new RuntimeException('Native creation total changed');
        }
    }

    /** A provisional core row is not evidence that a reversed attempt was approved. */
    private function prepareProvisionalSnapshot(array &$snapshot, array $recovery): void
    {
        $intent = $recovery['intent'];
        if ($intent['checkout'] !== $this->checkout || $intent['precision'] !== $snapshot['precision']) {
            throw new RuntimeException('Native creation checkout identity changed');
        }
        foreach ($intent['attempts'] as $id => $identity) {
            if (!isset($snapshot['attempts'][$id]) || !is_array($identity)) {
                throw new RuntimeException('Native creation payment could not be revalidated');
            }
            foreach (['id', 'currency', 'nominal_amount', 'type', 'method', 'merchant_order_id'] as $field) {
                if (!array_key_exists($field, $identity) || $identity[$field] !== $snapshot['attempts'][$id][$field]) {
                    throw new RuntimeException('Native creation payment identity changed');
                }
            }
        }
        $snapshot['native_payment_ids'] = [];
        $snapshot['confirmed_native_payment_ids'] = [];
        foreach ($recovery['result']['payments'] as $payment) {
            $nativeId = (int) $payment['id_order_payment'];
            $id = (string) $payment['transaction_id'];
            $snapshot['native_payment_ids'][] = $nativeId;
            if (isset($intent['approved_receipts'][$id])
                && PaymentReconciliationSnapshot::units($payment['amount'], $snapshot['precision'])
                    === PaymentReconciliationSnapshot::units($intent['approved_receipts'][$id]['amount'], $snapshot['precision'])
            ) {
                $snapshot['confirmed_native_payment_ids'][] = $nativeId;
            }
        }
        foreach ($intent['approved_receipts'] as $id => $receipt) {
            if (isset($snapshot['attempts'][$id]) && in_array($snapshot['attempts'][$id]['status'], ['refunded', 'charged_back', 'in_mediation'], true)) {
                $found = false;
                foreach ($recovery['result']['payments'] as $payment) {
                    if ((string) $payment['transaction_id'] === (string) $id
                        && in_array((int) $payment['id_order_payment'], $snapshot['confirmed_native_payment_ids'], true)
                    ) {
                        $found = true;
                    }
                }
                if (!$found) {
                    throw new RuntimeException('Reversed provisional receipt requires individual review');
                }
            }
        }
    }

    private function recoverNativeCreation($cart, array $orders): void
    {
        if ($this->nativeRecovery === null) {
            return;
        }
        $first = reset($orders);
        $this->order_id = (int) $first->id;
        $this->saveCreateOrderData($cart);
    }

    /** A committed wallet rule must be disabled even when a prior native call was interrupted. */
    private function cleanupInterruptedWalletRule($cart): void
    {
        $registration = $this->mp_transaction->findReconciliationForCart((int) $cart->id);
        if ($registration === null || empty($registration['creation_recovery'])) {
            return;
        }
        $recovery = json_decode($registration['creation_recovery'], true);
        $intent = $recovery['intent'] ?? [];
        $ruleId = (int) ($intent['wallet_rule_id'] ?? 0);
        if ($ruleId <= 0) {
            return;
        }
        $query = 'SELECT c.id_customer,cu.secure_key,cr.active FROM `' . _DB_PREFIX_ . 'cart` c'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'customer` cu ON cu.id_customer=c.id_customer'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'cart_cart_rule` ccr ON ccr.id_cart=c.id_cart'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'cart_rule` cr ON cr.id_cart_rule=ccr.id_cart_rule'
            . ' WHERE c.id_cart=' . (int) $cart->id . ' AND cr.id_cart_rule=' . $ruleId
            . ' AND cr.code=\'' . pSQL('MPDISCOUNT' . (int) $cart->id) . '\'';
        $check = function ($rows, $currentRegistration) use ($intent, $cart) {
            if (!is_array($rows) || count($rows) !== 1 || ($intent['checkout'] ?? null) !== 'wallet_button'
                || (int) ($intent['cart_id'] ?? 0) !== (int) $cart->id
                || (int) ($intent['customer_id'] ?? 0) !== (int) $rows[0]['id_customer']
                || (int) $currentRegistration['customer_id'] !== (int) $rows[0]['id_customer']
                || ($this->customer_secure_key !== null && !hash_equals((string) $rows[0]['secure_key'], (string) $this->customer_secure_key))
            ) {
                throw new RuntimeException('Interrupted wallet discount ownership could not be verified');
            }
        };
        $rows = Db::getInstance()->executeS($query, true, false);
        $check($rows, $registration);
        if ((int) $rows[0]['active'] === 0) {
            return;
        }
        $this->startReferenceTransaction();
        $lockedRegistration = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'mp_transactions` WHERE cart_id=' . (int) $cart->id . ' FOR UPDATE');
        if (count($lockedRegistration) !== 1 || $lockedRegistration[0]['creation_recovery'] !== $registration['creation_recovery']) {
            throw new RuntimeException('Interrupted wallet discount intent changed');
        }
        $rows = Db::getInstance()->executeS($query . ' FOR UPDATE', true, false);
        $check($rows, $lockedRegistration[0]);
        $rule = new CartRule($ruleId);
        $rule->active = false;
        OrderPaymentReconciler::assertPersistenceSucceeded($rule->save(), 'disable interrupted wallet discount');
        $verified = Db::getInstance()->executeS('SELECT active FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE id_cart_rule=' . $ruleId, true, false);
        if (!is_array($verified) || count($verified) !== 1 || (int) $verified[0]['active'] !== 0) {
            throw new RuntimeException('Interrupted wallet discount cleanup could not be verified');
        }
        OrderPaymentReconciler::assertPersistenceSucceeded(Db::getInstance()->execute('COMMIT'), 'commit wallet discount cleanup');
        $this->referenceTransactionActive = false;
    }

    /** Known IDs are fetched again, never trusted as a captured financial snapshot. */
    protected function knownPaymentIds($cart, bool $allowMissing = false): array
    {
        $locking = $this->lookupRevalidating;
        if ($locking) {
            $registrations = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'mp_transactions` WHERE cart_id=' . (int) $cart->id . ' FOR UPDATE');
            if (count($registrations) > 1 || (!$allowMissing && !$registrations)) {
                throw new RuntimeException('Mercado Pago cart registration is missing or ambiguous');
            }
            $registered = $registrations ? $registrations[0] : null;
        } else {
            $registered = $allowMissing ? $this->mp_transaction->findReconciliationForCart((int) $cart->id) : $this->mp_transaction->getReconciliationForCart((int) $cart->id);
        }
        $this->cartRegistration = $registered;
        if ($registered !== null && (int) $registered['customer_id'] !== (int) $cart->id_customer) {
            throw new RuntimeException('Payment registration customer mismatch');
        }
        $customers = Db::getInstance()->executeS('SELECT id_customer,secure_key FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer=' . (int) $cart->id_customer . ($locking ? ' FOR UPDATE' : ''), true, false);
        if (!is_array($customers) || count($customers) !== 1
            || ($this->customer_secure_key !== null && !hash_equals((string) $customers[0]['secure_key'], (string) $this->customer_secure_key))
        ) {
            throw new RuntimeException('Payment customer authorization changed');
        }
        if ($registered === null) {
            if ($this->findOrderId($cart)) {
                throw new RuntimeException('Existing order has no payment registration');
            }

            if (!$locking) {
                $this->lookupKnownIds = [];
                $this->lookupAllowsMissingRegistration = $allowMissing;
            }

            return [];
        }
        $ids = empty($registered['payment_id']) ? [] : explode(',', $registered['payment_id']);
        $recovery = null;
        if (!empty($registered['creation_recovery'])) {
            $recovery = json_decode($registered['creation_recovery'], true);
            if (!is_array($recovery) || empty($recovery['intent']['attempts']) || !is_array($recovery['intent']['attempts'])) {
                throw new RuntimeException('Native creation intent is invalid');
            }
            $ids = array_merge($ids, array_keys($recovery['intent']['attempts']));
        }
        $orderId = $this->findOrderId($cart);
        if ($orderId) {
            $order = new Order($orderId);
            if (empty($registered['order_id'])) {
                $this->nativeRecovery = $this->validateNativeRecovery($cart, $registered, $this->loadOrdersForCart($cart, $locking));
                $this->nativeCompletionRecorded = true;
            } else {
                if ($recovery !== null) {
                    throw new RuntimeException('Registered order has unfinished creation evidence');
                }
                $registeredOrder = new Order((int) $registered['order_id']);
                if (!Validate::isLoadedObject($registeredOrder) || $order->reference !== $registeredOrder->reference) {
                    throw new RuntimeException('Order registration reference mismatch');
                }
            }
            $rows = Db::getInstance()->executeS('SELECT transaction_id FROM `' . _DB_PREFIX_ . 'order_payment` WHERE order_reference=\'' . pSQL($order->reference) . '\' AND payment_method=\'Mercado Pago\'', true, false);
            if (!is_array($rows)) {
                throw new RuntimeException('Could not load registered payment IDs');
            }
            foreach ($rows as $row) {
                if ((string) $row['transaction_id'] !== '') {
                    $this->recordedPaymentIds[(string) $row['transaction_id']] = true;
                }
            }
            $ids = array_merge($ids, array_column($rows, 'transaction_id'));
        } elseif ($recovery !== null) {
            throw new RuntimeException('Interrupted native creation requires individual review');
        }
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return (string) $id !== '';
        })));
        foreach ($ids as $id) {
            if (!preg_match('/^[1-9][0-9]{0,19}$/D', (string) $id)) {
                throw new RuntimeException('Invalid registered payment identity');
            }
        }

        if (!$locking) {
            $this->lookupKnownIds = $ids;
            $this->lookupAllowsMissingRegistration = $allowMissing;
        }

        return $ids;
    }

    private function withCartPaymentLock($cart, callable $work, bool $transactionAtEntry = true): void
    {
        $db = Db::getInstance();
        $lockName = CartPaymentLock::name((int) $cart->id);
        $acquired = false;
        $completed = false;
        $this->referenceTransactionActive = false;
        $this->nativeRecovery = null;
        $this->walletCleanupDone = false;
        $this->deferredStates = [];
        $this->nativeCreationStarted = false;
        $this->nativeCompletionRecorded = false;
        $this->lookupKnownIds = [];
        $this->lookupAllowsMissingRegistration = false;
        $this->lookupRevalidating = false;
        try {
            $acquired = (int) $db->getValue('SELECT GET_LOCK(\'' . pSQL($lockName) . '\', 5)', false) === 1;
            if (!$acquired) {
                $this->setNotificationResponse('Could not acquire notification processing lock', 409);

                return;
            }
            if ($transactionAtEntry) {
                $this->startReferenceTransaction();
            }
            $this->reconciliationPhase = 'processing';
            $work();
            $awaitingReturn = $this->responseCode === 404 && !$this->financialReconciled && !$this->reconciliationOrders;
            if ($this->responseCode === null || ($this->responseCode >= 400 && !$awaitingReturn)) {
                throw new RuntimeException('Reference processing did not succeed');
            }
            if ($this->financialReconciled && !$this->referenceTransactionActive) {
                throw new RuntimeException('Financial reconciliation has no active transaction');
            }
            if ($this->financialReconciled) {
                $this->reconciliationPhase = 'postconditions';
                (new OrderPaymentReconciler())->assertConsistent($this->reconciliationOrders, $this->snapshot);
            }
            if ($this->referenceTransactionActive) {
                $this->reconciliationPhase = 'commit';
                OrderPaymentReconciler::assertPersistenceSucceeded($db->execute('COMMIT'), 'commit reference transaction');
                $this->referenceTransactionActive = false;
            }
            $this->applyDeferredStates();
            if ($this->financialReconciled) {
                (new OrderPaymentReconciler())->assertConsistent($this->reconciliationOrders, $this->snapshot);
            }
            $completed = true;
        } catch (Throwable $error) {
            $rollback = 'not_active';
            if ($this->referenceTransactionActive) {
                try {
                    OrderPaymentReconciler::assertPersistenceSucceeded($db->execute('ROLLBACK'), 'rollback reference transaction');
                    $rollback = 'statement_succeeded';
                } catch (Throwable $rollbackError) {
                    $rollback = 'unconfirmed';
                    MPLog::generate('Mercado Pago reference rollback could not be confirmed', 'error');
                }
                Cache::clean('order_invoice_paid_*');
                Cache::clean('objectmodel_Order_*');
                Cache::clean('objectmodel_OrderPayment_*');
                Cache::clean('objectmodel_OrderInvoice_*');
                Cache::clean('objectmodel_Cart_*');
                Cache::clean('Cart::getCartRules_*');
                $this->referenceTransactionActive = false;
            }
            MPLog::generate('Mercado Pago reference reconciliation failed: ' . json_encode([
                'cart_id' => (int) $cart->id,
                'phase' => $this->reconciliationPhase,
                'error_type' => get_class($error),
                'rollback' => $rollback,
                'commit_outcome_unknown' => in_array($this->reconciliationPhase, ['creation_intent_commit', 'commit'], true),
                'native_creation' => $this->nativeCompletionRecorded ? 'completion_recorded' : ($this->nativeCreationStarted ? 'may_be_partial' : 'not_observed'),
            ]), 'error');
            $this->setNotificationResponse('Could not reconcile payment; retry later', 503);
        } finally {
            if ($acquired && (int) $db->getValue('SELECT RELEASE_LOCK(\'' . pSQL($lockName) . '\')', false) !== 1) {
                MPLog::generate('Mercado Pago reference lock release could not be confirmed', 'error');
                $this->setNotificationResponse('Could not complete payment processing; retry later', 503);
            }
            if ($completed || $this->responseCode >= 400) {
                $this->logProcessingOutcome($cart);
            }
        }
    }

    /** Diagnostic logging must not change a committed result or request retries. */
    private function logProcessingOutcome($cart): void
    {
        try {
            $orderIds = array_map(function ($order) {
                return (int) $order->id;
            }, $this->reconciliationOrders);
            if (!$orderIds && $this->order_id) {
                $orderIds[] = (int) $this->order_id;
            }
            $checkout = in_array($this->checkout, ['pro', 'wallet_button', 'credit_card', 'pix', 'ticket', 'pse'], true)
                ? $this->checkout : 'unknown';
            $context = json_encode([
                'cart_id' => (int) $cart->id,
                'order_id' => (int) $this->order_id,
                'order_ids' => $orderIds,
                'payment_ids' => array_map('strval', array_keys($this->snapshot['attempts'] ?? [])),
                'checkout' => $checkout,
                'response_code' => (int) $this->responseCode,
                'outcome' => $this->responseMessage,
            ]);
            if ($context !== false) {
                MPLog::generate('Mercado Pago notification outcome: ' . $context, $this->responseCode >= 400 ? 'warning' : 'informative');
            }
        } catch (Throwable $loggingError) {
            // The result is already decided; another logger could fail or disclose context.
        }
    }

    private function startReferenceTransaction(): void
    {
        OrderPaymentReconciler::assertPersistenceSucceeded(Db::getInstance()->execute('START TRANSACTION'), 'start reference transaction');
        $this->referenceTransactionActive = true;
    }

    /** Execute native transitions synchronously after financial commit, under the cart lock. */
    private function applyDeferredStates(): void
    {
        foreach ($this->deferredStates as $entry) {
            $order = new Order((int) $entry['id']);
            $current = (int) Db::getInstance()->getValue('SELECT current_state FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order=' . (int) $entry['id'], false);
            $this->order_id = (int) $entry['id'];
            if ($current !== (int) $entry['actual'] || !$this->orderBelongsToMercadoPago($order)) {
                throw new RuntimeException('Order state changed after financial reconciliation');
            }
            $this->reconciliationPhase = 'state';
            $order->setCurrentState((int) $entry['target']);
            $persisted = Db::getInstance()->getRow('SELECT current_state FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order=' . (int) $order->id, false);
            $history = Db::getInstance()->getRow('SELECT id_order_state FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order=' . (int) $order->id . ' ORDER BY id_order_history DESC', false);
            if (!$persisted || (int) $persisted['current_state'] !== (int) $entry['target']
                || !$history || (int) $history['id_order_state'] !== (int) $entry['target']
            ) {
                throw new RuntimeException('Order state and history read-back failed');
            }
        }
    }

    private function assertAtomicStorage(): void
    {
        $tables = ['orders', 'order_payment', 'order_invoice', 'order_invoice_payment', 'order_history', 'mp_transactions'];
        $names = [];
        foreach ($tables as $table) {
            $names[] = "'" . pSQL(_DB_PREFIX_ . $table) . "'";
        }
        $rows = Db::getInstance()->executeS('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=\'' . pSQL(_DB_NAME_) . '\' AND TABLE_NAME IN (' . implode(',', $names) . ')', true, false);
        if (!is_array($rows) || count($rows) !== count($tables)) {
            throw new RuntimeException('Transactional payment storage could not be verified');
        }
        foreach ($rows as $row) {
            if (strtoupper($row['ENGINE']) !== 'INNODB') {
                throw new RuntimeException('Payment reconciliation requires transactional storage');
            }
        }
    }

    private function loadLockedOrder(int $orderId): Order
    {
        $order = new Order($orderId);
        if ($this->snapshot === null) {
            return $order;
        }
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order=' . $orderId . ' FOR UPDATE', true, false);
        if (!is_array($rows) || count($rows) !== 1) {
            throw new RuntimeException('Order could not be revalidated');
        }
        $order->hydrate($rows[0]);
        $order->clearCache();

        return $order;
    }

    private function applyOrdersLocked($cart, $orders): void
    {
        $this->reconciliationOrders = [];
        $this->financialReconciled = false;
        $reversal = in_array($this->status, ['refunded', 'charged_back', 'in_mediation'], true);
        foreach ($orders as $item) {
            $order = $this->loadLockedOrder((int) $item->id);
            $this->order_id = $order->id;
            $actual = (int) $order->getCurrentState();
            if ($this->snapshot !== null && ($this->isSameOutcome($actual, $this->order_state)
                || ($this->isApprovedEquivalent($actual) && $this->order_state == $this->getNotificationPaymentState('approved')))
            ) {
                $registration = $this->mp_transaction->getReconciliationForCart((int) $cart->id);
                if (!empty($registration['payment_status'])) {
                    $history = Db::getInstance()->getRow('SELECT id_order_state FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order=' . (int) $order->id . ' ORDER BY id_order_history DESC', false);
                    if (!$history || (int) $history['id_order_state'] !== $actual) {
                        throw new RuntimeException('Order state history requires individual review');
                    }
                }
            }
            $valid = $this->validateActualStatus($actual, $order);
            $fulfillment = in_array($actual, array_map('intval', array_filter([
                Configuration::get('PS_OS_SHIPPING'), Configuration::get('PS_OS_DELIVERED'), Configuration::get('PS_OS_REFUND'),
            ])), true);
            if ($this->snapshot !== null && !$this->orderBelongsToMercadoPago($order)) {
                $this->setNotificationResponse('The reference is not registered to Mercado Pago', 200);

                return;
            }
            if (!$valid && !($reversal && $fulfillment && $this->orderBelongsToMercadoPago($order))) {
                $this->setNotificationResponse($this->isSameOutcome($actual, $this->order_state)
                    ? 'Order status is the same' : 'The order has been updated to a status that does not belong to MP', 200);

                return;
            }
            if (!$reversal && $this->isApprovedEquivalent($actual) && $this->order_state != $this->getNotificationPaymentState('approved')) {
                $this->setNotificationResponse('It is not possible to update this approved payment', 200);

                return;
            }
            $this->reconciliationOrders[] = $order;
        }
        foreach ($this->reconciliationOrders as $order) {
            $this->order_id = $order->id;
            $this->updateOrderLocked($cart);
        }
    }

    /**
     * Body of updateOrder()/updateOrders(), executed while the per-cart notification lock is
     * held. Concurrent notifications for the same cart — e.g. two idempotent
     * "same state" callbacks — must not interleave their read of the current
     * order state with their OrderPayment mutations, or one can delete
     * payment rows the other just recreated.
     *
     * @param mixed $cart
     *
     * @return void
     */
    private function updateOrderLocked($cart)
    {
        $order = $this->loadLockedOrder((int) $this->order_id);
        $actual_status = (int) $order->getCurrentState();
        $validate_actual = $this->validateActualStatus($actual_status, $order);

        $status_approved = $this->getNotificationPaymentState('approved');
        $status_pending = $this->getNotificationPaymentState('pending');
        $status_inprocess = $this->getNotificationPaymentState('in_process');
        $status_authorized = $this->getNotificationPaymentState('authorized');
        $status_cancelled = $this->getNotificationPaymentState('cancelled');
        $status_rejected = $this->getNotificationPaymentState('rejected');
        $status_refunded = $this->getNotificationPaymentState('refunded');
        $status_charged = $this->getNotificationPaymentState('charged_back');
        $status_mediation = $this->getNotificationPaymentState('in_mediation');
        $status_fraud = $this->getNotificationPaymentState('possible_fraud');

        if ($this->order_id != 0 && $this->status != null) {
            switch ($this->order_state) {
                case $status_approved:
                    MPLog::generate('Entered the APPROVED rule');
                    $this->ruleApproved($cart, $order, $status_approved, $actual_status, $validate_actual);
                    break;

                case $status_pending:
                    MPLog::generate('Entered the PENDING rule');
                    $this->ruleProcessing($cart, $order, $status_pending, $actual_status, $validate_actual);
                    break;

                case $status_inprocess:
                    MPLog::generate('Entered the IN_PROCESS rule');
                    $this->ruleProcessing($cart, $order, $status_inprocess, $actual_status, $validate_actual);
                    break;

                case $status_authorized:
                    MPLog::generate('Entered the AUTHORIZED rule');
                    $this->ruleProcessing($cart, $order, $status_authorized, $actual_status, $validate_actual);
                    break;

                case $status_cancelled:
                    MPLog::generate('Entered the CANCELLED rule');
                    $this->ruleFailed($cart, $order, $status_cancelled, $actual_status, $validate_actual);
                    break;

                case $status_rejected:
                    MPLog::generate('Entered the REJECTED rule');
                    $this->ruleFailed($cart, $order, $status_rejected, $actual_status, $validate_actual);
                    break;

                case $status_refunded:
                    MPLog::generate('Entered the REFUNDED rule');
                    $this->ruleDevolution($cart, $order, $status_refunded, $actual_status, $validate_actual);
                    break;

                case $status_charged:
                    MPLog::generate('Entered the CHARGED_BACK rule');
                    $this->ruleDevolution($cart, $order, $status_charged, $actual_status, $validate_actual);
                    break;

                case $status_mediation:
                    MPLog::generate('Entered the MEDIATION rule');
                    $this->ruleDevolution($cart, $order, $status_mediation, $actual_status, $validate_actual);
                    break;

                case $status_fraud:
                    MPLog::generate('Entered the POSSIBLE_FRAUD rule');
                    $this->ruleFraud($cart, $order, $actual_status, $validate_actual);
                    break;

                default:
                    MPLog::generate('Order state not recognized by Mercado Pago', 'warning');
                    $this->setNotificationResponse('Order state not recognized by Mercado Pago', 200);

                    break;
            }
        } else {
            MPLog::generate('Order does not exist', 'error');
            $this->setNotificationResponse('Order does not exist', 404);
        }
    }

    /**
     * Rule to update approved order
     *
     * @return void
     */
    public function ruleApproved($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($this->isApprovedEquivalent($actual_status)) {
            $this->reconcileSameStatePayment($cart, $order, $validate_actual);
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->total > $this->approved) {
            $this->ruleFraud($cart, $order, $actual_status, $validate_actual);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleProcessing($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($actual_status == $status) {
            $this->reconcileSameStatePayment($cart, $order, $validate_actual);
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->isApprovedEquivalent($actual_status)) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->setNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleFailed($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($this->isSameOutcome($actual_status, $status)) {
            $this->reconcileSameStatePayment($cart, $order, $validate_actual);
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->isApprovedEquivalent($actual_status)) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->setNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update chargedback, refunded and inmediation order
     *
     * @return void
     */
    public function ruleDevolution($cart, $order, $status, $actual_status, $validate_actual = null)
    {
        if ($validate_actual === null) {
            $validate_actual = $this->validateActualStatus($actual_status, $order);
        }

        // Financial reversals remain valid after fulfillment, but only for an owned order.
        if (in_array((int) $actual_status, array_map('intval', array_filter([
            Configuration::get('PS_OS_SHIPPING'),
            Configuration::get('PS_OS_DELIVERED'),
            Configuration::get('PS_OS_REFUND'),
        ])), true) && $this->orderBelongsToMercadoPago($order)) {
            $validate_actual = true;
        }

        if ($actual_status == $status) {
            $this->reconcileSameStatePayment($cart, $order, $validate_actual);
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update order with payment with possible fraud
     *
     * @return void
     */
    public function ruleFraud($cart, $order, $actual_status, $validate_actual)
    {
        MPLog::generate('The order ' . $this->order_id . ' have a possible payment fraud', 'error');

        $status_fraud = $this->getNotificationPaymentState('possible_fraud');
        $this->order_state = $status_fraud;

        if ($actual_status == $status_fraud) {
            $this->reconcileSameStatePayment($cart, $order, $validate_actual);
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($validate_actual == true) {
            MPLog::generate('The order ' . $this->order_id . ' has been updated to possible fraud status', 'error');
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /** Reconcile an owned same-state event without another history transition.
     * Persistence failures propagate to the reference transaction owner.
     */
    private function reconcileSameStatePayment($cart, $order, $validate_actual): void
    {
        if ($validate_actual != true) {
            return;
        }

        $this->updateOrderTransaction($order);
        $this->saveUpdateOrderData($cart);
    }

    /**
     * Update order on Prestashop database
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function updatePrestashopOrder($cart, $order)
    {
        $targetState = $this->getTargetOrderState($this->order_state);
        $this->updateOrderTransaction($order);
        $this->saveUpdateOrderData($cart);
        $this->deferredStates[] = ['id' => (int) $order->id, 'actual' => (int) $order->getCurrentState(), 'target' => $targetState];
        $this->setNotificationResponse('The order has been updated', 201);
    }

    /**
     * Save payments info on mp_transaction table
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function saveCreateOrderData($cart)
    {
        $this->mp_transaction->persistReconciliation((int) $cart->id, ['order_id' => (int) $this->order_id]);
        $this->saveUpdateOrderData($cart);
    }

    /** Persist all attempts, aligned and with the real merchant-order identity. */
    public function saveUpdateOrderData($cart)
    {
        $this->reconciliationPhase = 'audit';
        $data = ['received_webhook' => 1];
        foreach (['id', 'type', 'method', 'status', 'amount'] as $field) {
            $data['payment_' . $field] = $this->verifyValue('payments_' . $field) ?? '';
        }
        if ($this instanceof IpnNotification && !empty($this->merchantOrderId)) {
            $data['merchant_order_id'] = $this->merchantOrderId;
        }
        if ($this->snapshot !== null) {
            $registration = $this->mp_transaction->getReconciliationForCart((int) $cart->id);
            $attempts = array_replace(
                $this->mp_transaction->getStoredPaymentAttempts($registration),
                $this->auditOnlyAttempts,
                $this->snapshot['attempts']
            );
            ksort($attempts, SORT_STRING);
            $encoded = json_encode($attempts, JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new RuntimeException('Could not serialize verified payment audit');
            }
            $data['payment_attempts'] = $encoded;
        }
        $this->mp_transaction->persistReconciliation((int) $cart->id, $data);
    }

    /**
     * @param $state
     *
     * @return mixed
     */
    public function getNotificationPaymentState($state)
    {
        $payment_states = [
            'in_process' => 'MERCADOPAGO_STATUS_0',
            'approved' => 'MERCADOPAGO_STATUS_1',
            'cancelled' => 'MERCADOPAGO_STATUS_2',
            'rejected' => 'MERCADOPAGO_STATUS_3',
            'refunded' => 'MERCADOPAGO_STATUS_4',
            'charged_back' => 'MERCADOPAGO_STATUS_5',
            'in_mediation' => 'MERCADOPAGO_STATUS_6',
            'pending' => 'MERCADOPAGO_STATUS_7',
            'authorized' => 'MERCADOPAGO_STATUS_8',
            'possible_fraud' => 'MERCADOPAGO_STATUS_9',
        ];

        return Configuration::get($payment_states[$state]);
    }

    /**
     * @param int $actual
     *
     * @return bool
     */
    public function validateActualStatus($actual, $order = null)
    {
        $result = $this->ps_order_state->where('id_order_state', '=', (int) $actual)->get();

        if ($result['module_name'] === 'mercadopago'
            || $this->getBackOrderStatus($actual)
            || (in_array((int) $actual, $this->getNativeCompatibleStates(), true)
                && $this->orderBelongsToMercadoPago($order))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Native PrestaShop order states accepted as a valid starting point for a Mercado Pago
     * notification to transition an order from, even though they don't belong to this module
     * (PPSP-1575). Before this, any order moved into a native state — manually by a seller, or
     * by another module — permanently stopped receiving MP synchronization: validateActualStatus()
     * rejected every state except this module's own 10, silently dropping the notification with a
     * non-retryable 200 (see traps.md).
     *
     * Deliberately limited to payment-adjacent states, not shipping/delivery/refund
     * (PS_OS_SHIPPING/PS_OS_DELIVERED/PS_OS_REFUND) — a late or duplicate webhook must not be
     * able to walk an order back to a payment state after it has already moved past payment
     * resolution into fulfillment.
     *
     * Being in one of these states is necessary but not sufficient — orderBelongsToMercadoPago()
     * must also confirm this order, or a split-order sibling with the same PrestaShop reference,
     * was created by this module before a native-compatible state is trusted.
     *
     * @return int[]
     */
    public function getNativeCompatibleStates()
    {
        return array_map('intval', array_filter([
            Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_WS_PAYMENT'),
            Configuration::get('PS_OS_ERROR'),
            Configuration::get('PS_OS_CANCELED'),
        ]));
    }

    /**
     * Whether this order belongs to Mercado Pago. The exact `mp_transactions.order_id`, written
     * by saveCreateOrderData(), is preferred. For PrestaShop split orders, a registered sibling
     * with the same reference also proves ownership because the table stores only one order ID
     * per cart (PPSP-1575).
     *
     * A native-compatible state (getNativeCompatibleStates()) alone can't distinguish "this
     * module created the order and a seller later moved it to a native state" (safe — Hyago's
     * repro) from "a DIFFERENT payment module created an unrelated order directly in that same
     * native state for the same cart" (unsafe — confirmed by reproduction: a stale/duplicate MP
     * notification could otherwise flip an order paid through another method to "Transaction
     * Declined"). The exact ID or the registered order's shared reference proves ownership;
     * cart identity alone does not.
     *
     * @return bool
     */
    public function orderBelongsToMercadoPago($order = null)
    {
        if (empty($this->order_id)) {
            return false;
        }

        if ($this->hasMercadoPagoTransactionForOrder($this->order_id)) {
            return true;
        }

        if ($order === null) {
            $order = new Order((int) $this->order_id);
        }

        if (empty($order->reference)) {
            return false;
        }

        $orders = Order::getByReference($order->reference);
        foreach ($orders as $relatedOrder) {
            if ($this->hasMercadoPagoTransactionForOrder($relatedOrder->id)) {
                MPLog::generate(
                    'Order ' . (int) $this->order_id . ' belongs to Mercado Pago through split order '
                    . (int) $relatedOrder->id
                );

                return true;
            }
        }

        return false;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    private function hasMercadoPagoTransactionForOrder($orderId)
    {
        $result = $this->mp_transaction->where('order_id', '=', (int) $orderId)->get();

        return !empty($result)
            && isset($result['order_id'])
            && (int) $result['order_id'] === (int) $orderId;
    }

    /**
     * Maps this module's own "rejected"/"cancelled" MP states to the equivalent native
     * PrestaShop state for later notification-driven transitions (PPSP-1575). The creation
     * entrypoints deliberately do not create orders for negative payment outcomes, so this
     * mapping is only used by updatePrestashopOrder().
     *
     * Every other outcome (approved, pending, in_process, authorized, refunded, charged_back,
     * in_mediation, possible_fraud) is unaffected and keeps this module's own state/label.
     *
     * @param mixed $moduleState one of this module's own getNotificationPaymentState() values
     *
     * @return int
     */
    public function getTargetOrderState($moduleState)
    {
        if ($moduleState == $this->getNotificationPaymentState('rejected')) {
            return (int) Configuration::get('PS_OS_ERROR');
        }

        if ($moduleState == $this->getNotificationPaymentState('cancelled')) {
            return (int) Configuration::get('PS_OS_CANCELED');
        }

        return (int) $moduleState;
    }

    /**
     * Whether $actual (the order's real current state) already represents the same outcome as
     * $moduleState (one of this module's own MP states), accounting for getTargetOrderState()'s
     * substitution — so a declined/cancelled order sitting on the native ID still short-circuits
     * as "order status is the same" (200, no-op) instead of re-running updatePrestashopOrder()
     * on every duplicate/retried notification (PPSP-1575).
     *
     * @param int $actual
     * @param mixed $moduleState
     *
     * @return bool
     */
    public function isSameOutcome($actual, $moduleState)
    {
        return (int) $actual === (int) $moduleState || (int) $actual === $this->getTargetOrderState($moduleState);
    }

    /**
     * Whether $actual represents an "approved" order for the "can't downgrade an approved
     * payment" guard in ruleApproved()/ruleProcessing()/ruleFailed() — true both for this
     * module's own approved state and for the native PS_OS_PAYMENT/PS_OS_WS_PAYMENT states that
     * validateActualStatus() now also treats as ours (PPSP-1575).
     *
     * Confirmed by reproduction: without this, an MP-owned order sitting in a native "paid"
     * state (getNativeCompatibleStates()) — e.g. moved there manually by a seller — could be
     * silently downgraded back to pending/in_process/rejected by a later notification, because
     * the guard only recognized this module's own approved ID. That's the same class of bug
     * this ticket closed in the opposite direction (a native state blocking sync entirely).
     *
     * @param int $actual
     *
     * @return bool
     */
    public function isApprovedEquivalent($actual)
    {
        $actual = (int) $actual;

        if ($actual === (int) $this->getNotificationPaymentState('approved')) {
            return true;
        }

        return in_array($actual, [
            (int) Configuration::get('PS_OS_PAYMENT'),
            (int) Configuration::get('PS_OS_WS_PAYMENT'),
        ], true);
    }

    /**
     * @param int $actual
     *
     * @return bool
     */
    public function getBackOrderStatus($actual)
    {
        $result = $this->ps_order_state_lang->columns(['id_order_state', 'name'])
            ->where('template', '=', 'outofstock')
            ->getAll();

        $count = count($result);

        foreach ($result as $row) {
            if ($row['id_order_state'] == $actual && $count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Records the outcome of processing this notification, without sending anything yet.
     *
     * receiveNotification() can update more than one order under the same reference (a cart
     * split across packages/warehouses/carriers loops here — see IpnNotification/WebhookNotification
     * ::receiveNotification()), and each iteration may reach a different branch of updateOrder().
     * Recording instead of sending immediately lets every order in that loop finish processing;
     * the caller (the notification front controller) decides when all processing for this request
     * is done and sends exactly one response via getNotificationResponse() (PPSP-1573).
     *
     * Within that loop, a retryable failure (code >= 400) always wins over a later success
     * (code < 400): once one order in the loop fails, a different order finishing successfully
     * afterwards must not mask it with a 2xx — Mercado Pago would stop retrying while the failed
     * order stays silently out of sync. Among calls on the same side of that line (two successes,
     * or two failures), the last call still wins.
     *
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    public function setNotificationResponse($message, $code)
    {
        if ($this->responseCode !== null && $this->responseCode >= 400 && $code < 400) {
            return;
        }

        $this->responseMessage = $message;
        $this->responseCode = $code;
    }

    /**
     * Sends the response for this notification request and terminates immediately. Must be
     * called exactly once, by the front controller, after all processing (including any
     * multi-order loop) has finished — never from within the per-order processing methods
     * themselves; use setNotificationResponse() there instead.
     *
     * Sets the status code before echoing the body, then terminates the request immediately.
     * Both matter and are independently verified (PPSP-1573):
     *
     * - `http_response_code()` must run before any output is produced. With `output_buffering`
     *   Off, the first `echo` flushes headers immediately using whatever status is set at that
     *   instant; calling `http_response_code()` after the `echo` silently loses the intended code
     *   and the client always sees a default 200, regardless of $code.
     * - `exit` must run after the response is sent. Without it, PrestaShop's front controller
     *   lifecycle continues past initContent() into display(), which tries to render a Smarty
     *   template that was never set for this controller and fatals. Whether that turns into a
     *   visible HTTP 500 depends on the host's output buffering (masked when it's already flushed
     *   the response), but the fatal fires either way and can start surfacing after any hosting/PHP
     *   change with no code change on either side — `exit` removes the dependency entirely.
     *
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    public static function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = self::buildNotificationResponse($message, $code);

        http_response_code($response['status']);
        echo $response['body'];

        exit;
    }

    /**
     * Builds the HTTP status/body pair for a notification response.
     *
     * Keeping the payload construction separate from transport makes the response
     * contract verifiable without executing the terminal exit used in production.
     *
     * @param string $message
     * @param int $code
     *
     * @return array<string, int|string>
     */
    public static function buildNotificationResponse($message, $code)
    {
        return [
            'status' => $code,
            'body' => json_encode([
                'code' => $code,
                'message' => $message,
                'version' => MP_VERSION,
            ]),
        ];
    }

    /**
     * Get order total
     *
     * @return float
     */
    public function getTotal($cart, $checkout)
    {
        // Custom/Ticket/Pix/PSE preferences have already applied their CartRule.
        // Only Wallet Button still needs the pre-CartRule discount mirror here.
        $amount = $checkout === 'wallet_button'
            ? $this->mpuseful->getCorrectedTotal($cart, $checkout)['amount']
            : (float) $cart->getOrderTotal(true);
        $localization = Configuration::get('MERCADOPAGO_SITE_ID');

        if ($localization == 'MCO' || $localization == 'MLC') {
            return Tools::ps_round($amount, 0);
        }

        return Tools::ps_round($amount, 2);
    }

    /**
     * Generate notification logs
     *
     * @return void
     */
    public function generateLogs()
    {
        $logs = [
            'transaction_id' => $this->transaction_id,
            'cart_total' => $this->total,
            'order_id' => $this->order_id,
            'payment_status' => $this->status,
            'approved_order_state' => $this->approved,
            'pending_order_state' => $this->pending,
            'order_state' => $this->order_state,
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate('Order id ' . $this->order_id . ' notification logs: ' . $encodedLogs);
    }

    /**
     * Verify value
     *
     * @param string $key
     *
     * @return string|null
     */
    public function verifyValue($key)
    {
        if (!isset($this->payments_data[$key])) {
            return null;
        }

        if (is_array($this->payments_data[$key])) {
            return implode(',', $this->payments_data[$key]);
        }

        return $this->payments_data[$key];
    }
}
