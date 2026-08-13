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

class StandardCheckout
{
    public const METHOD_CREDIT_CARD = 1;
    public const METHOD_DEBIT_CARD = 2;
    public const METHOD_TICKET = 3;

    /**
     * @var Mercadopago
     */
    public $payment;

    public $mpuseful;

    /**
     * Standard Checkout constructor.
     *
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * @param $cart
     *
     * @return array
     */
    public function getStandardCheckoutPS17($cart)
    {
        $informations = $this->getStandard($cart);
        $frontInformations = array_merge($informations, ['module_dir' => $this->payment->path]);

        return $frontInformations;
    }

    /**
     * @param $cart
     *
     * @return array
     */
    public function getStandard($cart)
    {
        $count = 0;
        $tarjetas = [];
        $paymentMethods = $this->payment->mercadopago->getPaymentMethods();

        $uniqueIds = [];
        $filteredPaymentsMethods = [];

        foreach ($paymentMethods as $item) {
            if (!in_array($item['id'], $uniqueIds)) {
                $uniqueIds[] = $item['id'];
                $item['sort'] = (int) $item['sort'];
                $filteredPaymentsMethods[] = $item;
            }
        }

        usort($filteredPaymentsMethods, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        foreach ($filteredPaymentsMethods as $paymentMethod) {
            if ($count >= 7) {
                break;
            }

            if (Configuration::get($paymentMethod['config']) != '' && (int) $paymentMethod['sort'] !== 999) {
                $tarjetas[] = $paymentMethod;
                ++$count;
            }
        }

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $modal = Configuration::get('MERCADOPAGO_STANDARD_MODAL');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'standard');

        $informations = [
            'tarjetas' => $tarjetas,
            'count' => $count,
            'modal' => $modal,
            'redirect' => $redirect,
            'site_id' => $site_id,
            'public_key' => $this->payment->mercadopago->getPublicKey(),
            'terms_url' => $this->mpuseful->getTermsAndPoliciesLink($site_id),
            'standardIcons' => $this->mpuseful->getIconsDetails($site_id),
        ];

        return $informations;
    }
}
