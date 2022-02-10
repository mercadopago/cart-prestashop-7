<?php
/**
 * 2007-2022 PrestaShop
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
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

class WalletButtonPreference extends AbstractPreference
{
    public $cart;

    public function __construct($cart)
    {
        parent::__construct();
        $this->checkout = 'standard';
        $this->cart = $cart;
    }

    /**
     * Create Wallet Button preference
     *
     * @return Array
     */
    public function createPreference()
    {
        $payload = $this->buildPreferencePayload();

        $this->generateLogs($payload, 'wallet button');
        $payloadToJson = Tools::jsonEncode($payload);

        $createPreference = $this->mercadopago->createPreference($payloadToJson);
        MPLog::generate('Cart id ' . $this->cart->id . ' - Standard Preference created successfully');

        return $createPreference;
    }

    /**
     * To build payload from Wallet Button payment
     *
     * @return Array
     */
    public function buildPreferencePayload()
    {
        $payloadParent = $this->getCommonPreference($this->cart);

        $payloadAdditional = [
            'items' => $this->getCartItems($this->cart),
            'payer' => $this->getCustomerData(),
            'shipments' => $this->getShipment(),
            'back_urls' => $this->getBackUrls(),
            'payment_methods' => $this->getPaymentOptions(),
            'auto_return' => $this->getAutoReturn(),
            'binary_mode' => $this->getBinaryMode(),
            'expires' => $this->getExpirationStatus(),
            'expiration_date_to' => $this->getExpirationDate(),
            'metadata' => $this->getInternalMetadata(),
            'purpose' => 'wallet_purchase',
        ];

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * Get customer data
     *
     * @return Array
     */
    public function getCustomerData()
    {
        $customerFields = Context::getContext()->customer->getFields();
        $addressInvoice = new Address((int) $this->cart->id_address_invoice);

        $customerData = array(
            'email' => $customerFields['email'],
            'first_name' => $customerFields['firstname'],
            'last_name' => $customerFields['lastname'],
            'phone' => array(
                'area_code' => '',
                'number' => $addressInvoice->phone,
            ),
            'identification' => array(
                'type' => '',
                'number' => '',
            ),
            'address' => array(
                'zip_code' => $addressInvoice->postcode,
                'street_name' => $addressInvoice->address1 . ' - ' .
                    $addressInvoice->address2 . ' - ' .
                    $addressInvoice->city . ' - ' .
                    $addressInvoice->country,
                'street_number' => '',
                'city' => $addressInvoice->city,
                'federal_unit' => '',
            ),
            'date_created' => date('c', strtotime($customerFields['date_add'])),
        );

        return $customerData;
    }

    /**
     * Get Mercado Pago payments options
     *
     * @return array
     */
    public function getPaymentOptions()
    {
        $excludedPaymentMethods = array();
        $paymentMethods = $this->mercadopago->getPaymentMethods();

        Configuration::updateValue('MERCADOPAGO_PAYMENT_ACCOUNT_MONEY', 'on');

        foreach ($paymentMethods as $paymentMethod) {
            $pmVariableName = 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($paymentMethods['id']);
            $value = Configuration::get($pmVariableName);

            if ($value != 'on') {
                $excludedPaymentMethods[] = array(
                    'id' => Tools::strtolower($paymentMethods['id']),
                );
            }
        }

        $paymentOptions = array(
            'installments' => (integer) $this->settings['MERCADOPAGO_INSTALLMENTS'],
            'excluded_payment_types' => array(),
            'excluded_payment_methods' => $excludedPaymentMethods,
        );

        return $paymentOptions;
    }

    /**
     * Get store shipment
     *
     * @return array
     */
    public function getShipment()
    {
        $addressShipment = new Address((int) $this->cart->id_address_delivery);

        $shipment = array(
            'receiver_address' => array(
                'zip_code' => $addressShipment->postcode,
                'street_name' => $addressShipment->address1 . ' - ' .
                    $addressShipment->address2 . ' - ' .
                    $addressShipment->city . ' - ' .
                    $addressShipment->country,
                'street_number' => '-',
                'apartment' => '-',
                'floor' => '-',
                'city_name' => $addressShipment->city,
            ),
        );

        return $shipment;
    }

    /**
     * Get back urls for preference callback
     *
     * @return array
     */
    public function getBackUrls()
    {
        return array(
            'success' => $this->getReturnUrl($this->cart, 'success'),
            'failure' => $this->getReturnUrl($this->cart, 'failure'),
            'pending' => $this->getReturnUrl($this->cart, 'pending'),
        );
    }

    /**
     * Get auto_return for preference
     *
     * @return mixed
     */
    public function getAutoReturn()
    {
        if ($this->settings['MERCADOPAGO_AUTO_RETURN'] == 1) {
            return $this->settings['MERCADOPAGO_AUTO_RETURN'] = 'approved';
        }
    }

    /**
     * Get binary_mode for preference
     *
     * @return mixed
     */
    public function getBinaryMode()
    {
        if ($this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] == 1) {
            return $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] = true;
        }

        return $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] = false;
    }

    /**
     * Define if expiration preference status
     *
     * @return mixed
     */
    public function getExpirationStatus()
    {
        if ($this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] != '') {
            return $this->settings['MERCADOPAGO_EXPIRATION'] = true;
        }

        return $this->settings['MERCADOPAGO_EXPIRATION'] = false;
    }

    /**
     * Get expiration_date_to for preference
     *
     * @return mixed
     */
    public function getExpirationDate()
    {
        if ($this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] != '') {
            return $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] = date(
                'Y-m-d\TH:i:s.000O',
                strtotime('+' . $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] . ' hours')
            );
        }

        return $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'];
    }

    /**
     * Get internal metadata
     *
     * @return Array
     */
    public function getInternalMetadata()
    {
        $internalMetadataParent = parent::getInternalMetadata($this->cart);

        $internalMetadataAdditional = [
            'checkout' => 'pro',
            'checkout_type' => 'modal',
        ];

        return array_merge($internalMetadataParent, $internalMetadataAdditional);
    }
}
