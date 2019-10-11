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
        $this->checkout = 'custom';
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
        $preference['description'] = $this->getPreferenceDescription($cart);
        $preference['payment_method_id'] = $ticket_info['paymentMethodId'];
        $preference['payer']['email'] = $this->getCustomerEmail();
        $preference['metadata'] = $this->getInternalMetadata(); 

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

        $preference['additional_info']['payer'] = $this->getCustomCustomerData($cart);
        $preference['additional_info']['shipments'] = $this->getShipmentAddress($cart);

        $preference['additional_info']['items'] = $this->getCartItems(
            $cart,
            true,
            $this->settings['MERCADOPAGO_TICKET_DISCOUNT']
        );

        //Validate mercadopago coupon
        if ($this->settings['MERCADOPAGO_TICKET_COUPON'] == true && $ticket_info['coupon_code'] != "") {
            if ($ticket_info['percent_off'] == 0) {
                $preference['campaign_id'] = $ticket_info['campaign_id'];
                $preference['coupon_amount'] = $ticket_info['coupon_amount'];
            } else {
                $preference['coupon_code'] = $ticket_info['coupon_code'];
            }
        }

        //Update cart total with CartRule()
        $this->setCartRule($cart);
        $preference['transaction_amount'] = $this->getTransactionAmount($cart);

        //Create preference
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
        $localization = $this->settings['MERCADOPAGO_SITE_ID'];
        if ($localization == 'MCO' || $localization == 'MLC') {
            return round($total);
        }

        return $total;
    }

    /**
     * Set ticket discount on CartRule()
     *
     * @param mixed $cart
     * @return void
     */
    public function setCartRule($cart)
    {
        if ($this->settings['MERCADOPAGO_TICKET_DISCOUNT'] != "") {
            parent::setCartRule($cart, $this->settings['MERCADOPAGO_TICKET_DISCOUNT']);
            MPLog::generate('Mercado Pago ticket discount applied to cart ' . $cart->id);
        }
    }

    /**
     * Disable cart rule when buyer completes purchase
     *
     * @return void
     */
    public function disableCartRule()
    {
        if ($this->settings['MERCADOPAGO_TICKET_DISCOUNT'] != "") {
            parent::disableCartRule();
        }
    }

    /**
     * Delete cart rule if an error occurs
     *
     * @return void
     */
    public function deleteCartRule()
    {
        if ($this->settings['MERCADOPAGO_TICKET_DISCOUNT'] != "") {
            parent::deleteCartRule();
        }
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
    }

    /**
     * Get internal metadata
     * 
     * @return array
     */
    public function getInternalMetadata()
    {
        $internal_metadata = parent::getInternalMetadata();
        $internal_metadata["checkout"] = "custom";
        $internal_metadata["checkout_type"] = "ticket";
        
        return $internal_metadata;
    }
}
