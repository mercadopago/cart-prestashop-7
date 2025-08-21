<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
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
                'status' => isset($payments_data['payments_status'][$index]) ? $payments_data['payments_status'][$index] : ''
            ];
        }

        return $summary;
    }
}
