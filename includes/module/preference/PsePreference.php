<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/AbstractPreference.php';
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';

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
            'notification_url' => $this->getPseNotificationUrl(),
            'callback_url' => Context::getContext()->link->getPageLink('order-confirmation', true) . $callbackPath,
            'description' => $this->getPreferenceDescription($this->cart),
            'payment_method_id' => $this->pseCheckout::PAYMENT_METHOD_NAME,
            'payer' => $buildedPayerObject,
            'metadata' => $this->buildMetadataObject($this->cart),
            'additional_info' => $this->buildAdditionalInfoObject($buildedPayerObject),
            'transaction_details' => [
                'financial_institution' => $payerData['financial_institution'],
            ],
        ];

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * getNotificationUrl() returns null when the shop domain is not safely configured
     * (PPSP-1892, CWE-601 fail-closed guard). Concatenating the PSE query suffix onto that
     * would silently send Mercado Pago a bare "&topic=payment&method=pse" as the webhook
     * target, so this fails closed too instead of building an invalid URL (PPSP-1892).
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    protected function getPseNotificationUrl()
    {
        $notificationUrl = $this->getNotificationUrl($this->cart);

        if ($notificationUrl === null || $notificationUrl === '') {
            throw new UnexpectedValueException('Unable to build PSE notification URL: shop domain is not configured.');
        }

        return $notificationUrl . '&topic=payment&method=pse';
    }

    /**
     * @return array
     */
    private function buildPayerObject($payerData)
    {
        $customerFields = Context::getContext()->customer->getFields();
        $payerInfoFromCart = new Address((int) $this->cart->id_address_invoice);

        $customerData = [
            'email' => $customerFields['email'],
            'first_name' => $customerFields['firstname'],
            'last_name' => $customerFields['lastname'],
            'entity_type' => $payerData['entity_type'],
            'identification' => [
                'type' => $payerData['document_type'],
                'number' => $payerData['document_number'],
            ],
            'phone' => [
                'area_code' => '-',
                'number' => $payerInfoFromCart->phone,
            ],
            'address' => [
                'zip_code' => $payerInfoFromCart->postcode,
                'street_name' => $payerInfoFromCart->address1 . ' - ' .
                    $payerInfoFromCart->address2 . ' - ' .
                    $payerInfoFromCart->city . ' - ' .
                    $payerInfoFromCart->country,
                'street_number' => '',
                'city' => $payerInfoFromCart->city,
                'federal_unit' => '',
            ],
        ];

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

        $additionalInfo = [
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'payer' => $payer,
            'shipments' => $this->getShipmentAddress($this->cart),
            'items' => $this->getCartItems(
                $this->cart,
                $isCustomCheckout,
                $this->pseCheckout->getDiscount()
            ),
        ];

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
