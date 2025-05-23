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

class CustomCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    public $mpuseful;

    /**
     * Custom Checkout constructor.
     *
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->assets_ext_min = !_PS_MODE_DEV_ ? '.min' : '';
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * @param  $cart
     * @return array
     */
    public function getCustomCheckoutPS16($cart)
    {
        $checkoutInfo = $this->getCustomCheckout($cart);
        $frontInformations = array_merge(
            $checkoutInfo,
            array("mp_logo" => _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png')
        );
        return $frontInformations;
    }

    /**
     * @param  $cart
     * @return array
     */
    public function getCustomCheckoutPS17($cart)
    {
        $checkoutInfo = $this->getCustomCheckout($cart);
        $frontInformations = array_merge($checkoutInfo, array("module_dir" => $this->payment->path));
        return $frontInformations;
    }

    /**
     * @param  $cart
     * @return array
     */
    public function getCustomCheckout($cart)
    {
        $this->loadJsCustom();

        $debit    = array();
        $credit   = array();
        $tarjetas = $this->payment->mercadopago->getPaymentMethods();

        foreach ($tarjetas as $tarjeta) {
            if (Configuration::get($tarjeta['config']) != "") {
                if ($tarjeta['type'] == 'credit_card') {
                    $credit[] = $tarjeta;
                } elseif ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                    $debit[] = $tarjeta;
                }
            }
        }

        $site_id        = Configuration::get('MERCADOPAGO_SITE_ID');
        $walletButton   = Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON');
        $redirect       = $this->payment->context->link->getModuleLink($this->payment->name, 'custom');
        $public_key     = $this->payment->mercadopago->getPublicKey();
        $correctedTotal = $this->mpuseful->getCorrectedTotal($cart, 'credit_card');

        $checkoutInfo = array(
            "debit"          => $debit,
            "credit"         => $credit,
            "amount"         => $correctedTotal['amount'],
            "site_id"        => $site_id,
            "wallet_button"  => $walletButton,
            "version"        => MP_VERSION,
            "redirect"       => $redirect,
            "discount"       => $correctedTotal['str_discount'],
            "public_key"     => $public_key,
            "assets_ext_min" => $this->assets_ext_min,
            "terms_url"      => $this->mpuseful->getTermsAndPoliciesLink($site_id),
        );

        return $checkoutInfo;
    }

    /**
     *
     */
    public function loadJsCustom()
    {
        $this->payment->context->controller->addJS(
            $this->payment->path . '/views/js/custom-card' . $this->assets_ext_min . '.js?v=' . MP_VERSION
        );
    }
}
