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
 * If a copy of the license was not supplied, consult LICENSE.md
 * or the license URL below.
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/PaymentReconciliationSnapshot.php';

/** Conservative financial closure. No audit, URL or sibling status is proof. */
class PaymentClosureProof
{
    public static function isClosed(array $payment, bool $wasApproved, int $precision): bool
    {
        if (in_array($payment['status'] ?? null, ['rejected', 'cancelled'], true)) {
            return !$wasApproved && empty($payment['date_approved']);
        }
        if (($payment['status'] ?? null) !== 'refunded'
            || !isset($payment['id'], $payment['transaction_amount'], $payment['transaction_amount_refunded'], $payment['refunds'])
            || !is_array($payment['refunds']) || !$payment['refunds']
        ) {
            return false;
        }
        try {
            $nominal = PaymentReconciliationSnapshot::units($payment['transaction_amount'], $precision);
            if ($nominal <= 0 || PaymentReconciliationSnapshot::units($payment['transaction_amount_refunded'], $precision) !== $nominal) {
                return false;
            }
            $total = 0;
            $seen = [];
            foreach ($payment['refunds'] as $refund) {
                if (!is_array($refund) || !isset($refund['id'], $refund['payment_id'], $refund['amount'])
                    || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $refund['id'])
                    || (string) $refund['payment_id'] !== (string) $payment['id']
                    || isset($seen[(string) $refund['id']]) || ($refund['status'] ?? null) !== 'approved'
                ) {
                    return false;
                }
                $seen[(string) $refund['id']] = true;
                $total += PaymentReconciliationSnapshot::units($refund['amount'], $precision);
            }

            return $total === $nominal;
        } catch (Throwable $error) {
            return false;
        }
    }
}
