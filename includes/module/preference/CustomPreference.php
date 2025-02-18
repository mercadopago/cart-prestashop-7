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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/includes/module/preference/AbstractPreference.php';

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
     * @return bool
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

        if (!empty($custom_info['issuer'])) {
            $preference['issuer_id'] = (int) $custom_info['issuer'];
        }

        $preference['additional_info']['items'] = $this->getCartItems(
            $cart,
            true,
            $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT']
        );

        //Update cart total with CartRule()
        $this->setCartRule($cart, $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT']);
        $preference['transaction_amount'] = $this->getTransactionAmount($cart);

        //Generate preference
        $this->generateLogs($preference, 'custom');
            
        //Create preference
        $createPreference = $this->mercadopago->createPayment($preference);
        MPLog::generate('Cart id ' . $cart->id . ' - Custom Preference created successfully');

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
            return Tools::ps_round($total, 2);
        }

        return $total;
    }

    /**
     * Set custom discount on CartRule()
     *
     * @param mixed $cart
     * @return void
     */
    public function setCartRule($cart, $discount)
    {
        if ($discount != "") {
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
        if ($this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] != "") {
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
        if ($this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] != "") {
            parent::deleteCartRule();
        }
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
