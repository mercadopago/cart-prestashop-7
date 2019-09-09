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

class IpnNotification extends AbstractPreference
{
    public $total;
    public $amount;
    public $status;
    public $order_id;
    public $order_state;
    public $payments_data;
    public $merchant_order_id;
    public $customer_secure_key;

    public function __construct($merchant_order_id, $customer_secure_key)
    {
        parent::__construct();
        $this->merchant_order_id = $merchant_order_id;
        $this->customer_secure_key = $customer_secure_key;
    }

    /**
     * Receive and trear the notification
     *
     * @param mixed $cart
     * @return void
     */
    public function receiveNotification($cart)
    {
        $this->amount = array();
        $this->amount['apro'] = 0;
        $this->amount['pend'] = 0;

        $merchant_order = $this->mercadopago->getMerchantOrder($this->merchant_order_id);
        $payments = $merchant_order['payments'];

        $this->total = (float) $cart->getOrderTotal();
        $this->order_id = Order::getOrderByCartId(Tools::getValue('cart_id'));

        $this->verifyWebhook($cart);
        $this->verifyPayments($payments);
        $this->validateOrderState();

        if ($this->order_id == 0 && $this->amount['total'] >= $this->total && $this->status != 'rejected') {
            $this->createOrder($cart);
        }else{
            $this->updateOrder($cart);
        }
    }
}
