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

class TicketPreference extends AbstractPreference
{
    public $methods;
    public $financial_institutions = array();
    public $ticket_info;

    public function __construct()
    {
        parent::__construct();
        $this->checkout = 'custom';
        $this->methods = $this->mercadopago->getPaymentMethods();
    }

    /**
     * @param  $cart
     * @param  $ticket_info
     * @return bool
     * @throws Exception
     */
    public function createPreference($cart, $ticket_info)
    {
        $this->ticket_info = $ticket_info;
        $preference = $this->getCommonPreference($cart);
        $preference['date_of_expiration'] = $this->getExpirationDate();
        $preference['description'] = $this->getPreferenceDescription($cart);
        $preference['payment_method_id'] = $this->mpuseful->getPaymentMethodId($ticket_info['paymentMethodId']);
        $preference['payer']['email'] = $this->getCustomerEmail();
        $preference['metadata'] = $this->getInternalMetadata($cart, $ticket_info);

        if ($this->settings['MERCADOPAGO_SITE_ID'] == 'MLB') {
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

        if ($this->settings['MERCADOPAGO_SITE_ID'] == 'MLU') {
            $preference['payer']['identification']['type'] = $ticket_info['docType'];
            $preference['payer']['identification']['number'] = $ticket_info['docNumber'];
        }

        $bankTransfers = $this->getBankTransferMethods();
        if (in_array(Tools::strtoupper($ticket_info['paymentMethodId']), $bankTransfers)) {
            $financial_institution = "1065";
            if (isset($this->financial_institutions[Tools::strtoupper($ticket_info['paymentMethodId'])])) {
                $financial_institution = $this->financial_institutions[
                    Tools::strtoupper($ticket_info['paymentMethodId'])
                ];
            }
            $preference['callback_url'] = $this->getSiteUrl();
            $preference['transaction_details']['financial_institution'] = $financial_institution;
            $preference['additional_info']['ip_address'] = "127.0.0.1";
            $preference['payer']['identification']['type'] = "RUT";
            $preference['payer']['identification']['number'] = "0";
            $preference['payer']['entity_type'] = "individual";
        }

        $preference['additional_info']['payer'] = $this->getCustomCustomerData($cart);
        $preference['additional_info']['shipments'] = $this->getShipmentAddress($cart);
        $preference['additional_info']['items'] = $this->getCartItems(
            $cart,
            true,
            $this->settings['MERCADOPAGO_TICKET_DISCOUNT']
        );

        //Update cart total with CartRule()
        $this->setCartRule($cart, $this->settings['MERCADOPAGO_TICKET_DISCOUNT']);
        $preference['transaction_amount'] = $this->getTransactionAmount($cart);

        //Generate preference
        $this->generateLogs($preference, 'ticket');

        //Create preference
        $createPreference = $this->mercadopago->createPayment($preference);
        MPLog::generate('Cart id ' . $cart->id . ' - Ticket Preference created successfully');

        return $createPreference;
    }

    /**
     * Get transaction amount
     *
     * @param  mixed $cart
     * @return void
     */
    public function getTransactionAmount($cart)
    {
        $total = (float) $cart->getOrderTotal();
        $localization = $this->settings['MERCADOPAGO_SITE_ID'];
        if ($localization == 'MCO' || $localization == 'MLC') {
            return Tools::ps_round($total, 2);
        }

        return $total;
    }

    /**
     * Set ticket discount on CartRule()
     *
     * @param  mixed $cart
     * @return void
     */
    public function setCartRule($cart, $discount)
    {
        if ($discount != "") {
            parent::setCartRule($cart, $discount);
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
                strtotime('+' . $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] . ' days')
            );
        }
    }

    /**
     * Get internal metadata
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $internal_metadata = parent::getInternalMetadata($cart);
        $internal_metadata['checkout'] ='custom';
        $internal_metadata['checkout_type'] ='ticket';
        $internal_metadata['payment_option_id'] = $this->mpuseful->getPaymentPlaceId($this->ticket_info['paymentMethodId']);

        return $internal_metadata;
    }

    /**
     * @return array
     */
    public function getBankTransferMethods()
    {
        $bankTransfers = array();

        foreach ($this->methods as $method) {
            if ($method['type'] == 'bank_transfer') {
                array_push($bankTransfers, Tools::strtoupper($method['id']));
                if (!empty($method['financial_institutions'])) {
                    $method_id = Tools::strtoupper($method['id']);
                    $financial_institution = $method['financial_institutions'][0]['id'];

                    $this->financial_institutions[$method_id] = $financial_institution;
                }
            }
        }

        return $bankTransfers;
    }
}
