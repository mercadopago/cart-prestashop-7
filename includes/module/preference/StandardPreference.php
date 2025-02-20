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

require_once MP_ROOT_URL . '/includes/module/preference/AbstractStandardPreference.php';

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
     * @return array
     */
    public function buildPreferencePayload($cart, $discount = 0)
    {
        $payloadParent = parent::buildPreferencePayload($cart);

        $payloadAdditional = array(
            'metadata' => $this->getInternalMetadata($cart),
        );

        return array_merge($payloadParent, $payloadAdditional);
    }

    /**
     * Get internal metadata
     *
     * @param $cart
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $internalMetadataParent = parent::getInternalMetadata($cart);

        $checkoutType = $this->settings['MERCADOPAGO_STANDARD_MODAL'] ? 'modal' : 'redirect';

        $internalMetadataAdditional = array(
            'checkout' => 'pro',
            'checkout_type' => $checkoutType,
        );

        return array_merge($internalMetadataParent, $internalMetadataAdditional);
    }

    /**
     * Generate preference logs
     *
     * @param $preference
     * @param $cart
     * @return void
     */
    public function generateLogs($preference, $cart)
    {
        $logs = array(
            "cart_id" => $preference['external_reference'],
            "cart_total" => $cart->getOrderTotal(),
            "cart_items" => $preference['items'],
            "metadata" => array_diff_key($preference['metadata'], array_flip(['collector'])),
        );

        $encodedLogs = json_encode($logs);
        MPLog::generate('standard preference logs: ' . $encodedLogs);
    }
}
