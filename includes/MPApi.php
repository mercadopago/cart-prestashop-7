<?php
/**
 * 2007-2020 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
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
        if (Configuration::get('MERCADOPAGO_PROD_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        }

        return Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
    }

    /**
     * Get public key
     *
     * @return string
     */
    public function getPublicKey()
    {
        if (Configuration::get('MERCADOPAGO_PROD_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        }

        return Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
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
            // remove on paypay release
            if ($value['id'] == 'paypal') {
                continue;
            }
            
            $payments[] = array(
                'id' => Tools::strtoupper($value['id']),
                'name' => $value['name'],
                'type' => $value['payment_type_id'],
                'image' => $value['secure_thumbnail'],
                'config' => 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($value['id']),
                'financial_institutions' => $value['financial_institutions'],
            );
        }

        return $payments;
    }

    /**
     * Create preference
     *
     * @param $preference
     * @return bool
     * @throws Exception
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
     * Create payment
     *
     * @param array $preference
     * @return bool
     * @throws Exception
     */
    public function createPayment($preference)
    {
        $access_token = $this->getAccessToken();
        $tracking_id = "platform:desktop,type:prestashop,so:1.0.0";
        $response = MPRestCli::postTracking(
            '/v1/payments?access_token=' . $access_token,
            $preference,
            $tracking_id
        );

        //in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API create_custom_payment error: ' . $response['response']['message'], 'error');
            return $response['response']['message'];
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
        $response = MPRestCli::get('/v1/payments/' . $transaction_id . '?access_token=' . $access_token);

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
     * Is test user
     *
     * @return boolean
     * @throws Exception
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
     * @return bool
     * @throws Exception
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

    /**
     * @param null $message
     * @return null
     */
    public static function validateMessageApi($message = null)
    {
        $module = Module::getInstanceByName('mercadopago');

        switch (trim($message)) {
            case 'Invalid payment_method_id':
                return $module->l('The payment method is not valid or not available.', 'MPApi');
            case 'Invalid transaction_amount':
                return $module->l('The transaction amount cannot be processed by Mercado Pago. ', 'MPApi') .
                    $module->l('Possible causes: Currency not supported; ', 'MPApi') .
                    $module->l('Amounts below the minimum or above the maximum allowed.', 'MPApi');
            case 'Invalid users involved':
                return $module->l('The users are not valid. Possible causes: ', 'MPApi') .
                    $module->l('Buyer and seller have the same account in Mercado Pago; ', 'MPApi') .
                    $module->l('The transaction involving production and test users.', 'MPApi');
            case 'Unauthorized use of live credentials':
                return $module->l('Unauthorized use of production credentials. ', 'MPApi') .
                    $module->l('Possible causes: Use permission in use for the credential of the seller.', 'MPApi');
            default:
                return null;
        }
    }
}
