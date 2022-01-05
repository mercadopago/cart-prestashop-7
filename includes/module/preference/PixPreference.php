<?php
/**
 * 2007-2021 PrestaShop
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
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

class PixPreference extends AbstractPreference
{
    public function __construct($cart)
    {
        parent::__construct();
        $this->checkout = 'pix';
        $this->cart = $cart;
    }

    /**
     * Create Pix preference
     *
     * @param Array $body Data returned from POST method in Pix page
     *
     * @return Array
     */
    public function createPreference($body)
    {
        $payload = $this->_buildPixPreferencePayload($body);
        $this->_generateLogs($payload, 'pix');
        
        $payloadToJson = Tools::jsonEncode($payload);
        $this->_setCartRule($this->cart);
        
        $createPreference = $this->mercadopago->createPayment($payloadToJson);

        MPLog::generate('Cart ID ' . $this->cart->id . ' - Pix Preference created successfully');

        return $createPreference;
    }

    /**
     * Set custom discount on CartRule()
     *
     * @param Object $cart
     *
     * @return void
     */
    private function _setCartRule($cart)
    {
        $discount = $this->settings['MERCADOPAGO_PIX_DISCOUNT'];

        if ($discount) {
            parent::setCartRule($cart, $discount);
            MPLog::generate(
                'Mercado Pago custom discount applied to cart ' . $cart->id
            );
        }
    }

    /**
     * Disable cart rule when buyer completes purchase
     *
     * @return void
     */
    public function disableCartRule()
    {
        if ($this->settings['MERCADOPAGO_PIX_DISCOUNT']) {
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
        if ($this->settings['MERCADOPAGO_PIX_DISCOUNT'] != "") {
            parent::deleteCartRule();
        }
    }

    /**
     * Get customer data
     *
     * @param Array $cart Shopping cart data
     *
     * @return Array
     */
    private function _getCustomerData($cart)
    {
        $customerFields = Context::getContext()->customer->getFields();
        $addressInvoice = new Address((int) $cart->id_address_invoice);

        $customer_data = array(
            'email' => $customerFields['email'],
            'first_name' => $customerFields['firstname'],
            'last_name' => $customerFields['lastname'],
            'identification' => array(
                'type' => '',
                'number' => '',
            ),
            'address' => array(
                'zip_code' => $addressInvoice->postcode,
                'street_name' => $addressInvoice->address1 . ' - ' . $addressInvoice->address2,
                'street_number' => '',
                'neighborhood' => $addressInvoice->city,
                'city' => $addressInvoice->city,
                'federal_unit' => $addressInvoice->state,
            )
        );

        return $customer_data;
    }

    /**
     * To build payload from Pix payment
     *
     * @param Array $body Data returned from POST method in Pix page
     *
     * @return Array
     */
    private function _buildPixPreferencePayload($body)
    {
        $payload = $this->getCommonPreference($this->cart);
        $payload['date_of_expiration'] = $this->_getExpirationDate();
        $payload['description'] = $this->getPreferenceDescription($cart);
        $payload['payment_method_id'] = $body['payment_method_id'];
        $payload['payer'] = $this->_getCustomerData($this->cart);
        $payload['metadata'] = $this->_getInternalMetadata();
        $payload['transaction_amount'] = intval($body['totalAmount']);
        $payload['additional_info'] = $this->_getAdditionalInfo();

        return $payload;
    }

    /**
     * Get additional info
     *
     * @return Array
     */
    private function _getAdditionalInfo() 
    {
        $additional_info['shipments'] = $this->getShipmentAddress($this->$cart);
        $additional_info['payer'] = $this->getCustomCustomerData($cart);
        $additional_info['items'] = $this->getCartItems(
            $cart,
            true,
            $this->settings['MERCADOPAGO_TICKET_DISCOUNT']
        );
        
        return $internal_metadata;
    }

    /**
     * Get expiration_date_to for preference
     *
     * @return Array
     */
    private function _getExpirationDate()
    {
        if ($this->settings['MERCADOPAGO_PIX_EXPIRATION'] != "") {
            return $this->settings['MERCADOPAGO_PIX_EXPIRATION'] = date(
                'Y-m-d\TH:i:s.000O',
                strtotime('+' . $this->settings['MERCADOPAGO_PIX_EXPIRATION'] . ' minutes')
            );
        }

        return $this->settings['MERCADOPAGO_PIX_EXPIRATION'];
    }

    /**
     * Get internal metadata
     *
     * @return Array
     */
    private function _getInternalMetadata()
    {
        $internal_metadata = parent::getInternalMetadata();
        $internal_metadata['checkout'] = 'custom';
        $internal_metadata['checkout_type'] = 'pix';

        return $internal_metadata;
    }

    /**
     * Generate preference logs
     *
     * @param Array $preferencePayload Data about payment
     *
     * @return void
     */
    private function _generateLogs($preferencePayload)
    {
        $logs = [
            "cart_id" => $preferencePayload['external_reference'],
            "cart_total" => $this->cart->getOrderTotal(),
            "cart_items" => $preferencePayload['items'],
            "metadata" => array_diff_key($preferencePayload['metadata'], array_flip(['collector'])),
        ];

        $encodedLogs = Tools::jsonEncode($logs);
        MPLog::generate('Pix Preference Payload logs: ' . $encodedLogs);
    }
}