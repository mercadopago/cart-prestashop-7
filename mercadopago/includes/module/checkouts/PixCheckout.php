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
     * Get Pix Checkout PS 16
     *
     *
     * @return Array
     *
     * @throws PrestaShopException
     */
    public function getPixCheckoutPS16()
    {
        $pixTemplateVariables = $this->getPixTemplateVariables();

        $frontInformations = array_merge(
            $pixTemplateVariables,
            array('module_dir' => $this->payment->path),
            array(
                'mp_logo' => _MODULE_DIR_
                . 'mercadopago/views/img/mpinfo_checkout.png'
            )
        );

        return $frontInformations;
    }

    /**
     * Get Pix Checkout PS 17
     *
     *
     * @return Array
     *
     * @throws PrestaShopException
     */
    public function getPixCheckoutPS17()
    {
        $pixTemplateVariables = $this->getPixTemplateVariables();

        $frontInformations = array_merge(
            $pixTemplateVariables,
            array('module_dir' => $this->payment->path)
        );

        return $frontInformations;
    }

    /**
     * Get Pix Template Variables
     *
     * @return Array
     *
     * @throws PrestaShopException
     */
    public function getPixTemplateVariables()
    {
        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');

        $variables = array(
            'logo_pix' => "{$this->payment->path}views/img/logo_pix.png",
            'badge_info_gray' => "{$this->payment->path}views/img/icons/badge_info_gray.png",
            'discount' => Configuration::get('MERCADOPAGO_PIX_DISCOUNT'),
            'terms_url' => $this->mpuseful->getTermsAndPoliciesLink($site_id),
            'redirect' => $this->payment->context->link->getModuleLink($this->payment->name, 'pix'),
        );

        return $variables;
    }
}
