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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/AbstractStandardPreference.php';

class StandardPreference extends AbstractStandardPreference
{
    public function __construct()
    {
        parent::__construct();
        $this->checkout = 'standard';
    }

    /**
     * Create standard preference
     *
     * @param $cart
     *
     * @return mixed
     */
    public function createPreference($cart)
    {
        $payload = $this->buildPreferencePayload($cart);

        $this->generateLogs($payload, $cart);

        $createPreference = $this->mercadopago->createPreference($payload);
        MPLog::generate('Cart id ' . $cart->id . ' - Standard Preference created successfully');

        return $createPreference;
    }

    /**
     * To build payload from standard payment
     *
     * @param $cart
     *
     * @return array
     */
    public function buildPreferencePayload($cart, $discount = 0)
    {
        $payloadParent = parent::buildPreferencePayload($cart);

        $payloadAdditional = [
            'metadata' => $this->getInternalMetadata($cart),
        ];

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * Get internal metadata
     *
     * @param $cart
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $internalMetadataParent = parent::getInternalMetadata($cart);

        $checkoutType = $this->settings['MERCADOPAGO_STANDARD_MODAL'] ? 'modal' : 'redirect';

        $internalMetadataAdditional = [
            'checkout' => 'pro',
            'checkout_type' => $checkoutType,
        ];

        return array_merge($internalMetadataParent, $internalMetadataAdditional);
    }

    /**
     * Generate preference logs
     *
     * @param $preference
     * @param $cart
     *
     * @return void
     */
    public function generateLogs($preference, $cart)
    {
        $logs = [
            'cart_id' => $preference['external_reference'],
            'cart_total' => $cart->getOrderTotal(),
            'cart_items' => $preference['items'],
            'metadata' => array_diff_key($preference['metadata'], array_flip(['collector'])),
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate('standard preference logs: ' . $encodedLogs);
    }
}
