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

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

class StandardPreference extends AbstractPreference
{
    public function __construct()
    {
        parent::__construct();
        $this->checkout = 'standard';
    }

    /**
     * @param $cart
     * @return bool
     * @throws Exception
     */
    public function createPreference($cart)
    {
        $preference = $this->getCommonPreference($cart);
        $preference['items'] = $this->getCartItems($cart);
        $preference['payer'] = $this->getCustomerData($cart);
        $preference['shipments'] = $this->getShipment();
        $preference['back_urls'] = $this->getBackUrls($cart);
        $preference['payment_methods'] = $this->getPaymentOptions();
        $preference['auto_return'] = $this->getAutoReturn();
        $preference['binary_mode'] = $this->getBinaryMode();
        $preference['expires'] = $this->getExpirationStatus();
        $preference['expiration_date_to'] = $this->getExpirationDate();
        $preference['metadata'] = $this->getInternalMetadata();

        //Create preference
        $preferenceEncoded = Tools::jsonEncode($preference);
        MPLog::generate('Create Preference Infos: ' . $preferenceEncoded);
        $createPreference = $this->mercadopago->createPreference($preferenceEncoded);
        MPLog::generate('Created Preference: ' . Tools::jsonEncode($createPreference));

        return $createPreference;
    }

    /**
     * Get customer data
     *
     * @return array
     */
    public function getCustomerData($cart)
    {
        $customer_fields = Context::getContext()->customer->getFields();
        $address_invoice = new Address((int) $cart->id_address_invoice);

        $customer_data = array(
            'name' => $customer_fields['firstname'],
            'surname' => $customer_fields['lastname'],
            'email' => $customer_fields['email'],
            'phone' => array(
                'area_code' => '',
                'number' => $address_invoice->phone,
            ),
            'identification' => array(
                'type' => '',
                'number' => '',
            ),
            'address' => array(
                'zip_code' => $address_invoice->postcode,
                'street_name' => $address_invoice->address1 . ' - ' .
                    $address_invoice->address2 . ' - ' .
                    $address_invoice->city . ' - ' .
                    $address_invoice->country,
                'street_number' => '',
            ),
            'date_created' => date("c", strtotime($customer_fields['date_add'])),
        );

        return $customer_data;
    }

    /**
     * Get Mercado Pago payments options
     *
     * @return array
     */
    public function getPaymentOptions()
    {
        $excluded_payment_methods = array();
        $payment_methods = $this->mercadopago->getPaymentMethods();

        foreach ($payment_methods as $payment_method) {
            $pm_variable_name = 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($payment_method['id']);
            $value = Configuration::get($pm_variable_name);

            if ($value != "on") {
                $excluded_payment_methods[] = array(
                    'id' => Tools::strtolower($payment_method['id']),
                );
            }
        }

        $payment_options = array(
            'installments' => (integer) $this->settings['MERCADOPAGO_INSTALLMENTS'],
            'excluded_payment_types' => array(),
            'excluded_payment_methods' => $excluded_payment_methods,
        );

        return $payment_options;
    }

    /**
     * Get store shipment
     *
     * @return array
     */
    public function getShipment()
    {
        return array(
            'mode' => 'not_specified'
        );
    }

    /**
     * Get back urls for preference callback
     *
     * @param mixed $cart
     * @return array
     */
    public function getBackUrls($cart)
    {
        return array(
            'success' => $this->getReturnUrl($cart, 'success'),
            'failure' => $this->getReturnUrl($cart, 'failure'),
            'pending' => $this->getReturnUrl($cart, 'pending'),
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
        if ($this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] != "") {
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
        if ($this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] != "") {
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
     * @return array
     */
    public function getInternalMetadata()
    {
        $internal_metadata = parent::getInternalMetadata();
        $internal_metadata["checkout"] = "smart";
        $internal_metadata["checkout_type"] = "redirect";

        if ($this->settings['MERCADOPAGO_STANDARD_MODAL'] == true) {
            $internal_metadata["checkout_type"] = "modal";
        }
        
        return $internal_metadata;
    }
}
