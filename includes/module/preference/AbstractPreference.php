<?php

/**
 * 2007-2018 PrestaShop.
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
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

class AbstractPreference
{
    public $module;
    public $settings;
    public $mpuseful;
    public $mercadopago;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->settings = $this->getMercadoPagoSettings();
        $this->mpuseful = MPUseful::getInstance();
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Verify if module is available
     *
     * @return void
     */
    public function verifyModuleParameters()
    {
        $cart = $this->module->context->cart;
        $authorized = false;

        if (
            $cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'mercadopago') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('Este método de pago no está disponible.'));
        }
    }

    /**
     * Return the common fields in preference
     *
     * @return void
     */
    public function getCommonPreference($cart)
    {
        $preference = array(
            'external_reference' => $cart->id,
            'notification_url' => $this->getNotificationUrl($cart),
            'statement_descriptor' => $this->settings['MERCADOPAGO_INVOICE_NAME'],
        );

        if (!$this->mercadopago->isTestUser()) {
            $preference['sponsor_id'] = $this->getSponsorId();
        }

        return $preference;
    }

    /**
     * Get all cart items
     *
     * @return array
     */
    public function getCartItems($cart)
    {
        $items = array();
        $products = $cart->getProducts();

        //Products
        foreach ($products as $product) {
            $image = Image::getCover($product['id_product']);
            $image_product = new Product($product['id_product'], false, Context::getContext()->language->id);

            $link = new Link();
            $link_image = $link->getImageLink($image_product->link_rewrite, $image['id_image'], "");

            $item = array(
                'id' => $product['id_product'],
                'title' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price_wt'],
                'picture_url' => ('https://' ? 'https://' : 'http://') . $link_image,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                "currency_id" => $this->module->context->currency->iso_code,
                'description' => strip_tags($product['description_short']),
            );

            $items[] = $item;
        }

        //Wrapping cost
        $wrapping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        if ($wrapping_cost > 0) {
            $item = array(
                'title' => 'Wrapping',
                'quantity' => 1,
                'unit_price' => $wrapping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'currency_id' => $this->module->context->currency->iso_code,
                'description' => 'Wrapping service used by store',
            );
            $items[] = $item;
        }

        //Discounts
        $discounts = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discounts > 0) {
            $item = array(
                'title' => 'Discount',
                'quantity' => 1,
                'unit_price' => -$discounts,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Discount provided by store',
            );
            $items[] = $item;
        }

        //Shipping cost
        $shipping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            $item = array(
                'title' => 'Shipping',
                'quantity' => 1,
                'unit_price' => $shipping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Shipping service used by store',
            );
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Get notification url
     *
     * @return void
     */
    public function getNotificationUrl($cart)
    {
        $customer = new Customer((int) $cart->id_customer);

        if (!strrpos($this->getSiteUrl(), 'localhost')) {
            $notifification_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
                '?fc=module&module=mercadopago&controller=notification&' .
                'checkout=standard&cart_id=' . $cart->id . '&customer=' . $customer->secure_key .
                '&notification=ipn';

            return $notifification_url;
        }
    }

    /**
     * Get site url
     *
     * @return void
     */
    public function getSiteUrl()
    {
        $url = Tools::htmlentitiesutf8(('https://' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__);

        return $url;
    }

    /**
     * Get return url
     *
     * @param mixed $cart
     * @param string $typeReturn
     * @return string
     */
    public function getReturnUrl($cart, $typeReturn)
    {
        $return_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=validationstandard&' .
            'checkout=standard&cart_id=' . $cart->id . '&typeReturn=' . $typeReturn;

        return $return_url;
    }

    /**
     * Get sponsor_id for preference
     *
     * @return mixed
     */
    public function getSponsorId()
    {
        $sponsor_id = $this->mpuseful->getCountryConfigs($this->settings['MERCADOPAGO_SITE_ID']);

        if ($this->settings['MERCADOPAGO_SPONSOR_ID'] == "") {
            return $this->settings['MERCADOPAGO_SPONSOR_ID'] = $sponsor_id;
        }
    }

    /**
     * Get Mercado Pago settings
     *
     * @return void
     */
    public function getMercadoPagoSettings()
    {
        //localization
        $this->settings['MERCADOPAGO_SITE_ID'] = Configuration::get('MERCADOPAGO_SITE_ID');
        $this->settings['MERCADOPAGO_COUNTRY_LINK'] = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        //credentials
        $this->settings['MERCADOPAGO_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $this->settings['MERCADOPAGO_SANDBOX_STATUS'] = Configuration::get('MERCADOPAGO_SANDBOX_STATUS');
        $this->settings['MERCADOPAGO_SANDBOX_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_SANDBOX_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        //store info
        $this->settings['MERCADOPAGO_SPONSOR_ID'] = Configuration::get('MERCADOPAGO_SPONSOR_ID');
        $this->settings['MERCADOPAGO_INVOICE_NAME'] = Configuration::get('MERCADOPAGO_INVOICE_NAME');
        $this->settings['MERCADOPAGO_STORE_CATEGORY'] = Configuration::get('MERCADOPAGO_STORE_CATEGORY');

        //standard checkout
        $this->settings['MERCADOPAGO_AUTO_RETURN'] = Configuration::get('MERCADOPAGO_AUTO_RETURN');
        $this->settings['MERCADOPAGO_INSTALLMENTS'] = Configuration::get('MERCADOPAGO_INSTALLMENTS');
        $this->settings['MERCADOPAGO_STANDARD_MODAL'] = Configuration::get('MERCADOPAGO_STANDARD_MODAL');
        $this->settings['MERCADOPAGO_STANDARD_CHECKOUT'] = Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT');
        $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] = Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO');
        $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] = Configuration::get('MERCADOPAGO_STANDARD_BINARY_MODE');

        //custom checkout
        $this->settings['MERCADOPAGO_CUSTOM_COUPON'] = Configuration::get('MERCADOPAGO_CUSTOM_COUPON');
        $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $this->settings['MERCADOPAGO_CUSTOM_CHECKOUT'] = Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT');
        $this->settings['MERCADOPAGO_CUSTOM_COMISSION'] = Configuration::get('MERCADOPAGO_CUSTOM_COMISSION');
        $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] = Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE');

        //ticket checkout
        $this->settings['MERCADOPAGO_TICKET_COUPON'] = Configuration::get('MERCADOPAGO_TICKET_COUPON');
        $this->settings['MERCADOPAGO_TICKET_CHECKOUT'] = Configuration::get('MERCADOPAGO_TICKET_CHECKOUT');
        $this->settings['MERCADOPAGO_TICKET_INVENTORY'] = Configuration::get('MERCADOPAGO_TICKET_INVENTORY');
        $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] = Configuration::get('MERCADOPAGO_TICKET_EXPIRATION');

        return $this->settings;
    }

    /**
     * Redirect if any errors occurs
     *
     * @return void
     */
    public function redirectError()
    {
        Tools::redirect('index.php?controller=order&step=1&step=3&typeReturn=failure');
    }
}
