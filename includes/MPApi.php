<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CoreSdkSettings.php';
require_once __DIR__ . '/module/settings/PaymentIssuanceManager.php';

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
     * Validate if the seller is homologated
     *
     * @return bool
     *
     * @throws Exception
     */
    public function getCredentialsWrapper($access_token)
    {
        $response = MPRestCli::get(
            '/plugins-credentials-wrapper/credentials',
            ['Authorization: Bearer ' . $access_token]
        );

        // in case of failures
        if ($response['status'] > 202) {
            MPLog::generate(
                'Validate homologation error (plugins-credentials-wrapper API). Status: ' . $response['status'],
                'error'
            );

            return false;
        }

        // response treatment
        $result = $response['response'];

        return $result;
    }

    /**
     * Get payment methods
     *
     * @return array|bool
     *
     * @throws Exception
     */
    public function getPaymentMethods()
    {
        $public_key = $this->getPublicKey();
        $response = MPRestCli::get('/ppcore/prod/payment-methods/v1/payment-methods', ['Authorization: ' . $public_key]);

        // in case of failures
        if (is_array($response) && isset($response['status'])) {
            if ($response['status'] > 202) {
                $message = isset($response['response']['message']) ? $response['response']['message'] : 'Mensagem não disponível';
                MPLog::generate('API get_payment_methods error: ' . $message, 'error');

                return false;
            }
        } else {
            MPLog::generate('API get_payment_methods error: Resposta inválida da API', 'error');

            return false;
        }

        // response treatment
        $result = $response['response'];
        asort($result);

        $payments = [];
        foreach ($result as $value) {
            // remove on paypay release
            if ($value['id'] == 'paypal') {
                continue;
            }

            if (isset($value['payment_places'])) {
                $paymentsDefaultData = $this->paymentsDefaultData($value);
                $paymentPlaces = ['payment_places' => $value['payment_places']];
                $payments[] = array_merge($paymentsDefaultData, $paymentPlaces);

                continue;
            }

            $payments[] = $this->paymentsDefaultData($value);
        }

        return $payments;
    }

    /**
     * Get payment main data
     *
     * @param array $value
     *
     * @return array
     */
    public function paymentsDefaultData($value)
    {
        return [
            'id' => Tools::strtoupper($value['id']),
            'name' => $value['name'],
            'type' => $value['payment_type_id'],
            'image' => $value['secure_thumbnail'],
            'config' => 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($value['id']),
            'sort' => isset($value['sort']) ? $value['sort'] : '999',
            'financial_institutions' => isset($value['financial_institutions']) ? $value['financial_institutions'] : [],
            'payment_places' => isset($value['payment_places']) ? $value['payment_places'] : [],
            'allowed_identification_types' => isset($value['allowed_identification_types']) ? $value['allowed_identification_types'] : [],
        ];
    }

    /**
     * Get standard payment
     *
     * @param int $transaction_id
     * @param bool $throwOnError Propagate SDK failures so notification callers can request retry
     *
     * @return array|false
     *
     * @throws Exception
     */
    public function getPaymentStandard($transaction_id, $throwOnError = false)
    {
        try {
            $transaction_id = preg_replace('/[^\d]/', '', (string) $transaction_id);
            $sdk = CoreSdkSettings::getInstance();
            $paymentModule = $sdk->getPaymentInstance();

            return $paymentModule->read([
                'id' => $transaction_id,
            ], [], false);
        } catch (Throwable $th) {
            MPLog::generate('SDK get_payment_standard error: ' . $th->getMessage(), 'error');

            if ($throwOnError) {
                throw $th;
            }

            return false;
        }
    }

    /**
     * Discover candidates omitted by a stale merchant-order payment list.
     * Callers must refetch each ID before using its financial state.
     *
     * @param string $cartId
     * @param string $merchantOrderId
     *
     * @return array
     */
    public function getMerchantOrderPaymentIds(string $cartId, string $merchantOrderId): array
    {
        if (!preg_match('/^[1-9][0-9]{0,19}$/D', $cartId)
            || !preg_match('/^(?:0|[1-9][0-9]{0,19})$/D', $merchantOrderId)
        ) {
            throw new InvalidArgumentException('Invalid payment discovery scope');
        }
        try {
            $paymentModule = CoreSdkSettings::getInstance()->getPaymentInstance();
            $ids = [];
            $seen = [];
            $total = null;
            $offset = 0;
            do {
                // Use the existing authenticated SDK transport without entity mapping.
                $page = $paymentModule->read(['id' => 'search'], [
                    'external_reference' => $cartId, 'sort' => 'date_created', 'criteria' => 'asc',
                    'limit' => 50, 'offset' => $offset,
                ], false);
                if (!is_array($page) || !isset($page['results'], $page['paging']['total'], $page['paging']['offset'])
                    || !is_array($page['results']) || !is_int($page['paging']['total'])
                    || $page['paging']['total'] < 0 || $page['paging']['total'] > 1000
                    || ($total !== null && $total !== $page['paging']['total'])
                    || $page['paging']['offset'] !== $offset
                    || count($page['results']) !== min(50, max(0, $page['paging']['total'] - $offset))
                ) {
                    throw new RuntimeException('Incomplete payment discovery response');
                }
                $total = $page['paging']['total'];
                foreach ($page['results'] as $payment) {
                    if (!is_array($payment) || !isset($payment['id'], $payment['external_reference'])
                        || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $payment['id'])
                        || (string) $payment['external_reference'] !== $cartId
                        || isset($seen[(string) $payment['id']])
                    ) {
                        throw new RuntimeException('Payment discovery ownership could not be verified');
                    }
                    $seen[(string) $payment['id']] = true;
                    // Reused carts can contain attempts from a different preference.
                    if ($merchantOrderId === '0' || (isset($payment['order']['id']) && (string) $payment['order']['id'] === $merchantOrderId)) {
                        $ids[] = (string) $payment['id'];
                    }
                }
                $offset += count($page['results']);
            } while ($offset < $page['paging']['total']);

            return array_values(array_unique($ids));
        } catch (Throwable $th) {
            MPLog::generate('SDK payment discovery failed; reconciliation deferred', 'error');
            throw new RuntimeException('Could not complete payment discovery', 0, $th);
        }
    }

    /** All cart search results are candidates, not amounts or closure evidence. */
    public function getCartPaymentIds(string $cartId): array
    {
        return $this->getMerchantOrderPaymentIds($cartId, '0');
    }

    /** Authenticated seller identity, never inferred from a token or caller input. */
    public function getAuthenticatedCollectorId(): string
    {
        require_once __DIR__ . '/module/model/AuthenticatedSellerResource.php';
        $resource = CoreSdkSettings::getInstance()->getEntityInstance(AuthenticatedSellerResource::class, 'https://api.mercadopago.com');
        $seller = $resource->read(['id' => 'me'], [], false);
        if (!is_array($seller) || !isset($seller['id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $seller['id'])) {
            throw new RuntimeException('Authenticated seller identity is unavailable');
        }

        return (string) $seller['id'];
    }

    /**
     * Get merchant order
     *
     * @param int $id
     * @param bool $throwOnError Propagate SDK failures so notification callers can request retry
     *
     * @return array|false
     *
     * @throws Exception
     */
    public function getMerchantOrder($id, $throwOnError = false)
    {
        try {
            $id = preg_replace('/[^\d]/', '', (string) $id);
            $sdk = CoreSdkSettings::getInstance();
            $merchantOrderModule = $sdk->getMerchantOrderInstance();

            return json_decode(json_encode($merchantOrderModule->getMerchantOrder($id)), true);
        } catch (Throwable $th) {
            MPLog::generate('SDK get_merchant_orders error: ' . $th->getMessage(), 'error');

            if ($throwOnError) {
                throw $th;
            }

            return false;
        }
    }

    /**
     * Get preferences
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getPreference($id)
    {
        try {
            $id = preg_replace('/[^\w-]/', '', (string) $id);
            $sdk = CoreSdkSettings::getInstance();
            $preferenceModule = $sdk->getPreferenceInstance();

            // The SDK entity omits response-only identity fields such as id and collector_id.
            return json_decode(json_encode($preferenceModule->read([
                'id' => $id,
            ], [], false)));
        } catch (Throwable $th) {
            MPLog::generate('SDK get_checkout_preferences error: ' . $th->getMessage(), 'error');

            return false;
        }
    }

    /**
     * @param $preference
     *
     * @return array|string|PaymentIssuanceRejected
     *
     * @throws Exception
     */
    public function createPreference($preference)
    {
        try {
            $sdk = CoreSdkSettings::getInstance();
            $preferenceModule = $sdk->getPreferenceInstance();

            $preferenceModule->setEntity($preference);

            // The pinned SDK exposes its manager through AbstractEntity's public accessors.
            $preferenceModule->__set('manager', new PaymentIssuanceManager($preferenceModule->__get('manager')));

            return $preferenceModule->save();
        } catch (Throwable $th) {
            MPLog::generate('API create_preference error: ' . $th->getMessage(), 'error');

            return $th->getMessage();
        }
    }

    /**
     * @param $preference
     *
     * @return array|string|PaymentIssuanceRejected
     *
     * @throws Exception
     */
    public function createPayment($preference)
    {
        try {
            $sdk = CoreSdkSettings::getInstance();
            $paymentModule = $sdk->getPaymentInstance();

            $paymentModule->setEntity($preference);

            $paymentModule->__set('manager', new PaymentIssuanceManager($paymentModule->__get('manager')));

            return $paymentModule->save();
        } catch (Throwable $th) {
            MPLog::generate('API create_custom_payment error: ' . $th->getMessage(), 'error');

            return $th->getMessage();
        }
    }

    /**
     * Is valid access token
     *
     * @param string $access_token
     *
     * @return bool
     *
     * @throws Exception
     */
    public function isValidAccessToken($access_token)
    {
        $response = MPRestCli::get('/users/me', ['Authorization: Bearer ' . $access_token]);

        // in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API valid_access_token error: ' . $response['response']['message'], 'error');

            return false;
        }

        // response treatment
        $result = $response['response'];

        return $result;
    }

    /**
     * Is test user
     *
     * @return bool
     *
     * @throws Exception
     */
    public function isTestUser()
    {
        $access_token = $this->getAccessToken();
        $response = MPRestCli::get('/users/me', ['Authorization: Bearer ' . $access_token]);

        // in case of failures
        if ($response['status'] > 202) {
            MPLog::generate('API is_test_user error: ' . $response['response']['message'], 'error');

            return false;
        }

        // response treatment
        if (in_array('test_user', $response['response']['tags'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $message
     *
     * @return string|null
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
