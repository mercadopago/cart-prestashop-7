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

require_once MP_ROOT_URL . '/includes/module/notification/AbstractNotification.php';

class WebhookNotification extends AbstractNotification
{
    public $payment;

    public function __construct($transaction_id, $payment)
    {
        parent::__construct($transaction_id);

        $this->payment = $payment;
        $this->checkout = $payment['metadata']['checkout_type'];
        $this->mp_transaction_amount = $payment['transaction_amount'];
    }

    /**
     * Receive and treat the notification
     *
     * @param  mixed $cart
     * @return void
     */
    public function receiveNotification($cart)
    {
        $this->verifyWebhook($cart);

        $this->total = $this->getTotal($cart, $this->checkout);
        $orderId = Order::getIdByCartId($cart->id);

        if ($orderId != 0) {
            $this->verifyCustomPayment();
            $this->validateOrderState();

            $baseOrder = new Order($orderId);
            $orders = Order::getByReference($baseOrder->reference);

            foreach ($orders as $order) {
                $this->order_id = $order->id;
                $this->updateOrderTransaction($order);
                $this->updateOrder($cart);
            }
        }
    }

    /**
     * Create order for custom payments without notification
     *
     * @param  mixed $cart
     * @return void
     */
    public function createCustomOrder($cart)
    {
        $this->total = $this->getTotal($cart, $this->checkout);
        $this->verifyCustomPayment();
        $this->validateOrderState();

        if ($this->order_id == 0 && $this->amount >= $this->total && $this->status != 'rejected') {
            $this->createOrder($cart, true);
        }
    }

    /**
     * Verify custom payments
     *
     * @return void
     */
    public function verifyCustomPayment()
    {
        $this->status = $this->payment['status'];
        $this->payments_data['payments_id'] = $this->payment['id'];
        $this->payments_data['payments_type'] = $this->payment['payment_type_id'];
        $this->payments_data['payments_method'] = $this->payment['payment_method_id'];
        $this->payments_data['payments_amount'] = $this->payment['transaction_amount'];
        $this->payments_data['payments_status'] = $this->status;

        if ($this->status == 'approved') {
            $this->approved += $this->payment['transaction_details']['total_paid_amount'];
        } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
            $this->pending += $this->payment['transaction_amount'];
        }
    }
}
