<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 * @author    MercadoPago
 * @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */
class CustomCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    /**
     * StandardCheckout constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @param $cart
     * @return array
     */
    public function getCustomCheckoutPS16($cart)
    {
        $checkoutInfo = $this->getCustomCheckout($cart);
        $frontInformations = array_merge($checkoutInfo,
            array("mp_logo" => _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png'));
        return $frontInformations;

    }

    /**
     * @param $cart
     * @return array
     */
    public function getCustomCheckoutPS17($cart)
    {
        $checkoutInfo = $this->getCustomCheckout($cart);
        $frontInformations = array_merge($checkoutInfo, array("module_dir" => $this->payment->path));
        return $frontInformations;
    }

    /**
     * @param $cart
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
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'custom');
        $public_key = $this->payment->mercadopago->getPublicKey();
        $discount = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $amount = (float)$cart->getOrderTotal();
        $amount = ($discount != "") ? $amount - ($amount * ($discount / 100)) : $amount;

        $checkoutInfo = array(
            "debit" => $debit,
            "credit" => $credit,
            "amount" => $amount,
            "site_id" => $site_id,
            "redirect" => $redirect,
            "public_key" => $public_key,
        );

        return $checkoutInfo;
    }

    /**
     *
     */
    public function loadJsCustom()
    {
        $this->payment->context->controller->addJS($this->payment->path . '/views/js/custom-card.js');
    }

}