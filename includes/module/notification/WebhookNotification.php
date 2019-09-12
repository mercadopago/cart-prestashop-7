<?php

/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

require_once MP_ROOT_URL . '/includes/module/notification/AbstractNotification.php';

class WebhookNotification extends AbstractNotification
{
    public $payment;

    public function __construct($transaction_id, $customer_secure_key)
    {
        parent::__construct($transaction_id, $customer_secure_key);
        $this->payment = $this->mercadopago->getPaymentStandard($transaction_id);
    }

    /**
     * Receive and treat the notification
     *
     * @param mixed $cart
     * @return void
     */
    public function receiveNotification($cart)
    {
        $this->order_id = Order::getOrderByCartId($cart->id);
        $this->total = (float) $cart->getOrderTotal();
        $this->verifyCustomPayment();
        $this->validateOrderState();

        return $this->updateOrder($cart);
    }

    /**
     * Create ordem for custom payments wihtout notification
     *
     * @param mixed $cart
     * @return void
     */
    public function createCustomOrder($cart)
    {
        $this->total = (float) $cart->getOrderTotal();
        $this->verifyCustomPayment();
        $this->validateOrderState();
        
        if ($this->order_id == 0 && $this->amount >= $this->total && $this->status != 'rejected') {
            return $this->createOrder($cart, true);
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
        $this->pending += $this->payment['transaction_amount'];

        $this->payments_data['payments_id'] = $this->payment['id'];
        $this->payments_data['payments_type'] = $this->payment['payment_type_id'];
        $this->payments_data['payments_method'] = $this->payment['payment_method_id'];
        $this->payments_data['payments_amount'] = $this->payment['transaction_amount'];
        $this->payments_data['payments_status'] = $this->status;
    }
}