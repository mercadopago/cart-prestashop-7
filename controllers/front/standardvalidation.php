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

class MercadoPagoStandardValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $mercadopago = MPApi::getInstance();
        $payments_id = Tools::getValue('collection_id');

        //failure payments
        if (Tools::getValue('typeReturn') == 'failure') {
            $this->redirectError();
        }

        //aproved and pending payments
        if (isset($payments_id) && $payments_id != 'null') {
            //treatment for payments data
            $collection_ids = explode(',', $payments_id);
            $payments_id = array();
            $payments_type = array();
            $payments_method = array();
            $payments_status = array();
            $payments_amount = array();

            foreach ($collection_ids as $collection_id) {
                $payment_info = $mercadopago->getPaymentStandard($collection_id);
                if ($payment_info == false) {
                    continue;
                }

                $payments_id[] = $payment_info['id'];
                $payments_type[] = $payment_info['payment_type_id'];
                $payments_method[] = $payment_info['payment_method_id'];
                $payments_status[] = $payment_info['status'];
                $payments_amount[] = $payment_info['transaction_amount'];
                $cart_id = $payment_info['external_reference'];
            }

            //order confirmation redirect
            $cart = new Cart($cart_id);
            $total = $cart->getOrderTotal();
            $order_id = Order::getOrderByCartId($cart->id);
            $order = new Order($order_id);

            $uri = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
            $uri .= '&id_cart=' . $order->id_cart;
            $uri .= '&key=' . $order->secure_key;
            $uri .= '&id_order=' . $order->id;
            $uri .= '&id_module=' . $this->module->id;
            $uri .= '&typeReturn=' . Tools::getValue('typeReturn');
            $uri .= '&payment_id=' . implode(',', $payments_id);
            $uri .= '&payment_type=' . implode(',', $payments_type);
            $uri .= '&payment_method=' . implode(',', $payments_method);
            $uri .= '&payment_status=' . implode(',', $payments_status);
            $uri .= '&payment_amount=' . implode(',', $payments_amount);
            $uri .= '&amount=' . $total;

            //redirect to order confirmation page
            Tools::redirect($uri);
        }
    }

    protected function redirectError()
    {
        MPLog::generate('The callback failed', 'error');
        Tools::redirect('index.php?controller=order&step=1&step=3&typeReturn=failure');
    }
}
