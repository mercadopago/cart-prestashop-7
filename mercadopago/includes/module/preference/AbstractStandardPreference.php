<?php
/**
 * 2007-2025 PrestaShop
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
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

abstract class AbstractStandardPreference extends AbstractPreference
{
    public $mpuseful;

    /**
     * AbstractStandardPreference constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * To build payload from Wallet Button payment
     *
     * @param $cart
     * @return array
     */
    public function buildPreferencePayload($cart, $discount = 0)
    {
        $items         = $this->getCartItems($cart);
        $payloadParent = $this->getCommonPreference($cart);

        if ($discount != 0) {
            $totalInfo = $this->mpuseful->getCorrectedTotal($cart, 'wallet_button');

            $discountPerItem = array(
                'id'          => 'discount',
                'title'       => 'Discount',
                'quantity'    => 1,
                'unit_price'  => -$totalInfo['discount'],
                'category_id' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
                'description' => 'Discount provided by store',
            );
            array_push($items, $discountPerItem);

            $itemsAmount = array_reduce(
                $items,
                function ($accumulator, $item) {
                    $accumulator += $item['unit_price'] * $item['quantity'];
                    return $accumulator;
                }
            );

            $amountDifferenceItem = array(
                'id'          => 'difference',
                'title'       => 'Difference',
                'quantity'    => 1,
                'unit_price'  => $totalInfo['amount_with_round'] - $itemsAmount,
                'category_id' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
                'description' => 'Difference provided by store',
            );
            array_push($items, $amountDifferenceItem);
        }

        $payloadAdditional = array(
            'items'              => $items,
            'payer'              => $this->getCustomerData($cart),
            'shipments'          => $this->getShipment($cart),
            'back_urls'          => $this->getBackUrls($cart),
            'payment_methods'    => $this->getPaymentOptions(),
            'auto_return'        => $this->getAutoReturn(),
            'binary_mode'        => $this->getBinaryMode(),
            'expires'            => $this->getExpirationStatus(),
            'expiration_date_to' => $this->getExpirationDate(),
        );

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * Get customer data
     *
     * @param $cart
     * @return array
     */
    public function getCustomerData($cart)
    {
        $customer = Context::getContext()->customer;
        if (!(empty($customer->firstname) && empty($customer->lastname))) {
            $customerFields = $customer->getFields();
            $addressInvoice = new Address((int) $cart->id_address_invoice);

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
            $pmVariableName = 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($paymentMethod['id']);
            $value = Configuration::get($pmVariableName);

            if ($value != 'on') {
                $excludedPaymentMethods[] = array(
                    'id' => Tools::strtolower($paymentMethod['id']),
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
     * @param $cart
     * @return array
     */
    public function getShipment($cart)
    {
        $addressShipment = new Address((int) $cart->id_address_delivery);

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
     * @param $cart
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
     * @param $cart
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        return parent::getInternalMetadata($cart);
    }
}
