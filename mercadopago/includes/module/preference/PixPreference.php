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

class PixPreference extends AbstractPreference
{
    public $cart;

    public function __construct($cart)
    {
        parent::__construct();
        $this->checkout = 'custom';
        $this->cart = $cart;
    }

    /**
     * Create Pix preference
     *
     * @param Array $body Data returned from POST method in Pix page
     *
     * @return Array
     */
    public function createPreference()
    {
        $payload = $this->_buildPixPreferencePayload();

        $this->_setCartRule();
        $payload['transaction_amount'] = $this->_getAmount();

        $this->generateLogs($payload, 'pix');
        $payloadToJson = Tools::jsonEncode($payload);

        $createPreference = $this->mercadopago->createPayment($payloadToJson);
        MPLog::generate('Cart ID ' . $this->cart->id . ' - Pix Preference created successfully');

        return $createPreference;
    }

    /**
     * Set custom discount on CartRule()
     *
     * @return void
     */
    private function _setCartRule()
    {
        $discount = $this->settings['MERCADOPAGO_PIX_DISCOUNT'];

        if ($discount) {
            parent::setCartRule($this->cart, $discount);
            MPLog::generate(
                'Mercado Pago custom discount applied to cart ' . $this->cart->id
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
        if ($this->settings['MERCADOPAGO_PIX_DISCOUNT'] != '') {
            parent::deleteCartRule();
        }
    }

    /**
     * To build payload from Pix payment
     *
     * @param Array $body Data returned from POST method in Pix page
     *
     * @return Array
     */
    private function _buildPixPreferencePayload()
    {
        $payload_parent = $this->getCommonPreference($this->cart);

        $payload_additional = [
            'date_of_expiration' => $this->_getExpirationDate(),
            'description' => $this->getPreferenceDescription($this->cart),
            'payment_method_id' => 'pix',
            'payer' => $this->_getCustomerData($this->cart),
            'metadata' => $this->_getInternalMetadata(),
            'additional_info' => $this->_getAdditionalInfo(),
        ];

        return array_merge($payload_parent, $payload_additional);
    }

    /**
     * Get expiration_date_to for preference
     *
     * @return Array
     */
    private function _getExpirationDate()
    {
        if ($this->settings['MERCADOPAGO_PIX_EXPIRATION'] != '') {
            return $this->settings['MERCADOPAGO_PIX_EXPIRATION'] = date(
                'Y-m-d\TH:i:s.000O',
                strtotime('+' . $this->settings['MERCADOPAGO_PIX_EXPIRATION'] . ' minutes')
            );
        }

        return $this->settings['MERCADOPAGO_PIX_EXPIRATION'];
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
                'street_name' => $addressInvoice->address1 . ' - ' .
                    $addressInvoice->address2 . ' - ' .
                    $addressInvoice->city . ' - ' .
                    $addressInvoice->country,
                'street_number' => '',
                'city' => $addressInvoice->city,
                'federal_unit' => '',
            )
        );

        return $customer_data;
    }

    /**
     * Get internal metadata
     *
     * @return Array
     */
    private function _getInternalMetadata()
    {
        $internal_metadata_parent = parent::getInternalMetadata($this->cart);

        $internal_metadata_additional = [
            'checkout' => 'custom',
            'checkout_type' => 'pix',
        ];

        return array_merge($internal_metadata_parent, $internal_metadata_additional);
    }

    /**
     * Get additional info
     *
     * @return Array
     */
    private function _getAdditionalInfo()
    {
        $additional_info = array(
            'payer' => $this->getCustomCustomerData($this->cart),
            'shipments' => $this->getShipmentAddress($this->cart),
            'items' =>  $this->getCartItems(
                $this->cart,
                true,
                $this->settings['MERCADOPAGO_PIX_DISCOUNT']
            ),
        );

        return $additional_info;
    }

    /**
     * Get Amount
     *
     * @param Object $cart Purchase details and information
     *
     * @return Number
     */
    private function _getAmount()
    {
        $total = (float) $this->cart->getOrderTotal();
        return $total;
    }
}
