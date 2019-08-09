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

class MercadoPagoNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        $mpuseful = MPUseful::getInstance();
        $mercadopago = MPApi::getInstance();
      
        $topic = Tools::getValue('topic');
        $checkout = Tools::getValue('checkout');
        $secure_key = Tools::getValue('customer');
        $merchant_order_id = Tools::getValue('id');
      
        $cart = new Cart(Tools::getValue('cart_id'));
        $cart_id = $cart->id;
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;
        
        //received webhook
        $mp_transaction = new MPTransaction();
        $mp_transaction->where('cart_id', '=', $cart_id)->update([
            "received_webhook" => true
        ]);
        MPLog::generate('Notification received on cart id '.$cart_id);
        
        if ($checkout == 'standard' && $topic == 'merchant_order' && $customer_secure_key == $secure_key) {
            $status = "";
            $amount = 0;
            $amount_apro = 0;
            $amount_pend = 0;
            $merchant_order = $mercadopago->getMerchantOrder($merchant_order_id);
            $payments = $merchant_order['payments'];
            
            $total = (float) $cart->getOrderTotal();
            $order_id = Order::getOrderByCartId(Tools::getValue('cart_id'));
            
            $payments_id = array();
            $payments_type = array();
            $payments_method = array();
            $payments_status = array();
            $payments_amount = array();
            
            foreach ($payments as $payment) {
                $payment_info = $mercadopago->getPaymentStandard($payment['id']);
                $payment_info = $payment_info['response'];
                $status = $payment_info['status'];
                
                $payments_id[] = $payment_info['id'];
                $payments_type[] = $payment_info['payment_type_id'];
                $payments_method[] = $payment_info['payment_method_id'];
                $payments_amount[] = $payment_info['transaction_amount'];
                $payments_status[] = $status;
                
                if ($status == 'approved') {
                    $amount_apro += $payment_info['transaction_amount'];
                } elseif ($status == 'in_process' || $status == 'pending' || $status == 'authorized') {
                    $amount_pend += $payment_info['transaction_amount'];
                }
            }
            
            //validate order state
            if ($amount_apro >= $total) {
                $amount = $amount_apro;
                $order_state = $mpuseful->getNotificationPaymentState('approved');
            } elseif ($amount_pend >= $total) {
                $amount = $amount_pend;
                $order_state = $mpuseful->getNotificationPaymentState('in_process');
            } else {
                $order_state = $mpuseful->getNotificationPaymentState($status);
            }
            
            //create order
            if ($order_id == 0 && $amount >= $total && $status != 'rejected') {
                try {
                    $this->module->validateOrder(
                        $cart_id,
                        $order_state,
                        $total,
                        "Mercado Pago",
                        null,
                        array(),
                        (int) $cart->id_currency,
                        false,
                        $customer_secure_key
                    );
                    
                    $order_id = Order::getOrderByCartId($cart_id);
                    $order = new Order($order_id);
                    
                    $payments = $order->getOrderPaymentCollection();
                    $payments[0]->transaction_id = $merchant_order_id;
                    $payments[0]->update();
                    
                    //update data in mercadopago table
                    $mp_transaction->where('cart_id', '=', $cart_id)->update([
                        "order_id" => $order_id,
                        "payment_id" => implode(',', $payments_id),
                        "payment_type" => implode(',', $payments_type),
                        "payment_method" => implode(',', $payments_method),
                        "payment_status" => implode(',', $payments_status),
                        "payment_amount" => implode(',', $payments_amount),
                        "notification_url" => $_SERVER['REQUEST_URI'],
                        "merchant_order_id" => $merchant_order_id,
                        "received_webhook" => true,
                    ]);
                    
                    //return response to IPN
                    MPLog::generate('Order created successfully on cart id '.$cart_id);
                    $this->getNotificationResponse("The order has been created", 201);
                } catch (Exception $e) {
                    MPLog::generate(
                        'The order has not been created on cart id '.$cart_id.' - '.$e->getMessage(),
                        'error'
                    );
                    $this->getNotificationResponse("The order has not been created", 422);
                }
            } else {
                $order_id = Order::getOrderByCartId(Tools::getValue('cart_id'));
                $order = new Order($order_id);
                $actual_status = (int) $order->getCurrentState();
                
                if ($order_state != $actual_status) {
                    try {
                        //update data in mercadopago table
                        $mp_transaction->where('cart_id', '=', $cart_id)->update([
                            "payment_status" => implode(',', $payments_status)
                        ]);

                        //update order status
                        $order->setCurrentState($order_state);

                        //return response to IPN
                        MPLog::generate('Updated order '.$order_id.' for the status of '.$order_state);
                        $this->getNotificationResponse("The order has been updated", 201);
                    } catch (Exception $e) {
                        MPLog::generate(
                            'The order has not been updated on cart id '.$cart_id.' - '.$e->getMessage(),
                            'error'
                        );
                        $this->getNotificationResponse("The order has not been updated", 422);
                    }
                } else {
                    MPLog::generate('The order status is the same', 'warning');
                    $this->getNotificationResponse("The order status is the same", 422);
                }
            }
        } else {
            MPLog::generate('The notification does not have the necessary parameters to create an order', 'error');
            $this->getNotificationResponse("The notification does not have the necessary parameters", 422);
        }
    }
    
    /**
     * Get responses to send for notification
     *
     * @param [string] $message
     * @param [integer] $code
     * @return void
     */
    public function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "message" => $message
        );
        
        echo json_encode($response);
        return var_dump(http_response_code($code));
    }
}
