<?php

/**
 * 2007-2015 PrestaShop
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
 * @author    MERCADOPAGO.COM REPRESENTA&Ccedil;&Otilde;ES LTDA.
 * @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *          International Registered Trademark & Property of MercadoPago
 */

class MPApi
{
    public function __construct()
    { 
        
    }

    /**
     * Instance the class
     *
     * @return MPApi
     */
    public static function getinstance()
    {
        static $mercadopago = null;
        if (null === $mercadopago) {
            $mercadopago = new MPApi();
        }
        return $mercadopago;
    }

    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        if (Configuration::get('MERCADOPAGO_SANDBOX_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
        }

        return Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
    }

    /**
     * Get public key
     *
     * @return string
     */
    public function getPublicKey()
    {
        if (Configuration::get('MERCADOPAGO_SANDBOX_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        }

        return Configuration::get('MERCADOPAGO_PUBLIC_KEY');
    }

    /**
     * Get payment methods
     *
     * @return array|bool
     * @throws Exception
     */
    public function getPaymentMethods()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payment_methods', ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API get_payment_methods error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        asort($result);

        $payments = array();
        foreach ($result as $value) {
            //ticket open for fix
            if ($value['id'] == "pec") {
                continue;
            }

            $payments[] = array(
                'id' => Tools::strtoupper($value['id']),
                'name' => $value['name'],
                'type' => $value['payment_type_id'],
                'image' => $value['secure_thumbnail'],
                'config' => 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($value['id']),
            );
        }

        return $payments;
    }

    /**
     * @param $preference
     * @return bool
     * @throws Exception
     */
    public function createPreference($preference)
    {
        $access_token = $this->getAccessToken();
        $headers = [
            "platform:desktop",
            "type:prestashop",
            "so:1.0.0",
            "Authorization: Bearer " . $access_token
        ];
        $response = MPRestCli::post(
            '/checkout/preferences',
            $preference,
            $headers
        );

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API create_preferences error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        return $result;
    }

    /**
     * @param $preference
     * @return bool
     * @throws Exception
     */
    public function createPayment($preference)
    {
        $access_token = $this->getAccessToken();
        $headers = [
            "platform:desktop",
            "type:prestashop",
            "so:1.0.0",
            "Authorization: Bearer " . $access_token
        ];
        $response = MPRestCli::post('/v1/payments',
            $preference,
            $headers
        );

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API create_custom_payment error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        return $result;
    }

    /**
     * Get standard payment
     *
     * @param integer $transaction_id
     * @return bool
     * @throws Exception
     */
    public function getPaymentStandard($transaction_id)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payments/' . $transaction_id, ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API get_payment_standard error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        return $result;
    }

    /**
     * Is valid access token
     *
     * @param [string] $access_token
     * @return boolean
     * @throws Exception
     */
    public function isValidAccessToken($access_token)
    {
        $response = MPRestCli::get('/users/me', ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API valid_access_token error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        return $result;
    }

    /**
     * Is valid sponsor id
     *
     * @param $integrator_id
     * @return boolean
     * @throws Exception
     */
    public function isValidIntegratorId($integrator_id)
    {
        $response = MPRestCli::get('/users/' . $integrator_id);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API valid_integrator_id error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        if (
            $result['site_id'] != Configuration::get('MERCADOPAGO_SITE_ID') ||
            $result['id'] == Configuration::get('MERCADOPAGO_SELLER_ID')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Is test user
     *
     * @return boolean
     * @throws Exception
     */
    public function isTestUser()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/users/me', ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API is_test_user error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        if (in_array('test_user', $response['response']['tags'])) {
            return true;
        }
    }

    /**
     * Get merchant order
     *
     * @param [integer] $id
     * @return bool
     * @throws Exception
     */
    public function getMerchantOrder($id)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/merchant_orders/' . $id, ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API get_merchant_orders error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        return $result;
    }

    /**
     * Send platform info to settings api
     *
     * @param [array] $params
     * @return bool
     * @throws Exception
     */
    public function saveApiSettings($params)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::post('/modules/tracking/settings', $params, ["Authorization: Bearer " . $access_token]);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API save_api_settings error: ' . $response['response']['message'], 'error');
            return false;
        }

        return true;
    }

    /**
     * Get application_id
     *
     * @param [integer] $seller
     * @return int
     */
    public function getApplicationId()
    {
        $seller = explode('-', $this->getAccessToken());
        return $seller[1];
    }

    /**
     * Get application_id
     *
     * @return bool
     * @throws Exception
     */
    public function homologValidate()
    {
        $seller = $this->getApplicationId();
        $response = MPRestCli::getMercadoLibre('/applications/' . $seller);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API application_search_owner_id error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];

        return $result['scopes'];
    }
}
