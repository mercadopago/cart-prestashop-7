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

/** Separates the audit of attempts from confirmed nominal receipts. No I/O. */
class PaymentReconciliationSnapshot
{
    public static function fromPayments(array $payments, int $precision): array
    {
        $attempts = [];
        $currency = null;
        foreach ($payments as $payment) {
            foreach (['id', 'status', 'currency_id', 'transaction_amount', 'payment_type_id', 'payment_method_id'] as $field) {
                if (!isset($payment[$field])) {
                    throw new InvalidArgumentException('Incomplete payment snapshot');
                }
            }
            $id = (string) $payment['id'];
            if (!preg_match('/^[1-9][0-9]{0,19}$/D', $id)
                || !preg_match('/^[A-Z]{3}$/D', $payment['currency_id'])
                || !preg_match('/^[a-z0-9_]{1,100}$/D', $payment['payment_type_id'])
                || !preg_match('/^[a-z0-9_]{1,100}$/D', $payment['payment_method_id'])
                || !in_array($payment['status'], ['approved', 'pending', 'in_process', 'authorized', 'rejected', 'cancelled', 'refunded', 'charged_back', 'in_mediation'], true)
            ) {
                throw new InvalidArgumentException('Invalid payment snapshot');
            }
            if ($currency !== null && $currency !== $payment['currency_id']) {
                throw new InvalidArgumentException('Mixed payment currencies');
            }
            $currency = $payment['currency_id'];
            $attempt = [
                'id' => $id,
                'status' => $payment['status'],
                'type' => (string) $payment['payment_type_id'],
                'method' => (string) $payment['payment_method_id'],
                'currency' => $currency,
                'nominal_amount' => self::decimal(self::units($payment['transaction_amount'], $precision), $precision),
                'payer_paid_amount' => isset($payment['transaction_details']['total_paid_amount'])
                    ? self::decimal(self::units($payment['transaction_details']['total_paid_amount'], $precision), $precision) : null,
                'coupon_amount' => self::decimal(self::units($payment['coupon_amount'] ?? 0, $precision), $precision),
                'updated_at' => self::eventTime($payment['date_last_updated'] ?? $payment['date_created'] ?? null),
                'approved_at' => self::eventTime($payment['date_approved'] ?? null),
                'merchant_order_id' => isset($payment['order']['id']) ? (string) $payment['order']['id'] : null,
            ];
            if (isset($attempts[$id])) {
                foreach (['nominal_amount', 'currency', 'type', 'method', 'merchant_order_id'] as $identity) {
                    if ($attempts[$id][$identity] !== $attempt[$identity]) {
                        throw new InvalidArgumentException('Conflicting payment identity');
                    }
                }
                if ($attempt['updated_at'] === $attempts[$id]['updated_at'] && $attempt !== $attempts[$id]) {
                    throw new InvalidArgumentException('Conflicting payment snapshot');
                }
                if ($attempt['updated_at'] < $attempts[$id]['updated_at']) {
                    continue;
                }
            }
            $attempts[$id] = $attempt;
        }
        uasort($attempts, function ($a, $b) {
            return ($a['updated_at'] <=> $b['updated_at']) ?: self::compareIds($a['id'], $b['id']);
        });
        $receipts = [];
        $approved = 0;
        $pending = 0;
        $primary = null;
        foreach ($attempts as $attempt) {
            if ($attempt['status'] === 'approved') {
                $receipts[$attempt['id']] = ['id' => $attempt['id'], 'amount' => $attempt['nominal_amount'], 'currency' => $attempt['currency']];
                $approved += self::units($attempt['nominal_amount'], $precision);
                if ($primary === null
                    || (($attempt['approved_at'] <=> $attempts[$primary]['approved_at']) ?: self::compareIds($attempt['id'], $primary)) < 0
                ) {
                    $primary = $attempt['id'];
                }
            } elseif (in_array($attempt['status'], ['pending', 'in_process', 'authorized'], true)) {
                $pending += self::units($attempt['nominal_amount'], $precision);
            }
        }
        $last = end($attempts);
        $primary = $primary ?? ($last ? $last['id'] : null);
        if ($primary !== null) {
            $attempts = [$primary => $attempts[$primary]] + $attempts;
        }

        return [
            'attempts' => $attempts, 'receipts' => $receipts, 'primary_payment_id' => $primary,
            'effective_status' => $approved > 0 ? 'approved' : ($pending > 0 ? 'in_process' : ($last ? $last['status'] : null)),
            'approved_amount' => self::decimal($approved, $precision),
            'pending_amount' => self::decimal($pending, $precision),
            'precision' => $precision,
        ];
    }

    public static function units($amount, int $precision): int
    {
        if (!in_array($precision, [0, 2], true) || !is_numeric($amount)
            || !is_finite((float) $amount) || $amount < 0 || $amount > 1000000000000
        ) {
            throw new InvalidArgumentException('Invalid nominal payment amount');
        }

        return (int) round((float) $amount * (10 ** $precision), 0, PHP_ROUND_HALF_UP);
    }

    public static function decimal(int $units, int $precision): string
    {
        return number_format($units / (10 ** $precision), $precision, '.', '');
    }

    private static function eventTime($value): int
    {
        if ($value === null) {
            return 0;
        }
        $time = strtotime($value);
        if ($time === false) {
            throw new InvalidArgumentException('Invalid payment event time');
        }

        return $time;
    }

    private static function compareIds(string $a, string $b): int
    {
        return (strlen($a) <=> strlen($b)) ?: strcmp($a, $b);
    }
}
