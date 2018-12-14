<?php
/**
 * 2007-2015 PrestaShop.
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
 *  @author    henriqueleite
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

include_once dirname(__FILE__).'/../../includes/NotificationIPN.php';
include_once dirname(__FILE__).'/../../includes/MPApi.php';

class MercadoPagoNotificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        //It is necessary to give time for the function validateOrder create an order.
        // https://goo.gl/oC44t8
        sleep(2);
      
        $cart = new Cart(Tools::getValue('cart_id'));
        $total = (float)($cart->getOrderTotal(true, Cart::BOTH));
        $notification = new NotificationIPN();
        $checkout = Tools::getValue('checkout');
        
        $topic = null;
        $id = null;
        
        $mercadopago = $this->module;
        
        if (empty(Tools::getValue('topic'))) {
            $topic = Tools::getValue('type');
            $id = Tools::getValue('data_id');
        } else {
            $topic = Tools::getValue('topic');
            $id = Tools::getValue('id');
        }
        
        UtilMercadoPago::log(
            "Notification received - ",
            "cart id ".Tools::getValue('cart_id')." - total is = " . $total
        );
        
        UtilMercadoPago::log(
            "Notification received - ",
            "cart_id = " . Tools::getValue('cart_id')
        );
        
        UtilMercadoPago::log(
            "Notification received - ",
            "cart id ".Tools::getValue('cart_id')." - id $topic = " . $id
        );
        
        UtilMercadoPago::log(
            "Notification received - ",
            "cart id ".Tools::getValue('cart_id')." - type checkout = " . $checkout
        );
        
        UtilMercadoPago::log(
            "Notification received - ",
            "cart id ".Tools::getValue('cart_id')." - topic = " . $topic
        );

        UtilMercadoPago::log(
            "Notification received - ",
            "cart id ".Tools::getValue('cart_id')." - the order exist ? = " . $cart->orderExists()
        );
        if ($topic == 'merchant_order') {
            $mercadopago_sdk = MPApi::getInstanceMP();
            $result = $mercadopago_sdk->getMerchantOrder($id);
            if ($result['response']['status'] == "opened") {
                var_dump(http_response_code(200));
                die();
            }
        }
        if ($checkout == 'standard' && $topic == 'merchant_order') {
            $id_order = Order::getOrderByCartId(Tools::getValue('cart_id'));
            if (!$cart->orderExists()) {
                UtilMercadoPago::log(
                    "Notification received - ",
                    "cart id ".Tools::getValue('cart_id')." - order doesn't exist " . $cart->id .
                    " and return 500 to API, because is necessary to create before."
                );
                var_dump(http_response_code(500));
                $displayName = $mercadopago->l('Mercado Pago Redirect');
                $payment_status = Configuration::get(UtilMercadoPago::$statusMercadoPagoPresta['started']);
              
                try {
                    $mercadopago->validateOrder(
                        $cart->id,
                        $payment_status,
                        $total,
                        $displayName,
                        null,
                        array(),
                        (int)$cart->id_currency,
                        false,
                        $cart->secure_key
                    );
                    $id_order = Order::getOrderByCartId(Tools::getValue('cart_id'));
                    UtilMercadoPago::log(
                        "Notification received - ",
                        "cart id ".Tools::getValue('cart_id')." - The order was created " .
                        $id_order . " for the cart ". $cart->id
                    );
                } catch (Exception $e) {
                    UtilMercadoPago::log(
                        "cart id ".Tools::getValue('cart_id').
                        " - There is a problem with notification id = " . $cart->id,
                        $e->getMessage()
                    );
                }
            } else {
                $notification->listenIPN(
                    $checkout,
                    $topic,
                    $id
                );
                var_dump(http_response_code(201));
                UtilMercadoPago::log(
                    "Notification received - ",
                    "cart id ".Tools::getValue('cart_id').
                    " - The notification return 201, the cart was updated = " .$cart->id
                );
            }
        } else {
            var_dump(http_response_code(500));
        }
        die();
    }
}
