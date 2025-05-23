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
require_once MP_ROOT_URL . '/includes/module/checkouts/PseCheckout.php';

class PsePreference extends AbstractPreference
{
    /**
     * @var PseCheckout
    */
    public $pseCheckout;

    /**
     * @var object
    */
    public $cart;

    public function __construct($pseCheckout, $cart)
    {
        parent::__construct();

        $this->pseCheckout = $pseCheckout;
        $this->cart = $cart;
        $this->checkout = $pseCheckout::CHECKOUT_TYPE;
    }

    /**
     * @param array $payerData from checkout screen
     * @param string $callbackPath
     * 
     * @return array
     */
    public function createPayment($payerData, $callbackPath)
    {
        $payload = $this->buildPayload($payerData, $callbackPath);

        $this->applyDiscountByCart($this->cart);

        $payload['transaction_amount'] = $this->getAmount();

        $this->generateLogs($payload, $this->pseCheckout::PAYMENT_METHOD_NAME);

        $createdPayment = $this->mercadopago->createPayment($payload);
        MPLog::generate('Cart ID ' . $this->cart->id . ' - PSE payment created successfully');

        return $createdPayment;
    }

    /**
     * @return void
     */
    private function applyDiscountByCart($cart)
    {
        $discount = $this->pseCheckout->getDiscount();

        if ($this->pseCheckout->getDiscount()) {
            parent::setCartRule($cart, $discount);
            MPLog::generate(
                'Mercado Pago custom discount applied to cart ' . $cart->id
            );
        }
    }

    /**
     * @return void
     */
    public function deactivateDiscount()
    {
        if ($this->pseCheckout->getDiscount()) {
            parent::disableCartRule();
        }
    }

    /**
     * @return void
     */
    public function removeDiscount()
    {
        if ($this->pseCheckout->getDiscount()) {
            parent::deleteCartRule();
        }
    }

    /**
     * @param array $payerData
     * @param string $callbackPath
     * 
     * @return array
     */
    public function buildPayload($payerData, $callbackPath)
    {
        $payloadParent = $this->getCommonPreference($this->cart);
        $buildedPayerObject = $this->buildPayerObject($payerData);

        $payloadAdditional = [
            'notification_url' => $this->getNotificationUrl($this->cart) . '&topic=payment' . '&method=pse',
            'callback_url' => Context::getContext()->link->getPageLink("order-confirmation", true) . $callbackPath,
            'description' => $this->getPreferenceDescription($this->cart),
            'payment_method_id' => $this->pseCheckout::PAYMENT_METHOD_NAME,
            'payer' => $buildedPayerObject,
            'metadata' => $this->buildMetadataObject($this->cart),
            'additional_info' => $this->buildAdditionalInfoObject($buildedPayerObject),
            'transaction_details' => array(
                'financial_institution' => $payerData['financial_institution']
            )
        ];

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * @return array
     */
    private function buildPayerObject($payerData)
    {
        $customerFields = Context::getContext()->customer->getFields();
        $payerInfoFromCart = new Address((int) $this->cart->id_address_invoice);

        $customerData = array(
            'email' => $customerFields['email'],
            'first_name' => $customerFields['firstname'],
            'last_name' => $customerFields['lastname'],
            'entity_type' => $payerData['entity_type'],
            'identification' => array(
                'type' => $payerData['document_type'],
                'number' => $payerData['document_number'],
            ),
            'phone' => array(
                'area_code' => '-',
                'number' => $payerInfoFromCart->phone,
            ),
            'address' => array(
                'zip_code' => $payerInfoFromCart->postcode,
                'street_name' => $payerInfoFromCart->address1 . ' - ' .
                    $payerInfoFromCart->address2 . ' - ' .
                    $payerInfoFromCart->city . ' - ' .
                    $payerInfoFromCart->country,
                'street_number' => '',
                'city' => $payerInfoFromCart->city,
                'federal_unit' => '',
            )
        );

        return $customerData;
    }

    /**
     * @return array
     */
    private function buildMetadataObject($cart)
    {
        $internalMetadataParent = parent::getInternalMetadata($cart);

        $internalMetadataAdditional = [
            'checkout' => $this->pseCheckout::CHECKOUT_TYPE,
            'checkout_type' => $this->pseCheckout::PAYMENT_METHOD_NAME,
        ];

        return array_merge($internalMetadataParent, $internalMetadataAdditional);
    }

    /**
     * @param array $payer builded from buildPayerObject
     * 
     * @return array
     */
    private function buildAdditionalInfoObject($payer)
    {
        $isCustomCheckout = $this->pseCheckout::CHECKOUT_TYPE === 'custom';

        $additionalInfo = array(
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'payer' => $payer,
            'shipments' => $this->getShipmentAddress($this->cart),
            'items' =>  $this->getCartItems(
                $this->cart,
                $isCustomCheckout,
                $this->pseCheckout->getDiscount()
            ),
        );

        return $additionalInfo;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->cart->getOrderTotal();
    }
}
