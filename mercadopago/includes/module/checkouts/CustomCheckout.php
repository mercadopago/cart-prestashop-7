<?php
/**
 * 2007-2021 PrestaShop
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
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

class CustomCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    /**
     * Custom Checkout constructor.
     *
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->assets_ext_min = !_PS_MODE_DEV_ ? '.min' : '';
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
        $debit = array();
        $credit = array();
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

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $strDiscount = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'custom');
        $public_key = $this->payment->mercadopago->getPublicKey();

        $shipping = (float) $cart->getOrderTotal(true, 5);
        $products = (float) $cart->getOrderTotal(true, 4);
        $cartTotal = (float) $cart->getOrderTotal();

        $discount = $products * ((float) $strDiscount / 100);
        $products = ($discount != 0) ? $products - $discount : $products;

        $subtotal = $products + $shipping;
        $difference = $cartTotal - $subtotal - $discount;
        $amount = $subtotal + $difference;

        $checkoutInfo = array(
            "debit" => $debit,
            "credit" => $credit,
            "amount" => $amount,
            "site_id" => $site_id,
            "version" => MP_VERSION,
            "redirect" => $redirect,
            "discount" => $strDiscount,
            "public_key" => $public_key,
            "assets_ext_min" => $this->assets_ext_min,
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
