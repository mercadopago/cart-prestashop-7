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

class CustomPreference extends AbstractPreference
{
    public function __construct()
    {
        parent::__construct();
        $this->checkout = 'custom';
    }

    /**
     * @param $cart
     * @param $custom_info
     *
     * @return array|string
     *
     * @throws Exception
     */
    public function createPreference($cart, $custom_info)
    {
        $preference = $this->getCommonPreference($cart);
        $preference['description'] = $this->getPreferenceDescription($cart);
        $preference['binary_mode'] = $this->getBinaryMode();
        $preference['payer']['email'] = $this->getCustomerEmail();
        $preference['additional_info']['payer'] = $this->getCustomCustomerData($cart);
        $preference['additional_info']['shipments'] = $this->getShipmentAddress($cart);
        $preference['metadata'] = $this->getInternalMetadata($cart);
        $preference['token'] = $custom_info['card_token_id'];
        $preference['installments'] = (int) $custom_info['installments'];
        $preference['payment_method_id'] = $custom_info['payment_method_id'];
        $preference['payer']['identification']['type'] = $custom_info['doc_type'];
        $preference['payer']['identification']['number'] = $custom_info['doc_number'];

        if (!empty($custom_info['issuer'])) {
            $preference['issuer_id'] = (int) $custom_info['issuer'];
        }

        $preference['additional_info']['items'] = $this->getCartItems(
            $cart,
            true,
            $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT']
        );

        // Update cart total with CartRule()
        $this->setCartRule($cart, $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT']);
        $preference['transaction_amount'] = $this->getTransactionAmount($cart);

        // Generate preference
        $this->generateLogs($preference, 'custom');

        // Create preference
        $createPreference = $this->mercadopago->createPayment($preference);
        MPLog::generate('Cart id ' . $cart->id . ' - Custom Preference created successfully');

        return $createPreference;
    }

    /**
     * Get transaction amount
     *
     * @param mixed $cart
     *
     * @return float
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
     * Set custom discount on CartRule()
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function setCartRule($cart, $discount)
    {
        if ($discount != '') {
            parent::setCartRule($cart, $discount);
            MPLog::generate('Mercado Pago custom discount applied to cart ' . $cart->id);
        }
    }

    /**
     * Disable cart rule when buyer completes purchase
     *
     * @return void
     */
    public function disableCartRule()
    {
        if ($this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] != '') {
            parent::disableCartRule();
        }
    }

    /**
     * Delete cart rule if an error occurs
     *
     * @return bool
     */
    public function deleteCartRule()
    {
        if ($this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] != '') {
            return parent::deleteCartRule();
        }

        return true;
    }

    /**
     * Get binary_mode for preference
     *
     * @return mixed
     */
    public function getBinaryMode()
    {
        if ($this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] == 1) {
            return $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] = true;
        }

        return $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] = false;
    }

    /**
     * Get internal metadata
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $internal_metadata = parent::getInternalMetadata($cart);
        $internal_metadata['checkout'] = 'custom';
        $internal_metadata['checkout_type'] = 'credit_card';

        return $internal_metadata;
    }
}
