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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';

abstract class AbstractPreference
{
    public $module;
    public $checkout;
    public $settings;
    public $mpuseful;
    public $cart_rule;
    public $mercadopago;
    public $ps_cart_rule;
    public $ps_cart_rule_rule;

    /**
     * AbstractPreference constructor.
     */
    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->settings = $this->getMercadoPagoSettings();
        $this->mpuseful = MPUseful::getInstance();
        $this->mercadopago = MPApi::getInstance();
        $this->ps_cart_rule = new PSCartRule();
        $this->ps_cart_rule_rule = new PSCartRuleRule();
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

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
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
            exit($this->module->l('This payment method is not available.'));
        }
    }

    /**
     * @param $cart
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCommonPreference($cart)
    {
        $preference = [
            'external_reference' => $cart->id,
            'notification_url' => $this->getNotificationUrl($cart),
            'statement_descriptor' => $this->getStatementDescriptor(),
        ];

        if (!$this->mercadopago->isTestUser()) {
            $preference['sponsor_id'] = $this->getSponsorId();
        }

        return $preference;
    }

    /**
     * Get all cart items
     *
     * @param $cart
     * @param bool $custom
     * @param int|float|null $percent
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCartItems($cart, $custom = false, $percent = null)
    {
        $items = [];
        $products = $cart->getProducts();

        // Verify country for round
        $round = $this->mpuseful->getRound();

        // Products
        foreach ($products as $product) {
            /** @var array|false $image */
            $image = Image::getCover($product['id_product']);
            $image_id = is_array($image) ? (string) $image['id_image'] : '';
            $image_product = new Product($product['id_product'], false, Context::getContext()->language->id);

            $link = new Link();
            $link_image = $link->getImageLink($image_product->link_rewrite, $image_id, '');

            $product_price = $product['price_wt'];
            if ($percent != null) {
                $product_price = (float) $product_price - ($product_price * ($percent / 100));
            }

            $item = [
                'id' => $product['id_product'],
                'title' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => $round ? Tools::ps_round($product_price) : $product_price,
                'picture_url' => 'https://' . $link_image,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => strip_tags($product['description_short']),
            ];

            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $items[] = $item;
        }

        // Wrapping cost
        $wrapping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        if ($wrapping_cost > 0) {
            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $item = [
                'title' => 'Wrapping',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round($wrapping_cost) : $wrapping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Wrapping service used by store',
            ];

            $items[] = $item;
        }

        // Discounts
        $discounts = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discounts > 0) {
            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $item = [
                'title' => 'Discount',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round(-$discounts) : -$discounts,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Discount provided by store',
            ];

            $items[] = $item;
        }

        // Shipping cost
        $shipping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $item = [
                'title' => 'Shipping',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round($shipping_cost) : $shipping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Shipping service used by store',
            ];

            $items[] = $item;
        }

        // Check has price difference
        $cartTotal = $round ? Tools::ps_round($cart->getOrderTotal(true)) : $cart->getOrderTotal();
        $itemsTotal = array_reduce(
            $items,
            function ($accumulator, $item) {
                $accumulator += $item['unit_price'] * $item['quantity'];

                return $accumulator;
            }
        );

        $itemsTotal = $round ? Tools::ps_round($itemsTotal) : Tools::ps_round($itemsTotal, 2);
        $priceDiff = $cartTotal - $itemsTotal;

        if ($priceDiff > 0) {
            $items[] = [
                'title' => 'Difference',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round($priceDiff) : $priceDiff,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Adjustment for the Mercado Pago price to be the same as the store',
            ];
        }

        return $items;
    }

    public function getStatementDescriptor()
    {
        if ($this->settings['MERCADOPAGO_INVOICE_NAME'] == null) {
            return '';
        }

        return $this->settings['MERCADOPAGO_INVOICE_NAME'];
    }

    /**
     * Get notification url
     *
     * @param $cart
     *
     * @return string|null
     */
    public function getNotificationUrl($cart)
    {
        $customer = new Customer((int) $cart->id_customer);

        // The configured shop domain is the only trusted source for this outbound URL. Using
        // Tools::getHttpHost() here would let a forged Host header suppress notifications, while
        // Tools::getShopDomainSsl() would fall back to that same header when configuration is absent.
        $domain = $this->getValidatedShopAuthority(ShopUrl::getMainShopDomainSSL());
        $host = $domain === null ? null : parse_url('https://' . $domain, PHP_URL_HOST);

        if ($domain === null || !is_string($host) || strcasecmp($host, 'localhost') === 0) {
            return null;
        }

        $notification_url = 'https://' . Tools::htmlentitiesutf8($domain) . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=notification&' .
            'checkout=' . $this->checkout . '&customer=' . $customer->secure_key .
            '&notification=ipn&&source_news=ipn';

        return $notification_url;
    }

    /**
     * Get site url
     *
     * Scheme: resolved via `Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()`,
     * the same combined check PrestaShop core itself uses in `Tools::getShopProtocol()` (PPSP-1574,
     * verified against PrestaShop 8.2.7 and 9.0.3 source) — see git history on this method for the
     * two narrower attempts that preceded it.
     *
     * Domain: resolved directly from the current shop's configured `ShopUrl` instead of
     * `Tools::getShopDomainSsl()` or `$_SERVER['HTTP_HOST']` (PPSP-1892, CWE-601). The helper falls
     * back to `Tools::getHttpHost()` when no domain is configured, which would reintroduce the
     * client-controlled Host header into the `callback_url`. HTTPS uses `domain_ssl`; HTTP uses
     * `domain`, and a missing selected domain fails closed instead of consulting request headers.
     *
     * `getNotificationUrl()` follows the same trust boundary independently: it reads the configured
     * SSL domain directly so a request header cannot suppress or redirect payment notifications.
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getSiteUrl()
    {
        $useSsl = Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode();
        $scheme = $useSsl ? 'https://' : 'http://';
        $domain = $useSsl ? ShopUrl::getMainShopDomainSSL() : ShopUrl::getMainShopDomain();
        $domain = $this->getValidatedShopAuthority($domain);

        if ($domain === null) {
            throw new UnexpectedValueException('Unable to build callback URL: shop domain is not configured or valid.');
        }

        $url = Tools::htmlentitiesutf8($scheme . $domain . __PS_BASE_URI__);

        return $url;
    }

    /**
     * Get return url
     *
     * `back_urls` (PPSP-1892, CWE-601): Mercado Pago redirects the customer's browser here
     * after checkout, so this cannot fall back to `Tools::getShopDomainSsl()`/the request's
     * `Host` header the way it did before — see `getSiteUrl()` for the same trust boundary.
     *
     * @param mixed $cart
     * @param string $typeReturn
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getReturnUrl($cart, $typeReturn)
    {
        $return_url = $this->getConfiguredSslShopUrl() . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=standardvalidation&' .
            'checkout=standard&cart_id=' . $cart->id . '&typeReturn=' . $typeReturn;

        return $return_url;
    }

    /**
     * Resolve the shop's configured HTTPS URL directly from `ShopUrl`, never from request
     * headers, failing closed when no domain is configured (PPSP-1892, CWE-601).
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    private function getConfiguredSslShopUrl()
    {
        $domain = $this->getValidatedShopAuthority(ShopUrl::getMainShopDomainSSL());

        if ($domain === null) {
            throw new UnexpectedValueException('Unable to resolve shop domain: SSL domain is not configured or valid.');
        }

        return 'https://' . Tools::htmlentitiesutf8($domain);
    }

    /**
     * Validate a configured shop domain as a hostname with an optional numeric port.
     *
     * @param mixed $domain
     *
     * @return string|null
     */
    private function getValidatedShopAuthority($domain)
    {
        $authority = trim((string) $domain);
        $parts = $authority === '' ? false : parse_url('https://' . $authority);

        if (!is_array($parts)
            || !isset($parts['host'])
            || filter_var($parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['path'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] === 0)
        ) {
            return null;
        }

        $expectedAuthority = $parts['host'];
        if (isset($parts['port'])) {
            $expectedAuthority .= ':' . $parts['port'];
        }

        return $authority === $expectedAuthority ? $authority : null;
    }

    /**
     * Get sponsor_id for preference
     *
     * @return mixed
     */
    public function getSponsorId()
    {
        $sponsor_id = $this->mpuseful->getCountryConfigs($this->settings['MERCADOPAGO_SITE_ID']);

        return $sponsor_id;
    }

    /**
     * Get customer email
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getCustomerEmail()
    {
        $customer_fields = Context::getContext()->customer->getFields();
        $customer_email = $customer_fields['email'];

        return $customer_email;
    }

    /**
     * Get customer data for custom checkout
     *
     * @return array|null
     *
     * @throws PrestaShopException
     */
    public function getCustomCustomerData($cart)
    {
        $customer = Context::getContext()->customer;
        if (!(empty($customer->firstname) && empty($customer->lastname))) {
            $customer_fields = $customer->getFields();
            $address_invoice = new Address((int) $cart->id_address_invoice);

            $customer_data = [
                'first_name' => $customer_fields['firstname'],
                'last_name' => $customer_fields['lastname'],
                'phone' => [
                    'area_code' => '-',
                    'number' => $address_invoice->phone,
                ],
                'address' => [
                    'zip_code' => $address_invoice->postcode,
                    'street_name' => $this->buildStreetName($address_invoice),
                    'street_number' => '-',
                ],
            ];

            return $customer_data;
        }

        return null;
    }

    /**
     * Get shippment address
     *
     * @return array
     */
    public function getShipmentAddress($cart)
    {
        $address_shipment = new Address((int) $cart->id_address_delivery);

        $shipment = [
            'receiver_address' => [
                'zip_code' => $address_shipment->postcode,
                'street_name' => $this->buildStreetName($address_shipment),
                'street_number' => '-',
                'apartment' => '-',
                'floor' => '-',
                'city_name' => $address_shipment->city,
            ],
        ];

        return $shipment;
    }

    /**
     * Get items description
     *
     * @return array|string
     */
    public function getPreferenceDescription($cart)
    {
        $items = [];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $items[] = $product['name'] . ' x ' . $product['quantity'];
        }

        $items = implode(', ', $items);

        return $items;
    }

    /**
     * Create the array for medatada informations
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $address_invoice = new Address((int) $cart->id_address_invoice);
        $customer_fields = Context::getContext()->customer->getFields();
        $is_logged = Context::getContext()->customer->isLogged();

        $internal_metadata = [
            'details' => '',
            'platform' => MPRestCli::PLATFORM_ID,
            'platform_version' => _PS_VERSION_,
            'module_version' => MP_VERSION,
            'sponsor_id' => $this->getSponsorId(),
            'collector' => $this->settings['MERCADOPAGO_SELLER_ID'],
            'test_mode' => $this->validateSandboxMode(),
            'site' => $this->settings['MERCADOPAGO_SITE_ID'],
            'basic_settings' => $this->getStandardCheckoutSettings(),
            'custom_settings' => $this->getCustomCheckoutSettings(),
            'ticket_settings' => $this->getTicketCheckoutSettings(),
            'pix_settings' => $this->getPixCheckoutSettings(),
            'seller_website' => $this->getConfiguredSslShopUrl(),
            'billing_address' => [
                'zip_code' => $address_invoice->postcode,
                'street_name' => $address_invoice->address1 . ' - ' . $address_invoice->address2,
                'street_number' => '-',
                'city_name' => $address_invoice->city,
                'country_name' => $address_invoice->country,
            ],
            'user' => [
                'registered_user' => $is_logged ? 'yes' : 'no',
                'user_email' => $is_logged ? $customer_fields['email'] : ' ',
                'user_registration_date' => $is_logged ? $customer_fields['date_add'] : ' ',
            ],
        ];

        return $internal_metadata;
    }

    /**
     * Save payments primary info on mp_transaction table
     *
     * @param mixed $cart
     * @param mixed $notification_url
     *
     * @return void
     */
    public function saveCreatePreferenceData($cart, $notification_url)
    {
        $mp_module = $this->getOrUpdateMpModule();
        $mp_transaction = new MPTransaction();
        $count = $mp_transaction->where('cart_id', '=', $cart->id)->count();

        if ($count == 0) {
            $mp_transaction->create(
                [
                    'total' => $cart->getOrderTotal(),
                    'cart_id' => $cart->id,
                    'customer_id' => $cart->id_customer,
                    'mp_module_id' => $mp_module['id_mp_module'],
                    'notification_url' => $notification_url,
                    'is_payment_test' => $this->validateSandboxMode(),
                ]
            );
        } else {
            $mp_transaction->where('cart_id', '=', $cart->id)->update(
                [
                    'total' => $cart->getOrderTotal(),
                    'customer_id' => $cart->id_customer,
                    'notification_url' => $notification_url,
                    'is_payment_test' => $this->validateSandboxMode(),
                ]
            );
        }
    }

    /**
     * Get mp module id to save mp transactions
     *
     * @return MPModule
     */
    public function getOrUpdateMpModule()
    {
        $count = (new MPModule())->where('version', '=', MP_VERSION)->count();
        if ($count) {
            return (new MPModule())->where('version', '=', MP_VERSION)->get();
        }

        $old_mp = (new MPModule())->orderBy('id_mp_module', 'desc')->get();
        $old_mp = (new MPModule())->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(['updated' => true]);

        (new MPModule())->create(['version' => MP_VERSION]);

        return (new MPModule())->where('version', '=', MP_VERSION)->get();
    }

    /**
     * Get validate sandbox mode
     *
     * @return bool
     */
    public function validateSandboxMode()
    {
        if ($this->settings['MERCADOPAGO_PROD_STATUS'] == true) {
            return false;
        }

        return true;
    }

    /**
     * Create and set ticket discount on CartRule()
     *
     * @param mixed $cart
     * @param $discount
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setCartRule($cart, $discount)
    {
        $mp_code = 'MPDISCOUNT' . $cart->id;
        $store_name = Configuration::get('PS_LANG_DEFAULT');
        $discount_name = $this->module->l('Mercado Pago discount applied to cart ' . $cart->id);

        $cart_rule = new CartRule();
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y') + 10));
        $cart_rule->name[$store_name] = $discount_name;
        $cart_rule->quantity = 1;
        $cart_rule->code = $mp_code;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->reduction_percent = $discount;
        $cart_rule->reduction_amount = 0;
        $cart_rule->active = true;
        $cart_rule->save();

        $cart->addCartRule($cart_rule->id);

        $this->cart_rule = $cart_rule->id;
    }

    /**
     * Disable cart rule when buyer completes purchase
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function disableCartRule()
    {
        $cart_rule = new CartRule($this->cart_rule);
        $cart_rule->active = false;
        $cart_rule->save();
    }

    /**
     * Delete cart rule if an error occurs
     *
     * @return bool
     */
    public function deleteCartRule()
    {
        $result_cart_rule = $this->ps_cart_rule->where('id_cart_rule', '=', $this->cart_rule)->destroy();
        $result_cart_rule_rule = $this->ps_cart_rule_rule->where('id_cart_rule', '=', $this->cart_rule)->destroy();

        if ($result_cart_rule == false || $result_cart_rule_rule == false) {
            $this->disableCartRule();
            MPLog::generate('Failed to delete cart_rule from database', 'error');

            return false;
        }

        return true;
    }

    /**
     * Redirect if any errors occurs
     *
     * @return void
     */
    public function redirectError()
    {
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }

    /**
     * Get plugin settings on database
     *
     * @return mixed
     */
    public function getMercadoPagoSettings()
    {
        // localization
        $this->settings['MERCADOPAGO_SITE_ID'] = Configuration::get('MERCADOPAGO_SITE_ID');
        $this->settings['MERCADOPAGO_SELLER_ID'] = Configuration::get('MERCADOPAGO_SELLER_ID');
        $this->settings['MERCADOPAGO_COUNTRY_LINK'] = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        // credentials
        $this->settings['MERCADOPAGO_PROD_STATUS'] = Configuration::get('MERCADOPAGO_PROD_STATUS');
        $this->settings['MERCADOPAGO_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $this->settings['MERCADOPAGO_SANDBOX_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_SANDBOX_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        // store info
        $this->settings['MERCADOPAGO_INVOICE_NAME'] = Configuration::get('MERCADOPAGO_INVOICE_NAME');
        $this->settings['MERCADOPAGO_INTEGRATOR_ID'] = Configuration::get('MERCADOPAGO_INTEGRATOR_ID');
        $this->settings['MERCADOPAGO_STORE_CATEGORY'] = Configuration::get('MERCADOPAGO_STORE_CATEGORY');

        // standard checkout
        $this->settings['MERCADOPAGO_AUTO_RETURN'] = Configuration::get('MERCADOPAGO_AUTO_RETURN');
        $this->settings['MERCADOPAGO_INSTALLMENTS'] = Configuration::get('MERCADOPAGO_INSTALLMENTS');
        $this->settings['MERCADOPAGO_STANDARD_MODAL'] = Configuration::get('MERCADOPAGO_STANDARD_MODAL');
        $this->settings['MERCADOPAGO_STANDARD_CHECKOUT'] = Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT');
        $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] = Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO');
        $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] = Configuration::get('MERCADOPAGO_STANDARD_BINARY_MODE');

        // custom checkout
        $this->settings['MERCADOPAGO_CUSTOM_CHECKOUT'] = Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT');
        $this->settings['MERCADOPAGO_CUSTOM_WALLET_BUTTON'] = Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON');
        $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] = Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE');

        // ticket checkout
        $this->settings['MERCADOPAGO_TICKET_CHECKOUT'] = Configuration::get('MERCADOPAGO_TICKET_CHECKOUT');
        $this->settings['MERCADOPAGO_TICKET_DISCOUNT'] = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] = Configuration::get('MERCADOPAGO_TICKET_EXPIRATION');

        // pix checkout
        $this->settings['MERCADOPAGO_PIX_CHECKOUT'] = Configuration::get('MERCADOPAGO_PIX_CHECKOUT');
        $this->settings['MERCADOPAGO_PIX_DISCOUNT'] = Configuration::get('MERCADOPAGO_PIX_DISCOUNT');
        $this->settings['MERCADOPAGO_PIX_EXPIRATION'] = Configuration::get('MERCADOPAGO_PIX_EXPIRATION');

        // pse checkout
        $this->settings[PseCheckout::PSE_CHECKOUT_NAME] = Configuration::get(PseCheckout::PSE_CHECKOUT_NAME);
        $this->settings[PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME] = Configuration::get(PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME);

        return $this->settings;
    }

    /**
     * Get standard checkout settings for metadata
     *
     * @return array
     */
    public function getStandardCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_STANDARD_CHECKOUT'] == '' ? false : true;
        $settings['modal'] = $this->settings['MERCADOPAGO_STANDARD_MODAL'] == '' ? false : true;
        $settings['auto_return'] = $this->settings['MERCADOPAGO_AUTO_RETURN'] == '' ? false : true;
        $settings['binary_mode'] = $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] == '' ? false : true;
        $settings['installments'] = $this->settings['MERCADOPAGO_INSTALLMENTS'];
        $settings['expiration_date_to'] = $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'];

        return $settings;
    }

    /**
     * Get custom checkout settings for metadata
     *
     * @return array
     */
    public function getCustomCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_CUSTOM_CHECKOUT'] == '' ? false : true;
        $settings['wallet_button'] = $this->settings['MERCADOPAGO_CUSTOM_WALLET_BUTTON'] == '' ? false : true;
        $settings['discount'] = (float) $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'];
        $settings['binary_mode'] = $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] == '' ? false : true;

        return $settings;
    }

    /**
     * Get ticket checkout settings for metadata
     *
     * @return array
     */
    public function getTicketCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_TICKET_CHECKOUT'] == '' ? false : true;
        $settings['discount'] = (float) $this->settings['MERCADOPAGO_TICKET_DISCOUNT'];
        $settings['expiration_date_to'] = $this->settings['MERCADOPAGO_TICKET_EXPIRATION'];

        return $settings;
    }

    /**
     * Get pix checkout settings for metadata
     *
     * @return array
     */
    public function getPixCheckoutSettings()
    {
        $settings = [
            'active' => !($this->settings['MERCADOPAGO_PIX_CHECKOUT'] == ''),
            'discount' => (float) $this->settings['MERCADOPAGO_PIX_DISCOUNT'],
            'expiration_date_to' => $this->settings['MERCADOPAGO_PIX_EXPIRATION'],
        ];

        return $settings;
    }

    /**
     * Generate preference logs
     *
     * @param array $preference
     * @param mixed $checkout
     *
     * @return void
     */
    public function generateLogs($preference, $checkout)
    {
        $logs = [
            'cart_id' => $preference['external_reference'],
            'cart_total' => $preference['transaction_amount'],
            'payment_method' => $preference['payment_method_id'],
            'cart_items' => $preference['additional_info']['items'],
            'metadata' => array_diff_key($preference['metadata'], array_flip(['collector'])),
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate($checkout . ' preference logs: ' . $encodedLogs);
    }

    /**
     * build street name
     *
     * @param object $address_data
     *
     * @return string
     */
    public function buildStreetName($address_data)
    {
        $address = $address_data->address1 . ' - ' .
        $address_data->address2 . ' - ' .
        $address_data->city . ' - ' .
        $address_data->country;

        return $address;
    }
}
