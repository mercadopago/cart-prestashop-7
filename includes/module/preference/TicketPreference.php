<?php

/**
 * 2007-2018 PrestaShop.
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
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

class TicketPreference extends AbstractPreference
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get preference params to send to MP
     *
     * @param mixed $cart
     * @return mixed
     */
    public function createPreference($cart, $ticket_info)
    {
        $preference = $this->getCommonPreference($cart);
        $preference['date_of_expiration'] = $this->getExpirationDate();
        $preference['transaction_amount'] = $this->getTransactionAmount($cart);
        $preference['description'] = $this->getPreferenceDescription($cart);
        $preference['payment_method_id'] = $ticket_info['paymentMethodId'];
        $preference['payer']['email'] = $this->getCustomerEmail();

        if ($this->module->context->currency->iso_code == 'BRL') {
            $preference['payer']['first_name'] = $ticket_info['firstname'];
            $preference['payer']['last_name'] = $ticket_info['docType'] == "CPF" ? $ticket_info['lastname'] : "";
            $preference['payer']['identification']['type'] = $ticket_info['docType'];
            $preference['payer']['identification']['number'] = $ticket_info['docNumber'];
            $preference['payer']['address']['street_name'] = $ticket_info['address'];
            $preference['payer']['address']['street_number'] = $ticket_info['number'];
            $preference['payer']['address']['neighborhood'] = $ticket_info['city'];
            $preference['payer']['address']['city'] = $ticket_info['city'];
            $preference['payer']['address']['federal_unit'] = $ticket_info['state'];
            $preference['payer']['address']['zip_code'] = $ticket_info['zipcode'];
        }

        $preference['additional_info']['items'] = $this->getCartItems($cart);
        $preference['additional_info']['payer'] = $this->getCustomCustomerData($cart);
        $preference['additional_info']['shipments'] = $this->getShipmentAddress($cart);

        $preference = Tools::jsonEncode($preference);
        return $preference;

        $preference = Tools::jsonEncode($preference);
        $createPreference = $this->mercadopago->createPayment($preference);

        return $createPreference;
    }

    /**
     * Get transaction amount
     *
     * @param mixed $cart
     * @return void
     */
    public function getTransactionAmount($cart)
    {
        $total = (float) $cart->getOrderTotal();
        $localization = $this->settings['MERCADOPAGO_COUNTRY_LINK'];
        if ($localization == 'mco' || $localization == 'mlc') {
            return round($total);
        }

        return $total;
    }

    /**
     * Get expiration_date_to for preference
     *
     * @return mixed
     */
    public function getExpirationDate()
    {
        if ($this->settings['MERCADOPAGO_TICKET_EXPIRATION'] != "") {
            return $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] = date(
                'Y-m-d\TH:i:s.000O',
                strtotime('+' . $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] . ' hours')
            );
        }

        return $this->settings['MERCADOPAGO_TICKET_EXPIRATION'];
    }
}
