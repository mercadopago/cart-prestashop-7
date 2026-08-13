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

class PseCheckout
{
    public const PSE_CHECKOUT_NAME = 'MERCADOPAGO_PSE_CHECKOUT';

    public const PSE_CHECKOUT_DISCOUNT_NAME = 'MERCADOPAGO_PSE_DISCOUNT';

    public const PAYMENT_METHOD_NAME = 'pse';

    public const CHECKOUT_TYPE = 'custom';

    /**
     * @var MPUseful
     */
    public $utility;

    /**
     * @var string
     */
    public $assetsExtMin;

    public function __construct()
    {
        $this->assetsExtMin = !_PS_MODE_DEV_ ? '.min' : '';
        $this->utility = MPUseful::getInstance();
    }

    /**
     * @param array $paymentMethods From Core P&P Payment Methods API
     * @param array $pluginInfos Some properties about plugin
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getPseTemplateData($paymentMethods, $pluginInfos)
    {
        $site_id = $this->getSiteId();
        $redirect = $pluginInfos['redirect_link'];
        $termsUrl = $this->utility->getTermsAndPoliciesLink($site_id);
        $moduleDir = $pluginInfos['module_dir'];
        $templateData = [
            'payment_method_info' => $this->getPsePaymentMethod($paymentMethods),
            'site_id' => $site_id,
            'version' => MP_VERSION,
            'redirect' => $redirect,
            'module_dir' => $moduleDir,
            'terms_url' => $termsUrl,
            'assets_ext_min' => $this->assetsExtMin,
            'discount' => $this->getDiscount(),
        ];

        return array_merge($templateData, ['module_dir' => $moduleDir]);
    }

    /**
     * Display checkout only MCO
     *
     * @param string $country ex.: MCO, MLB, etc..
     *
     * @return bool
     */
    public function isAvailableToCountry($country)
    {
        return Tools::strtolower($country) == 'mco';
    }

    /**
     * If PSE discount is setted on admin, return the banner
     *
     * @return string
     */
    public function getDiscountBanner()
    {
        $discount = $this->getDiscount();

        if ($discount > 0) {
            return '(' . $discount . '% OFF)';
        }

        return '';
    }

    /**
     * Get site id
     *
     * @return string
     */
    private function getSiteId()
    {
        return Configuration::get('MERCADOPAGO_SITE_ID');
    }

    /**
     * Get discount value
     *
     * @return int
     */
    public function getDiscount()
    {
        $discount = Configuration::get(self::PSE_CHECKOUT_DISCOUNT_NAME);

        return is_numeric($discount) && $discount > 0 ? $discount : 0;
    }

    /**
     * Only filter PSE payment method
     *
     * @param array $paymentMethods from Core P&P Payment Methods API
     *
     * @return array
     */
    private function getPsePaymentMethod($paymentMethods)
    {
        if (empty($paymentMethods)) {
            return [];
        }

        $checkoutPaymentMethods = array_filter($paymentMethods, function ($paymentMethod) {
            return Tools::strtolower($paymentMethod['id']) == 'pse';
        });

        if (empty($checkoutPaymentMethods)) {
            return [];
        }

        $psePaymentMethod = array_shift($checkoutPaymentMethods);

        return [
            'id' => $psePaymentMethod['id'],
            'name' => $psePaymentMethod['name'],
            'person_types' => $this->getPersonTypes(),
            'financial_institutions' => $psePaymentMethod['financial_institutions'],
            'allowed_identification_types' => $psePaymentMethod['allowed_identification_types'],
        ];
    }

    /**
     * Person types allowed in PSE (Equivalent to PF and PJ in Brazil)
     *
     * @return array
     */
    private function getPersonTypes()
    {
        return [
            [
                'id' => 'individual',
                'name' => 'Individual',
            ],
            [
                'id' => 'association',
                'name' => 'Association',
            ],
        ];
    }
}
