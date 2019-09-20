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

require_once MP_ROOT_URL . '/includes/module/preference/CustomPreference.php';
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';

class MercadoPagoCustomModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        $cart_id = $cart->id;
        $customer_secure_key = $cart->secure_key;

        $preference = new CustomPreference();
        $preference->verifyModuleParameters();

        $custom_info = Tools::getValue('mercadopago_custom');
        $payment = $preference->createPreference($cart, $custom_info);

        var_dump($payment);
        echo '<br>';

        if (is_array($payment) && array_key_exists('transaction_details', $payment)) {
            //payment created
            $transaction_details = $payment['transaction_details'];
            $preference->saveCreatePreferenceData($cart, '');
            MPLog::generate('Cart id ' . $cart->id . ' - Custom payment created successfully');

            //create order
            $transaction_id = $payment['id'];
            $notification = new WebhookNotification($transaction_id, $customer_secure_key);
            $notification = $notification->createCustomOrder($cart);

            //order confirmation redirect
            $old_cart = new Cart($cart_id);
            $order = Order::getOrderByCartId($old_cart->id);
            $order = new Order($order);

            $uri = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
            $uri .= '&id_cart=' . $order->id_cart;
            $uri .= '&key=' . $order->secure_key;
            $uri .= '&id_order=' . $order->id;
            $uri .= '&id_module=' . $this->module->id;
            $uri .= '&payment_id=' . $payment['id'];
            $uri .= '&payment_status=' . $payment['status'];
            $uri .= '&payment_ticket=' . urlencode($transaction_details['external_resource_url']);

            //redirect to order confirmation page
            // Tools::redirect($uri);
        }

        // return $preference->redirectError();
    }
}
