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

class CustomCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    public $mpuseful;

    /**
     * @var string
     */
    public $assets_ext_min;

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
     * @param $cart
     *
     * @return array
     */
    public function getCustomCheckoutPS17($cart)
    {
        $checkoutInfo = $this->getCustomCheckout($cart);
        $frontInformations = array_merge($checkoutInfo, ['module_dir' => $this->payment->path]);

        return $frontInformations;
    }

    /**
     * @param $cart
     *
     * @return array
     */
    public function getCustomCheckout($cart)
    {
        $this->loadJsCustom();

        $debit = [];
        $credit = [];
        $tarjetas = $this->payment->mercadopago->getPaymentMethods();

        foreach ($tarjetas as $tarjeta) {
            if (Configuration::get($tarjeta['config']) != '') {
                if ($tarjeta['type'] == 'credit_card') {
                    $credit[] = $tarjeta;
                } elseif ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                    $debit[] = $tarjeta;
                }
            }
        }

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $walletButton = Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'custom');
        $public_key = $this->payment->mercadopago->getPublicKey();
        $correctedTotal = $this->mpuseful->getCorrectedTotal($cart, 'credit_card');

        $checkoutInfo = [
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $correctedTotal['amount'],
            'site_id' => $site_id,
            'wallet_button' => $walletButton,
            'version' => MP_VERSION,
            'redirect' => $redirect,
            'discount' => $correctedTotal['str_discount'],
            'public_key' => $public_key,
            'assets_ext_min' => $this->assets_ext_min,
            'terms_url' => $this->mpuseful->getTermsAndPoliciesLink($site_id),
        ];

        return $checkoutInfo;
    }

    public function loadJsCustom()
    {
        $this->payment->context->controller->addJS(
            $this->payment->path . '/views/js/custom-card' . $this->assets_ext_min . '.js?v=' . MP_VERSION
        );
    }
}
