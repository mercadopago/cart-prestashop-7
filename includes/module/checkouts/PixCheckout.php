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

class PixCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    /**
     * @var MPUseful
     */
    public $mpuseful;

    /**
     * Pix Checkout constructor.
     *
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * Get Pix Checkout PS 17
     *
     * @return array
     */
    public function getPixCheckoutPS17()
    {
        $pixTemplateVariables = $this->getPixTemplateVariables();

        $frontInformations = array_merge(
            $pixTemplateVariables,
            ['module_dir' => $this->payment->path]
        );

        return $frontInformations;
    }

    /**
     * Get Pix Template Variables
     *
     * @return array
     */
    public function getPixTemplateVariables()
    {
        $siteId = Configuration::get('MERCADOPAGO_SITE_ID');

        $variables = [
            'site_id' => $siteId,
            'module_dir' => $this->payment->path,
            'discount' => Configuration::get('MERCADOPAGO_PIX_DISCOUNT'),
            'terms_url' => $this->mpuseful->getTermsAndPoliciesLink($siteId),
            'redirect' => $this->payment->context->link->getModuleLink($this->payment->name, 'pix'),
        ];

        return $variables;
    }
}
