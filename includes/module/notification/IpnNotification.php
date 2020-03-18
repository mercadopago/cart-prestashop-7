<?php
/**
 * 2007-2020 PrestaShop
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
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/notification/AbstractNotification.php';

class IpnNotification extends AbstractNotification
{
    public function __construct($transaction_id, $customer_secure_key)
    {
        parent::__construct($transaction_id, $customer_secure_key);
    }

    /**
     * Receive and treat the notification
     *
     * @param mixed $cart
     * @return void
     */
    public function receiveNotification($cart)
    {
        $this->total = $this->getTotal($cart);
        $this->getOrderId($cart);
        $this->verifyWebhook($cart);

        if ($this->order_id != 0) {
            $merchant_order = $this->mercadopago->getMerchantOrder($this->transaction_id);
            $payments = $merchant_order['payments'];

            $this->verifyPayments($payments);
            $this->validateOrderState();
            $this->updateTransactionId();
            $this->updateOrder($cart);
        } else {
            $this->createStandardOrder($cart);
        }
    }

    /**
     * Create order for standard payments without notification
     *
     * @param mixed $cart
     * @return void
     */
    public function createStandardOrder($cart)
    {
        $this->getOrderId($cart);
        $this->total = $this->getTotal($cart);
        $this->status = 'pending';
        $this->pending += $this->total;
        $this->validateOrderState();

        if ($this->order_id == 0 && $this->amount >= $this->total && $this->status != 'rejected') {
            $this->createOrder($cart, true);
        }
    }

    /**
     * Verify if order exists then get order_id
     *
     * @param mixed $cart
     * @return void
     */
    public function getOrderId($cart)
    {
        $this->order_id = Order::getOrderByCartId($cart->id);
    }

    /**
     * Verify merchant order payments
     *
     * @param mixed $payments
     * @return void
     */
    public function verifyPayments($payments)
    {
        $this->payments_data['payments_id'] = array();
        $this->payments_data['payments_type'] = array();
        $this->payments_data['payments_method'] = array();
        $this->payments_data['payments_status'] = array();
        $this->payments_data['payments_amount'] = array();

        foreach ($payments as $payment) {
            $payment_info = $this->mercadopago->getPaymentStandard($payment['id']);
            $this->status = $payment_info['status'];

            $this->payments_data['payments_id'][] = $payment_info['id'];
            $this->payments_data['payments_type'][] = $payment_info['payment_type_id'];
            $this->payments_data['payments_method'][] = $payment_info['payment_method_id'];
            $this->payments_data['payments_amount'][] = $payment_info['transaction_amount'];
            $this->payments_data['payments_status'][] = $this->status;

            if ($this->status == 'approved') {
                $this->approved += $payment_info['transaction_amount'];
            } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
                $this->pending += $payment_info['transaction_amount'];
            }
        }
    }

    /**
     * Update merchant_order_id on transaction id
     *
     * @param mixed $cart
     * @return void
     */
    public function updateTransactionId()
    {
        $order = new Order($this->order_id);
        $payments = $order->getOrderPaymentCollection();
        $payments[0]->transaction_id = $this->transaction_id;
        $payments[0]->update();
    }
}
