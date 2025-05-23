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
     * @return array
     */
    public function createPreference()
    {
        $payload = $this->buildPixPreferencePayload();

        $this->setCartRule($this->cart, $this->settings['MERCADOPAGO_PIX_DISCOUNT']);
        $payload['transaction_amount'] = $this->getAmount();

        $this->generateLogs($payload, 'pix');

        $createPreference = $this->mercadopago->createPayment($payload);
        MPLog::generate('Cart ID ' . $this->cart->id . ' - Pix Preference created successfully');

        return $createPreference;
    }

    /**
     * Set custom discount on CartRule()
     *
     * @return void
     */
    public function setCartRule($cart, $discount)
    {
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
        if ($this->settings['MERCADOPAGO_PIX_DISCOUNT'] != '') {
            parent::deleteCartRule();
        }
    }

    /**
     * To build payload from Pix payment
     *
     * @return array
     */
    public function buildPixPreferencePayload()
    {
        $payloadParent = $this->getCommonPreference($this->cart);

        $payloadAdditional = [
            'date_of_expiration' => $this->getExpirationDate(),
            'description' => $this->getPreferenceDescription($this->cart),
            'payment_method_id' => 'pix',
            'payer' => $this->getCustomerData(),
            'metadata' => $this->getInternalMetadata($this->cart),
            'additional_info' => $this->getAdditionalInfo(),
            'point_of_interaction' => [
                'type' => 'CHECKOUT',
            ]
        ];

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * Get expiration_date_to for preference
     *
     * @return mixed
     */
    public function getExpirationDate()
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
     * @return array
     */
    public function getCustomerData()
    {
        $customerFields = Context::getContext()->customer->getFields();
        $addressInvoice = new Address((int) $this->cart->id_address_invoice);

        $customerData = array(
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

        return $customerData;
    }

    /**
     * Get internal metadata
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $internalMetadataParent = parent::getInternalMetadata($cart);

        $internalMetadataAdditional = [
            'checkout' => 'custom',
            'checkout_type' => 'pix',
        ];

        return array_merge($internalMetadataParent, $internalMetadataAdditional);
    }

    /**
     * Get additional info
     *
     * @return array
     */
    public function getAdditionalInfo()
    {
        $additionalInfo = array(
            'payer' => $this->getCustomCustomerData($this->cart),
            'shipments' => $this->getShipmentAddress($this->cart),
            'items' =>  $this->getCartItems(
                $this->cart,
                true,
                $this->settings['MERCADOPAGO_PIX_DISCOUNT']
            ),
        );

        return $additionalInfo;
    }

    /**
     * Get Amount
     *
     * @return float
     */
    public function getAmount()
    {
        $total = (float) $this->cart->getOrderTotal();
        return $total;
    }
}
