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

class AbstractPreference extends ModuleFrontController
{
    public $mpuseful;
    public $mercadopago;

    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * Verify if module is available
     *
     * @return void
     */
    public function verifyModuleParameters()
    {
        $cart = $this->context->cart;
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
     * Get customer data
     *
     * @return array
     */
    public function getCustomerData()
    {
        $cart = $this->context->cart;
        $customer_fields = Context::getContext()->customer->getFields();
        $address_invoice = new Address((int) $cart->id_address_invoice);

        $customer_data = array(
            'name' => $customer_fields['firstname'],
            'surname' => $customer_fields['lastname'],
            'email' => $customer_fields['email'],
            'phone' => array(
                'area_code' => '',
                'number' => $address_invoice->phone,
            ),
            'identification' => array(
                'type' => '',
                'number' => '',
            ),
            'address' => array(
                'zip_code' => $address_invoice->postcode,
                'street_name' => $address_invoice->address1 . ' - ' .
                    $address_invoice->address2 . ' - ' .
                    $address_invoice->city . ' - ' .
                    $address_invoice->country,
                'street_number' => '',
            ),
            'date_created' => date("c", strtotime($customer_fields['date_add'])),
        );

        return $customer_data;
    }

    /**
     * Get all cart items
     *
     * @return array
     */
    public function getCartItems()
    {
        $items = array();
        $cart = $this->context->cart;
        $products = $cart->getProducts();
        $mp_category = Configuration::get('MERCADOPAGO_STORE_CATEGORY');

        //Products
        foreach ($products as $product) {
            $image = Image::getCover($product['id_product']);
            $image_product = new Product($product['id_product'], false, Context::getContext()->language->id);

            $link = new Link();
            $link_image = $link->getImageLink($image_product->link_rewrite, $image['id_image'], "");

            $item = array(
                'id' => $product['id_product'],
                'title' => $product['name'],
                'description' => strip_tags($product['description_short']),
                'picture_url' => ('https://' ? 'https://' : 'http://') . $link_image,
                'category_id' => $mp_category,
                'quantity' => $product['quantity'],
                "currency_id" => $this->context->currency->iso_code,
                'unit_price' => $product['price_wt'],
            );

            $items[] = $item;
        }

        //Wrapping cost
        $wrapping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        if ($wrapping_cost > 0) {
            $item = array(
                'title' => 'Wrapping',
                'description' => 'Wrapping service used by store',
                'category_id' => $mp_category,
                'quantity' => 1,
                'currency_id' => $this->context->currency->iso_code,
                'unit_price' => $wrapping_cost,
            );
            $items[] = $item;
        }

        //Discounts
        $discounts = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discounts > 0) {
            $item = array(
                'title' => 'Discount',
                'description' => 'Discount provided by store',
                'category_id' => $mp_category,
                'quantity' => 1,
                'unit_price' => -$discounts,
            );
            $items[] = $item;
        }

        //Shipping cost
        $shipping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            $item = array(
                'title' => 'Shipping',
                'description' => 'Shipping service used by store',
                'category_id' => $mp_category,
                'quantity' => 1,
                'unit_price' => $shipping_cost,
            );
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Create preference json
     *
     * @return array
     */
    public function createJson()
    {
        $cart = $this->context->cart;
        $customer = new Customer((int) $cart->id_customer);
        $mp_settings = array();

        $json = array(
            'items' => $this->getCartItems(),
            'payer' => $this->getCustomerData(),
            'external_reference' => $cart->id,

            'binary_mode' => $mp_settings['binary_mode'],
            'statement_descriptor' => $mp_settings['statement_descriptor'],

            'payment_methods' => array(
                'excluded_payment_methods' => $this->getExcludedPaymentMethods(),
                'excluded_payment_types' => array(),
                'installments' => (integer) $mp_settings['installments'],
            ),

            'shipments' => array(
                'mode'=> 'not_specified'
            ),

            'back_urls'=> array(
                'success' => $this->getURLReturn($cart->id, 'success'),
                'failure' => $this->getURLReturn($cart->id, 'failure'),
                'pending' => $this->getURLReturn($cart->id, 'pending'),
            ),

            'auto_return' => $mp_settings['auto_return'],
            'expires' => $mp_settings['expires'],
            'expiration_date_to' => $mp_settings['expiration_date_to'],
        );
        
        if (!strrpos($this->getURLSite(), 'localhost')) {
            $json['notification_url'] = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.
                '?fc=module&module=mercadopago&controller=notification&'.
                'checkout=standard&cart_id='.$cart->id.'&customer='.$customer->secure_key.
                '&notification=ipn';
            
            if (!$this->mercadopago->isTestUser()) {
                $json['sponsor_id'] = $mp_settings['sponsor_id'];
            }
        }
        
        return Tools::jsonEncode($json);
    }
}
