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

class MercadoPagoStandardModuleFrontController extends ModuleFrontController
{
    protected $mercadopago;
    
    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
        $this->mpuseful = MPUseful::getInstance();
    }
    
    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        $cart_id = $cart->id;
        
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'mercadopago') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->module->l('Este método de pago no está disponible.'));
        }
        
        $preferenceParams = $this->getPreferencesStandard();
        $createPreference = $this->mercadopago->createPreference($preferenceParams);
        
        //save data in mercadopago table
        $mp_module = new MPModule();
        $mp_module = $mp_module->where('version', '=', MP_VERSION)->get();
        $payment_test = Configuration::get('MERCADOPAGO_SANDBOX_STATUS');
        
        $mp_transaction = new MPTransaction();
        $count = $mp_transaction->where('cart_id', '=', $cart_id)->count();
        
        if ($count == 0) {
            $mp_transaction->create([
                'total' => $cart->getOrderTotal(),
                'cart_id' => $cart_id,
                'customer_id' => $cart->id_customer,
                'notification_url' => $createPreference['notification_url'],
                'is_payment_test' => $payment_test,
                'mp_module_id' => $mp_module['id_mp_module']
            ]);
        } else {
            $mp_transaction->where('cart_id', '=', $cart_id)->update([
                'total' => $cart->getOrderTotal(),
                'customer_id' => $cart->id_customer,
                'notification_url' => $createPreference['notification_url'],
                'is_payment_test' => $payment_test
            ]);
        }
        
        //success redirect link for sandbox and production mode
        if (array_key_exists('init_point', $createPreference)) {
            MPLog::generate('Cart id '.$cart_id.' - Preference created successfully');
            return Tools::redirectLink($createPreference['init_point']);
        }
        
        //failure redirect link
        return $this->redirectError();
    }
    
    /**
     * mercado pago preferences for standard checkout
     *
     * @return void
     */
    private function getPreferencesStandard()
    {
        $cart = $this->context->cart;
        $customer = new Customer((int) $cart->id_customer);
        $customer_fields = Context::getContext()->customer->getFields();
        $mercadopagoSettings = $this->getMercadoPagoSettings();
        
        // get costumer data
        $address_invoice = new Address((integer) $cart->id_address_invoice);
        $phone = $address_invoice->phone;
        $customer_data = array(
            'name' => $customer_fields['firstname'],
            'surname' => $customer_fields['lastname'],
            'email' => $customer_fields['email'],
            'phone' => array(
                'area_code' => '',
                'number' => $phone,
            ),
            'identification' => array(
                'number' => '',
                'type' => '',
            ),
            'address' => array(
                'zip_code' => $address_invoice->postcode,
                'street_name' => $address_invoice->address1 .' - '.
                                 $address_invoice->address2 .' - '.
                                 $address_invoice->city .' - '.
                                 $address_invoice->country,
                'street_number' => '',
            ),
            'date_created' => date("c", strtotime($customer_fields['date_add'])),
        );
        
        // get cart items
        $items = array();
        $products = $cart->getProducts();

        //verify country for round
        $round = false;
        $localization = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        if ($localization == 'mco' || $localization == 'mlc') {
            $round = true;
        }
        
        foreach ($products as $product) {
            $image = Image::getCover($product['id_product']);
            $product_image = new Product($product['id_product'], false, Context::getContext()->language->id);
            $link = new Link();
            $imagePath = $link->getImageLink($product_image->link_rewrite, $image['id_image'], "");

            $item = array(
                'id' => $product['id_product'],
                'title' => $product['name'],
                'description' => strip_tags($product['description_short']),
                'picture_url' => ('https://' ? 'https://' : 'http://').$imagePath,
                'category_id' => $mercadopagoSettings['category_id'],
                'quantity' => $product['quantity'],
                "currency_id" => $this->context->currency->iso_code,
                'unit_price' => $round ? round($product['price_wt']) : $product['price_wt'],
            );
            
            $items[] = $item;
        }
        
        // include wrapping cost
        $wrapping_cost = (integer) $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        if ($wrapping_cost > 0) {
            $item = array(
                'title' => 'Wrapping',
                'description' => 'Wrapping service used by store',
                'category_id' => $mercadopagoSettings['category_id'],
                'quantity' => 1,
                'currency_id' => $this->context->currency->iso_code,
                'unit_price' => $round ? round($wrapping_cost) : $wrapping_cost,
            );
            $items[] = $item;
        }
        
        // include discounts
        $discounts = (double) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discounts > 0) {
            $item = array(
                'title' => 'Discount',
                'description' => 'Discount provided by store',
                'category_id' => $mercadopagoSettings['category_id'],
                'quantity' => 1,
                'unit_price' => $round ? round(-$discounts) : -$discounts,
            );
            $items[] = $item;
        }
        
        // include shipping cost
        $shipping_cost = (double) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            $item = array(
                'title' => 'Shipping',
                'description' => 'Shipping service used by store',
                'category_id' => $mercadopagoSettings['category_id'],
                'quantity' => 1,
                'unit_price' => $round ? round($shipping_cost) : $shipping_cost,
            );
            $items[] = $item;
        }
        
        //create json data
        $data = array(
            'external_reference' => $cart->id,
            'binary_mode' => $mercadopagoSettings['binary_mode'],
            'statement_descriptor' => $mercadopagoSettings['statement_descriptor'],
            'items' => $items,
            'payer' => $customer_data,
            'payment_methods' => array(
                'excluded_payment_methods' => $this->getExcludedPaymentMethods(),
                'excluded_payment_types' => array(),
                'installments' => (integer) $mercadopagoSettings['installments'],
            ),
            'shipments' => array(
                'mode'=> 'not_specified'
            ),
            'back_urls'=> array(
                'success' => $this->getURLReturn($cart->id, 'success'),
                'failure' => $this->getURLReturn($cart->id, 'failure'),
                'pending' => $this->getURLReturn($cart->id, 'pending'),
            ),
            'auto_return' => $mercadopagoSettings['auto_return'],
            'expires' => $mercadopagoSettings['expires'],
            'expiration_date_to' => $mercadopagoSettings['expiration_date_to'],
        );
        
        if (!strrpos($this->getURLSite(), 'localhost')) {
            $data['notification_url'] = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.
                '?fc=module&module=mercadopago&controller=notification&'.
                'checkout=standard&cart_id='.$cart->id.'&customer='.$customer->secure_key.
                '&notification=ipn';
            
            if (!$this->mercadopago->isTestUser()) {
                $data['sponsor_id'] = $mercadopagoSettings['sponsor_id'];
            }
        }
        
        return Tools::jsonEncode($data);
    }
    
    /**
     * Get url return
     *
     * @param [type] $cart_id
     * @param [type] $typeReturn
     * @return void
     */
    private function getURLReturn($cart_id, $typeReturn)
    {
        $statusUrl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.
            '?fc=module&module=mercadopago&controller=validationstandard&'.
            'checkout=standard&cart_id='.$cart_id.'&typeReturn='.$typeReturn;
        
        return $statusUrl;
    }
    
    /**
     * Get url site
     *
     * @return void
     */
    private function getURLSite()
    {
        $url = Tools::htmlentitiesutf8(('https://' ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].__PS_BASE_URI__);
        return $url;
    }
    
    /**
     * Mercado pago info settings
     *
     * @return void
     */
    private function getMercadoPagoSettings()
    {
        $mercadoPagoSettings = array();
        $mercadoPagoSettings['accessToken'] = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $mercadoPagoSettings['standard_active'] = Configuration::get('MERCADOPAGO_CHECKOUT_STATUS');
        $mercadoPagoSettings['site_id'] = Configuration::get('MERCADOPAGO_SITE_ID');
        $mercadoPagoSettings['category_id'] = Configuration::get('MERCADOPAGO_STORE_CATEGORY');
        $mercadoPagoSettings['installments'] = Configuration::get('MERCADOPAGO_INSTALLMENTS');
        $mercadoPagoSettings['statement_descriptor'] = Configuration::get('MERCADOPAGO_INVOICE_NAME');
        
        $mercadoPagoSettings['sponsor_id'] = $this->mpuseful->getCountryConfigs($mercadoPagoSettings['site_id']);
        if (Configuration::get('MERCADOPAGO_SPONSOR_ID') != "") {
            $mercadoPagoSettings['sponsor_id'] = (integer) Configuration::get('MERCADOPAGO_SPONSOR_ID');
        }
        
        $mercadoPagoSettings['auto_return'] = Configuration::get('MERCADOPAGO_AUTO_RETURN');
        if ($mercadoPagoSettings['auto_return'] == 1) {
            $mercadoPagoSettings['auto_return'] = 'approved';
        }
        
        $mercadoPagoSettings['binary_mode'] = false;
        if (Configuration::get('MERCADOPAGO_BINARY_MODE') == 1) {
            $mercadoPagoSettings['binary_mode'] = true;
        }
        
        $mercadoPagoSettings['expires'] = false;
        $mercadoPagoSettings['expiration_date_to'] = Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO');
        if ($mercadoPagoSettings['expiration_date_to'] != "") {
            $mercadoPagoSettings['expires'] = true;
            $mercadoPagoSettings['expiration_date_to'] = date(
                'Y-m-d\TH:i:s.000O',
                strtotime('+'.$mercadoPagoSettings['expiration_date_to'].' hours')
            );
        }
        
        return $mercadoPagoSettings;
    }
    
    /**
     * Mercado pago excluded payments methods
     *
     * @return void
     */
    private function getExcludedPaymentMethods()
    {
        $payment_methods = $this->mercadopago->getPaymentMethods();
        $excluded_payment_methods = array();

        foreach ($payment_methods as $payment_method) {
            $pm_variable_name = 'MERCADOPAGO_PAYMENT_' . Tools::strtoupper($payment_method['id']);
            $value = Configuration::get($pm_variable_name);

            if ($value != "on") {
                $excluded_payment_methods[] = array(
                    'id' => Tools::strtolower($payment_method['id']),
                );
            }
        }
        return $excluded_payment_methods;
    }
    
    //redirect error
    protected function redirectError()
    {
        Tools::redirect('index.php?controller=order&step=1&step=3&typeReturn=failure');
    }
}
