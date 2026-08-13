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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/AbstractNotification.php';

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
     * @param mixed $cart
     *
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
        } else {
            MPLog::generate(sprintf(
                'Custom webhook received for cart %d before its order exists (transaction %s); the order is '
                . 'created on the customer return flow, so this status callback is not applied here',
                (int) $cart->id,
                $this->transaction_id
            ), 'warning');
        }
    }

    /**
     * Create order for custom payments without notification
     *
     * @param mixed $cart
     *
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
            // Cash payments (e.g. OXXO) may not expose transaction_details.total_paid_amount
            // when confirmed; fall back to transaction_amount so the status still updates
            // (mercadopago/cart-prestashop-7#88).
            if (isset($this->payment['transaction_details']['total_paid_amount'])) {
                $this->approved += $this->payment['transaction_details']['total_paid_amount'];
            } else {
                $this->approved += $this->payment['transaction_amount'];
            }
        } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
            $this->pending += $this->payment['transaction_amount'];
        }
    }
}
