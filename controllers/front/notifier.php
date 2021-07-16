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


require_once MP_ROOT_URL . '/includes/helpers/cryptography/cryptography.php';
require_once MP_ROOT_URL . '/includes/helpers/request/request.php';

class MercadoPagoNotifierModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
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
            // @codingStandardsIgnoreLine
            $method = $_SERVER['REQUEST_METHOD'];

            switch ($method) {
                case 'GET':
                    MPLog::generate('Request GET from Core Notifier');
                    $this->getOrder();
                    break;

                case 'POST':
                    MPLog::generate('Request POST from Core Notifier');
                    $this->updateOrder();
                    break;

                default:
                    return $this->getNotificationResponse('Method not allowed', 405);
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

            if (
                !empty($paymentId)
                && !empty($externalReference)
                && !empty($timestamp)
            ) {

                $data = array();
                $data['payment_id'] = $paymentId;
                $data['external_reference'] = $externalReference;
                $data['timestamp'] = $timestamp;
                $secret = $this->mercadopago->getaccessToken();

                if ($this->isAuthenticated($data, $secret)) {

                    $cart = new Cart($externalReference);
                    $orderId = Order::getOrderByCartId($cart->id);

                    if ($orderId != 0) {

                        $response = $this->buildOrderResponse($orderId, $secret, $externalReference, $cart);

                        return $this->getNotificationResponse($response, 200);
                    }
                    return  $this->getNotificationResponse('Order not found', 404);
                }
                return  $this->getNotificationResponse('Unauthorized', 401);
            }
            return  $this->getNotificationResponse('Some parameters are empty', 400);
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
            $this->getNotificationResponse('Bad Request', 400);
        }
    }

    /**
     * Update Order
     * 
     * @return void
     */
    public function updateOrder()
    {

        $request = new Request();
        $data = $request->getJsonBody();

        if ($this->_validateUpdateOrderParams($data)) {
            $secret = $this->mercadopago->getaccessToken();

            if (is_null($secret) || empty($secret)) {
                $this->getNotificationResponse('Credentials not found', 500);
            }

            $cryptography = new Cryptography();
            $auth         = $cryptography->encrypt($data, $secret);

            $request = new Request();
            $token   = $request->getBearerToken();

            if (!$token) {
                return $this->getNotificationResponse('Unauthorized', 401);
            }

            if ($auth === $token) {
                return $this->getNotificationResponse('Authorized', 200);
            }

            return $this->getNotificationResponse('Unauthorized', 401);
        }

        return $this->getNotificationResponse('Some parameters are empty', 400);
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
            && isset($order['order_id'])
            && isset($order['payment_type_id'])
            && isset($order['payment_method_id'])
            && isset($order['payment_created_at'])
            && isset($order['total'])
            && isset($order['total_paid'])
            && isset($order['total_refunded']);
    }

    /**
     * Get error response
     *
     * @return void
     */
    public function getErrorResponse()
    {
        $this->getNotificationResponse(
            'The notification does not have the necessary parameters',
            500,
        );
    }

    /**
     * Get error response
     *
     * @return void
     */
    public function getNotificationResponse($body, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "version" => MP_VERSION
        );
        if (is_string($body)) {
            $response['message'] = $body;
        } else {
            foreach ($body as $key => $value) {
                $response[$key] = $value;
            }
        }
        echo Tools::jsonEncode($response);
        return http_response_code($code);
    }

    /**
     * Get order total
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
     * @param mixed $data, $secret
     * 
     * @return boolean
     */
    private function isAuthenticated($data, $secret)
    {

        if (is_null($secret) || empty($secret)) {
            return $this->getNotificationResponse('Credentials not found', 500);
        }

        $cryptography = new Cryptography();
        $auth         = $cryptography->encrypt($data, $secret);

        $request = new Request();
        $token   = $request->getBearerToken();

        if (!$token) {
            return $this->getNotificationResponse('Unauthorized', 401);
        }
        return ($auth === $token);
    }

     /**
     * Build order response
     * 
     * @param mixed $orderId, $secret, $externalReference, $cart
     * 
     * @return array
     */
    private function buildOrderResponse($orderId, $secret, $externalReference, $cart)
    {

        $order = new Order($orderId);
        $cryptography = new Cryptography();

        $response                       = array();
        $response['order_id']           = $orderId;
        $response['external_reference'] = $externalReference;
        $response['status']             = $order->getCurrentState();
        $response['created_at']         = $order->date_add;
        $response['total']              = $this->getTotal($cart);
        $response['timestamp']          = time();
        $response['hmac']               = "********************";

        MPLog::generate('Response: ' . Tools::jsonEncode($response));

        $hmac = $cryptography->encrypt($response, $secret);
        $response['hmac'] = $hmac;

        return $response;
    }
}
