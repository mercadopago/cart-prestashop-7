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
     * @return void
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
     * @return void
     */
    protected function getAccessToken()
    {
        if (Configuration::get('MERCADOPAGO_SANDBOX_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
        }

        return Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
    }

    /**
     * Get payment methods
     *
     * @return void
     */
    public function getPaymentMethods()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payment_methods?access_token=' . $access_token);

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
     * Get online payment methods
     *
     * @return void
     */
    public function getOnlinePaymentMethods()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payment_methods?access_token=' . $access_token);

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
            if ($value['payment_type_id'] == 'credit_card' ||
                $value['payment_type_id'] == 'debit_card' ||
                $value['payment_type_id'] == 'prepaid_card'
            ) {
                $payments[] = array(
                    'id' => Tools::strtoupper($value['id']),
                    'name' => $value['name'],
                );
            }
        }

        return $payments;
    }

    /**
     * Get offline payment methods
     *
     * @return void
     */
    public function getOfflinePaymentMethods()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payment_methods?access_token=' . $access_token);

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
            if ($value['payment_type_id'] != 'credit_card' &&
                $value['payment_type_id'] != 'debit_card' &&
                $value['payment_type_id'] != 'prepaid_card'
            ) {
                $payments[] = array(
                    'id' => Tools::strtoupper($value['id']),
                    'name' => $value['name'],
                );
            }
        }

        return $payments;
    }

    /**
     * Create preference
     *
     * @param [array] $preference
     * @return void
     */
    public function createPreference($preference)
    {
        $access_token = $this->getAccessToken();
        $tracking_id = "platform:desktop,type:prestashop,so:1.0.0";
        $response = MPRestCli::postTracking(
            '/checkout/preferences?access_token=' . $access_token,
            $preference,
            $tracking_id
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
     * Get payment standard
     *
     * @param [integer] $collection_id
     * @return void
     */
    public function getPaymentStandard($collection_id)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/v1/payments/' . $collection_id . '?access_token=' . $access_token);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API get_payment_standard error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response;
        return $result;
    }

    /**
     * Is valid access token
     *
     * @param [string] $access_token
     * @return boolean
     */
    public function isValidAccessToken($access_token)
    {
        $response = MPRestCli::get('/users/me?access_token=' . $access_token);

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
     * @param [integer] $sponsor_id
     * @return boolean
     */
    public function isValidSponsorId($sponsor_id)
    {
        $response = MPRestCli::get('/users/' . $sponsor_id);

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API valid_sponsor_id error: ' . $response['response']['message'], 'error');
            return false;
        }

        //response treatment
        $result = $response['response'];
        if ($result['site_id'] != Configuration::get('MERCADOPAGO_SITE_ID') ||
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
     */
    public function isTestUser()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/users/me?access_token=' . $access_token);

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
     * @return void
     */
    public function getMerchantOrder($id)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/merchant_orders/' . $id . '?access_token=' . $access_token);

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
     * @return void
     */
    public function saveApiSettings($params)
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::post('/modules/tracking/settings?access_token=' . $access_token, $params);

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
     * @param [integer] $seller
     * @return int
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
