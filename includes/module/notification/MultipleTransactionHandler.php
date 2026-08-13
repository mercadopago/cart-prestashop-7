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

class MultipleTransactionHandler
{
    /**
     * Process multiple transactions for an order
     *
     * @param Order $order
     * @param array $payments_data
     *
     * @return bool
     */
    public static function processMultipleTransactions($order, $payments_data)
    {
        try {
            if (empty($payments_data['payments_id']) || count($payments_data['payments_id']) <= 1) {
                return false;
            }

            $order_payments = $order->getOrderPaymentCollection();

            foreach ($order_payments as $payment) {
                $payment->delete();
            }

            foreach ($payments_data['payments_id'] as $index => $payment_id) {
                $new_payment = new OrderPayment();
                $new_payment->order_reference = $order->reference;
                $new_payment->id_currency = $order->id_currency;
                $new_payment->amount = $payments_data['payments_amount'][$index];
                $new_payment->payment_method = 'Mercado Pago';
                $new_payment->transaction_id = $payment_id;

                if (isset($payments_data['payments_type'][$index])) {
                    $new_payment->payment_method = 'Mercado Pago - ' . $payments_data['payments_type'][$index];
                }

                $new_payment->add();
            }

            MPLog::generate(sprintf(
                'Successfully created %d payment records for order %d',
                count($payments_data['payments_id']),
                $order->id
            ));

            return true;
        } catch (Exception $e) {
            MPLog::generate('Error processing multiple transactions: ' . $e->getMessage(), 'error');

            return false;
        }
    }

    /**
     * Check if there are multiple transactions
     *
     * @param array $payments_data
     *
     * @return bool
     */
    public static function hasMultipleTransactions($payments_data)
    {
        return !empty($payments_data['payments_id']) && count($payments_data['payments_id']) > 1;
    }

    /**
     * Get consolidated information about multiple transactions
     *
     * @param array $payments_data
     *
     * @return array
     */
    public static function getTransactionsSummary($payments_data)
    {
        if (empty($payments_data['payments_id'])) {
            return [];
        }

        $summary = [];
        foreach ($payments_data['payments_id'] as $index => $payment_id) {
            $summary[] = [
                'id' => $payment_id,
                'amount' => isset($payments_data['payments_amount'][$index]) ? $payments_data['payments_amount'][$index] : 0,
                'type' => isset($payments_data['payments_type'][$index]) ? $payments_data['payments_type'][$index] : '',
                'method' => isset($payments_data['payments_method'][$index]) ? $payments_data['payments_method'][$index] : '',
                'status' => isset($payments_data['payments_status'][$index]) ? $payments_data['payments_status'][$index] : '',
            ];
        }

        return $summary;
    }
}
