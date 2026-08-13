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

class TicketCheckout
{
    public const ALLOW_PAYMENT_METHOD_TYPES = ['ticket', 'atm'];

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
     * Ticket Checkout constructor.
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
     *
     * @throws PrestaShopException
     */
    public function getTicketCheckoutPS17($cart)
    {
        $checkoutInfo = $this->getTicketCheckout($cart);
        $frontInformations = array_merge($checkoutInfo, ['module_dir' => $this->payment->path]);

        return $frontInformations;
    }

    /**
     * @param $cart
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getTicketCheckout($cart)
    {
        $this->getTicketJS();
        $ticket = [];
        $paymentMethods = $this->payment->mercadopago->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            if (Configuration::get('MERCADOPAGO_TICKET_PAYMENT_' . $paymentMethod['id']) != '') {
                if (in_array($paymentMethod['type'], self::ALLOW_PAYMENT_METHOD_TYPES)
                     && Tools::strtolower($paymentMethod['id']) != 'meliplace'
                ) {
                    $ticket[] = $paymentMethod;
                }
            }
        }

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $address = new Address((int) $cart->id_address_invoice);
        $context = Context::getContext();
        $discount = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'ticket');

        $info = [
            'ticket' => $ticket,
            'site_id' => $site_id,
            'address' => $address,
            'version' => MP_VERSION,
            'context' => $context,
            'redirect' => $redirect,
            'discount' => $discount,
            'module_dir' => $this->payment->path,
            'assets_ext_min' => $this->assets_ext_min,
            'terms_url' => $this->mpuseful->getTermsAndPoliciesLink($site_id),
        ];

        return $info;
    }

    /**
     * Get ticket JS
     */
    public function getTicketJS()
    {
        $assets_ext_min = !_PS_MODE_DEV_ ? '.min' : '';
        $this->payment->context->controller->addJS(
            $this->payment->path . '/views/js/ticket' . $assets_ext_min . '.js?v=' . MP_VERSION
        );
    }
}
