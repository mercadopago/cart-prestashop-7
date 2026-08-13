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

class IpnNotification extends AbstractNotification
{
    public $merchant_order;
    public $preference;
    public $isWalletButton;

    public function __construct($transaction_id, $merchant_order)
    {
        parent::__construct($transaction_id);

        $this->merchant_order = $merchant_order;
        $this->checkout = $this->getCheckoutType();
        $this->isWalletButton = $this->checkout === 'wallet_button';
        $this->preference = $this->getCheckoutPreference();
        $this->mp_transaction_amount = isset($merchant_order['total_amount']) ? $merchant_order['total_amount'] : 0;
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
        $orderId = $this->getOrderId($cart);

        if ($orderId != 0) {
            $payments = $this->merchant_order['payments'];

            $this->verifyPayments($payments);
            $this->validateOrderState();

            $baseOrder = new Order($orderId);
            $orders = Order::getByReference($baseOrder->reference);

            foreach ($orders as $order) {
                $this->order_id = $order->id;
                $this->updateOrderTransaction($order);
                $this->updateOrder($cart);
            }
        } else {
            $this->createStandardOrder($cart);
        }
    }

    /**
     * Create order for standard payments without notification
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function createStandardOrder($cart)
    {
        if (!is_array($this->merchant_order)) {
            MPLog::generate(
                'Standard order not created: merchant order was not retrieved or authorized by Mercado Pago',
                'error'
            );

            return;
        }

        if ($this->isWalletButton) {
            $this->preference->setCartRule($cart, Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT'));
        }

        if (empty($this->transaction_id) && !empty($this->merchant_order['payments'])) {
            $payments = $this->merchant_order['payments'];
            if (isset($payments[0]['id'])) {
                $this->transaction_id = $payments[0]['id'];
            }
        }

        $this->getOrderId($cart);
        $this->total = $this->getTotal($cart, $this->checkout);

        // If the merchant order carries payments, verify them to resolve the real
        // status. Assuming a hardcoded 'pending' marks already-approved payments
        // as rejected on PrestaShop 8.2 (mercadopago/cart-prestashop-7#89).
        if (!empty($this->merchant_order['payments'])) {
            $this->verifyPayments($this->merchant_order['payments']);
        } else {
            $this->status = 'pending';
            $this->pending += $this->total;
        }

        $this->validateOrderState();

        if ($this->order_id == 0 && $this->amount >= $this->total && $this->status != 'rejected') {
            $this->createOrder($cart, true);
        }

        if ($this->isWalletButton) {
            $this->preference->disableCartRule();
        }
    }

    /**
     * Get Checkout Preference
     *
     * @return mixed
     */
    public function getCheckoutPreference()
    {
        if ($this->isWalletButton) {
            return new WalletButtonPreference();
        }

        return new StandardPreference();
    }

    /**
     * Get Preference
     *
     * @return mixed
     */
    public function getCheckoutType()
    {
        $preference = $this->mercadopago->getPreference($this->merchant_order['preference_id']);

        $checkout = 'pro';
        $checkoutType = isset($preference->metadata->checkout_type) ? $preference->metadata->checkout_type : false;

        if ($checkoutType && $checkoutType === 'wallet_button') {
            $checkout = 'wallet_button';
        }

        return $checkout;
    }

    /**
     * Verify if order exists then get order_id
     *
     * @param mixed $cart
     *
     * @return int
     */
    public function getOrderId($cart)
    {
        $orderId = Order::getIdByCartId($cart->id);
        $this->order_id = $orderId;

        return $orderId;
    }

    /**
     * Verify merchant order payments
     *
     * @param mixed $payments
     *
     * @return void
     */
    public function verifyPayments($payments)
    {
        $this->payments_data['payments_id'] = [];
        $this->payments_data['payments_type'] = [];
        $this->payments_data['payments_method'] = [];
        $this->payments_data['payments_status'] = [];
        $this->payments_data['payments_amount'] = [];

        if (!empty($payments) && isset($payments[0]['id'])) {
            $this->transaction_id = $payments[0]['id'];
        }

        foreach ($payments as $payment) {
            $payment_info = $this->mercadopago->getPaymentStandard($payment['id']);
            $this->status = $payment_info['status'];

            $this->payments_data['payments_id'][] = $payment_info['id'];
            $this->payments_data['payments_type'][] = $payment_info['payment_type_id'];
            $this->payments_data['payments_method'][] = $payment_info['payment_method_id'];
            $this->payments_data['payments_amount'][] = $payment_info['transaction_amount'];
            $this->payments_data['payments_status'][] = $this->status;

            if ($this->status == 'approved') {
                $coupon_amount = isset($payment_info['coupon_amount']) ? $payment_info['coupon_amount'] : 0.00;
                $this->approved += $payment_info['transaction_details']['total_paid_amount'] + $coupon_amount;
            } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
                $this->pending += $payment_info['transaction_amount'];
            }
        }

        MPLog::generate(sprintf(
            'Processed %d payments for order. Transaction IDs: %s',
            count($this->payments_data['payments_id']),
            implode(', ', $this->payments_data['payments_id'])
        ));
    }
}
