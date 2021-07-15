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

use Helpers\Cryptography;
use Helpers\Request;

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

        try {
            $payment_id = Tools::getValue('payment_id');
            $external_reference = Tools::getValue('external_reference');
            $timestamp = Tools::getValue('timestamp');

            if (
                !empty($payment_id)
                && !empty($external_reference)
                && !empty($timestamp)
            ) {
                $data = array();

                $data['payment_id'] = $payment_id;
                $data['external_reference'] = $external_reference;
                $data['timestamp'] = $timestamp;

                $secret = $this->mercadopago->getaccessToken();

                if (is_null($secret) || empty($secret)) {
                    $this->getNotificationResponse('Credentials not found', 500);
                }

                $auth  = Cryptography::encrypt($data, $secret);
                $token = Request::getBearerToken();

                if (!$token) {
                    $this->getNotificationResponse('Unauthorized', 401);
                } elseif ($auth === $token) {
                    $cart = new Cart($external_reference);
                    $order_id = Order::getOrderByCartId($cart->id);

                    if ($order_id != 0) {
                        $order = new Order($order_id);

                        $response                       = array();
                        $response['order_id']           = $order_id;
                        $response['external_reference'] = $external_reference;
                        $response['status']             = $order->getCurrentState();
                        $response['created_at']         = $order->date_add;
                        $response['total']              = $this->getTotal($cart);
                        $response['timestamp']          = time();

                        MPLog::generate('Response: ' . Tools::jsonEncode($response));

                        $hmac             = Cryptography::encrypt($response, $secret);
                        $response['hmac'] = $hmac;


                        $this->getNotificationResponse(
                            $response,
                            200 
                        );
                    } else {
                        $this->getNotificationResponse('Order not found', 404);
                    }
                } else {
                    $this->getNotificationResponse('Unauthorized', 401);
                }
            } else {
                $this->getNotificationResponse('Some parameters are empty', 400);
            }
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
            $this->getNotificationResponse('Bad Request', 400);
        }
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
            null
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
            foreach ($body as $key=>$value) {
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
}
