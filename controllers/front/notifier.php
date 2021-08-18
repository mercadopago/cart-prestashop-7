<?php

/**
 * 2007-2021 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * <ModuleClassName> => Notifier
 * <FileName> => notifier.php
 * 
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

class MercadoPagoNotifierModuleFrontController extends ModuleFrontController
{
    public $mercadopago;
    public $request;
    public $cryptography;

    public function __construct()
    {
        parent::__construct();
        $this->mercadopago  = MPApi::getInstance();
        $this->request      = Request::getInstance();
        $this->cryptography = Cryptography::getInstance();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        MPLog::generate('--------Core Notification--------');

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
            case 'GET':
                MPLog::generate('Request GET from Core Notifier');
                return $this->getOrder();

            case 'POST':
                MPLog::generate('Request POST from Core Notifier');
                return $this->updateOrder();

            default:
                return $this->request->response('Method not allowed', 405);
            }
        }
    }

    /**
     * Get Order
     * 
     * @return void
     */
    public function getOrder()
    {
        try {
                $paymentId = Tools::getValue('payment_id');
                $externalReference = Tools::getValue('external_reference');
                $timestamp = Tools::getValue('timestamp');

                if (empty($paymentId)
                    || empty($externalReference)
                    || empty($timestamp)) 
                {
                    return  $this->request->response('Some parameters are empty', 400);
                }

                $data = array();
                $data['payment_id'] = $paymentId;
                $data['external_reference'] = $externalReference;
                $data['timestamp'] = $timestamp;

                $secret = $this->mercadopago->getaccessToken();

                if (!$this->_isAuthenticated($data, $secret)) 
                {
                    return  $this->request->response('Unauthorized', 401);
                }

                $cart = new Cart($externalReference);
                $orderId = Order::getOrderByCartId($cart->id);

                if ($orderId == 0) 
                {
                    return  $this->request->response('Order not found', 404);
                }

                $response = $this->_buildOrderResponse($orderId, 
                                                        $externalReference, 
                                                        $cart);

                return $this->request->response($response, 200);
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
            return $this->request->response('Server Internal Error', 500);
        }
    }

    /**
     * Update Order
     * 
     * @return void
     */
    public function updateOrder()
    {
        try {
                $data = $this->request->getJsonBody();

                if (!$this->_validateUpdateOrderParams($data)) 
                {
                    return $this->request->response('Some parameters are empty', 400);
                }

                $secret = $this->mercadopago->getaccessToken();
                
                if (!$this->_isAuthenticated($data, $secret)) 
                {
                    return $this->request->response('Unauthorized', 401);
                }

                $cartId = $data['external_reference'];
                $cart = new Cart($cartId);
                $customer = new Customer((int) $cart->id_customer);
                $customer_secure_key = $customer->secure_key;
                $secureKey = Tools::getValue('customer');

                if ($customer_secure_key != $secureKey) {
                    MPLog::generate('Missing secure-key on request');
                    return $this->request->erroResponse();                    
                }
                                
                $transactionId = $data['payment_id'];
                $status = $data['status'];
                $mpOrder = new MP_Order($transactionId, $status);

                $payment                      = array();
                $payment['id']                = $data['payment_id'];
                $payment['payment_type_id']   = $data['payment_type_id'];
                $payment['payment_method_id'] = $data['payment_method_id'];
                $payment['total']             = $data['total'];  

                return $mpOrder->receiveNotification($cart, $payment);
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
            return $this->request->response('Server Internal Error', 500);
        }   
        
        
    }

    /**
     * Validate Update Order Params
     * 
     * @param mixed $order 
     * 
     * @return bool
     */
    private function _validateUpdateOrderParams($order)
    {
        return isset($order['status'])
            && isset($order['timestamp'])
            && isset($order['payment_id'])
            && isset($order['external_reference'])
            && isset($order['checkout'])
            && isset($order['checkout_type'])
            && isset($order['payment_method_id'])
            && isset($order['payment_type_id'])
            && isset($order['total']);
    }    

    /**
     * Get order total
     * 
     * @param mixed $cart Cart
     * 
     * @return mixed
     */
    public function getTotal($cart)
    {
        $total = (float) $cart->getOrderTotal();
        $localization = Configuration::get('MERCADOPAGO_SITE_ID');

        if ($localization == 'MCO' || $localization == 'MLC') {
            return Tools::ps_round($total, 2);
        }

        return $total;
    }

    /**
     * Token authentication 
     * 
     * @param mixed  $data   Params from Request
     * @param String $secret Client Access Token
     * 
     * @return boolean
     */
    private function _isAuthenticated($data, $secret)
    {

        if (is_null($secret) || empty($secret)) {
            MPLog::generate('Credentials not found');
            return $this->request->response('Unauthorized', 400);
        }

        $auth  = $this->cryptography->encrypt($data, $secret);
        $token = $this->request->getBearerToken();

        if (!$token) {
            return $this->request->response('Unauthorized', 401);
        }
        return ($auth === $token);
    }

    /**
     * Build order response
     * 
     * @param integer $orderId           OrderID
     * @param String  $secret            Client access token
     * @param String  $externalReference PaymentID
     * @param mixed   $cart              Cart
     * 
     * @return array
     */
    private function _buildOrderResponse($orderId, $externalReference, $cart)
    {
        $order = new Order($orderId);

        $response                       = array();
        $response['order_id']           = $orderId."";
        $response['external_reference'] = $externalReference;
        $response['status']             = $this->getOrderState($order->getCurrentState());
        $response['created_at']         = strtotime($order->date_add);
        $response['total']              = $this->getTotal($cart);
        $response['timestamp']          = time();

        return $response;
    }

    /**
     * @param  $state
     * @return mixed
     */
    public function getOrderState($state)
    {
        $payment_states = array();
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_0')] = 'in_process';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_1')] = 'approved';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_2')] = 'cancelled';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_3')] = 'rejected';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_4')] = 'refunded';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_5')] = 'charged_back';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_6')] = 'in_mediation';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_7')] = 'pending';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_8')] = 'authorized';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_9')] = 'possible_fraud';

        return $payment_states[$state];
    }
}
