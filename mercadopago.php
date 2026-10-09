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
define('MP_VERSION', '4.19.1');

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mercadopago/vendor/autoload.php';

class Mercadopago extends PaymentModule
{
    public $tab;
    public $name;
    public $path;
    public $author;
    public $version;
    public $context;
    public $mpuseful;
    public $bootstrap;
    public $module_key;
    public $mercadopago;
    public $displayName;
    public $description;
    public $need_instance;
    public $assets_ext_min;
    public $customCheckout;
    public $ticketCheckout;
    public $standardCheckout;
    public $pixCheckout;
    public $pseCheckout;
    public $confirmUninstall;
    public $ps_versions_compliancy;
    public $ps_version;
    public static $form_alert;
    public static $form_message;

    public const PRESTA17 = '1.7';

    public function __construct()
    {
        $this->loadFiles();
        $this->mercadopago = MPApi::getInstance();
        $this->mpuseful = MPUseful::getInstance();

        $this->name = 'mercadopago';
        $this->tab = 'payments_gateways';
        $this->author = 'mercadopago';
        $this->need_instance = 1;
        $this->bootstrap = true;

        // Always update, because prestashop doesn't accept version coming from another variable (MP_VERSION)
        $this->version = '4.19.1';
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => '8.2.7'];

        parent::__construct();

        $this->displayName = $this->l('Mercado Pago');
        $this->description = $this->l('Customize the payment experience of your customers in your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
        $this->module_key = '4380f33bbe84e7899aacb0b7a601376f';
        $this->ps_version = _PS_VERSION_;
        $this->assets_ext_min = !_PS_MODE_DEV_ ? '.min' : '';
        $this->path = $this->_path;
        $this->standardCheckout = new StandardCheckout($this);
        $this->customCheckout = new CustomCheckout($this);
        $this->ticketCheckout = new TicketCheckout($this);
        $this->pixCheckout = new PixCheckout($this);
        $this->pseCheckout = new PseCheckout();
    }

    /**
     * Load files
     *
     * @return void
     */
    public function loadFiles()
    {
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPApi.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPLog.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPUseful.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPRestCli.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/StandardPreference.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/WalletButtonPreference.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPModule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPTransaction.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/MPTransaction.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSCartRule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSCartRuleRule.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSOrderState.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/model/PSOrderStateLang.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/StandardCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/CustomCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/TicketCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PixCheckout.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';
    }

    /**
     * Install the module
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension ') .
                $this->l('on your server to install this module.');

            return false;
        }

        // Prestashop configuration table
        $mp_currency = $this->getContextCurrencyIsoCode();
        Configuration::updateValue('MERCADOPAGO_COUNTRY_LINK', $this->mpuseful->setMPCurrency($mp_currency));

        // Validate if is a new seller or a plugin upgrade
        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        if ($access_token != '' && $sandbox_access_token != '') {
            Configuration::updateValue('MERCADOPAGO_STANDARD_CHECKOUT', true);
        }

        // Mercadopago configurations
        include _PS_MODULE_DIR_ . 'mercadopago/sql/install.php';
        MPLog::generate(sprintf('Mercadopago plugin %s installed in the store', MP_VERSION));

        // install hooks and dependencies
        return parent::install()
            && $this->createPaymentStates()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayPaymentReturn')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('displayWrapperTop');
    }

    /**
     * Get the ISO code of the currency currently held in the context.
     *
     * $this->context->currency can be null/non-object when the module runs
     * without a fully resolved store/currency context (e.g. install/upgrade
     * triggered from CLI via `prestashop:module install`), so this method
     * falls back to the shop's default currency instead of reading
     * iso_code directly from a possibly null object.
     *
     * @return string
     */
    protected function getContextCurrencyIsoCode()
    {
        // PHPStan's Context stub types $this->context->currency as non-null, but at
        // CLI install/upgrade time it can actually be null, so the runtime guard stays.
        if (isset($this->context->currency) && is_object($this->context->currency) && !empty($this->context->currency->iso_code)) { // @phpstan-ignore-line
            return $this->context->currency->iso_code;
        }

        $default_currency = Currency::getDefaultCurrency();

        if (is_object($default_currency) && !empty($default_currency->iso_code)) {
            return $default_currency->iso_code;
        }

        $default_currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        return !empty($default_currency->iso_code) ? $default_currency->iso_code : '';
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        MPLog::generate('Mercadopago plugin uninstalled in the store');
        include _PS_MODULE_DIR_ . 'mercadopago/sql/uninstall.php';

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getContent()
    {
        // add css to configuration page
        $this->context->controller->addCSS($this->_path . 'views/css/back' . $this->assets_ext_min . '.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        // test flow
        $mp_transaction = new MPTransaction();
        $count_test = $mp_transaction->where('is_payment_test', '=', 1)->andWhere('received_webhook', '=', 1)->count();

        // return forms
        $store = '';
        $custom = '';
        $ticket = '';
        $standard = '';
        $pix = '';
        $pse = '';
        $this->loadSettings();
        new RatingSettings();

        $localization = new LocalizationSettings();
        $credentials = new CredentialsSettings();
        $homologation = new HomologationSettings();

        $localization = $this->renderForm($localization->submit, $localization->values, $localization->form);
        $credentials = $this->renderForm($credentials->submit, $credentials->values, $credentials->form);
        $homologation = $this->renderForm($homologation->submit, $homologation->values, $homologation->form);

        // variables for admin configuration
        $public_key = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $homologated = Configuration::get('MERCADOPAGO_HOMOLOGATION');
        $country_link = Configuration::get('MERCADOPAGO_COUNTRY_LINK');
        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_public_key = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        $pix_enabled = null;
        $country_id = null;

        if ($access_token != '' && $sandbox_access_token != '') {
            // verify if seller is homologated
            $credentialsWrapper = $this->mercadopago->getCredentialsWrapper($access_token);

            if ($homologated == false && $credentialsWrapper['homologated'] == true) {
                $homologated = Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', true);
            }

            // return checkout forms
            $store = new StoreSettings();
            $standard = new StandardSettings();
            $custom = new CustomSettings();
            $ticket = new TicketSettings();
            $pix = new PixSettings();
            $pse = new PseSettings();

            $store = $this->renderForm($store->submit, $store->values, $store->form);
            $standard = $this->renderForm($standard->submit, $standard->values, $standard->form);
            $custom = $this->renderForm($custom->submit, $custom->values, $custom->form);
            $ticket = $this->renderForm($ticket->submit, $ticket->values, $ticket->form);
            $pix = $this->renderForm($pix->submit, $pix->values, $pix->form);
            $pse = $this->renderForm($pse->submit, $pse->values, $pse->form);

            $pix_enabled = $this->isEnabledPaymentMethod('pix');
            $country_id = $this->getSiteIdByCredentials($access_token);
        }

        $this->context->smarty->assign(
            [
                // module requirements
                'message' => self::$form_message,
                'form_alert' => self::$form_alert,
                'mp_version' => MP_VERSION,
                'url_base' => __PS_BASE_URI__,
                'log' => MPLog::getLogUrl(),
                'country_link' => $country_link,
                'application' => Configuration::get('MERCADOPAGO_APPLICATION_ID'),
                'standard_test' => Configuration::get('MERCADOPAGO_STANDARD'),
                'sandbox_status' => Configuration::get('MERCADOPAGO_PROD_STATUS'),
                'seller_protect_link' => $this->mpuseful->setSellerProtectLink($country_link),
                'psjLink' => $this->mpuseful->getCountryPsjLink($country_link),
                'pix_enabled' => $pix_enabled,
                'country_id' => $country_id,
                // credentials
                'public_key' => $public_key,
                'access_token' => $access_token,
                'sandbox_public_key' => $sandbox_public_key,
                'sandbox_access_token' => $sandbox_access_token,
                // test flow
                'count_test' => $count_test,
                'seller_homolog' => $homologated,
                // forms
                'country_form' => $localization,
                'credentials' => $credentials,
                'homolog_form' => $homologation,
                'store_form' => $store,
                'standard_form' => $standard,
                'custom_form' => $custom,
                'ticket_form' => $ticket,
                'pix_form' => $pix,
                'pse_form' => $pse,
            ]
        );

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Load settings
     *
     * @return void
     */
    public function loadSettings()
    {
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/StoreSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/RatingSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/StandardSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CustomSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/TicketSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/PixSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CredentialsSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/LocalizationSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/HomologationSettings.php';
        include_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/PseSettings.php';
        require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/CoreSdkSettings.php';
    }

    /**
     * Render forms
     *
     * @param $submit
     * @param $values
     * @param $form
     *
     * @return string
     */
    protected function renderForm($submit, $values, $form)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->submit_action = $submit;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /**
     * Create the payment states
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createPaymentStates()
    {
        $order_states = [
            ['#ccfbff', $this->l('Transaction in Process'), 'in_process', '110010000'],
            ['#c9fecd', $this->l('Transaction Completed'), 'payment', '110010010'],
            ['#fec9c9', $this->l('Transaction Canceled'), 'order_canceled', '100010000'],
            ['#fec9c9', $this->l('Transaction Declined'), 'payment_error', '100010000'],
            ['#ffeddb', $this->l('Transaction Refunded'), 'refund', '100010000'],
            ['#c28566', $this->l('Transaction Chargedback'), 'charged_back', '100010000'],
            ['#b280b2', $this->l('Transaction in Mediation'), 'in_mediation', '100010000'],
            ['#fffb96', $this->l('Transaction Pending'), 'pending', '110010000'],
            ['#ccfbff', $this->l('Transaction Authorized'), 'authorized', '100010000'],
            ['#ffb0d9', $this->l('Transaction in Possible Fraud'), 'payment_error', '100010000'],
        ];

        foreach ($order_states as $key => $value) {
            if ($this->orderStateAvailable((int) Configuration::get('MERCADOPAGO_STATUS_' . $key)) == 1) {
                continue;
            }
            $order_state = new OrderState();
            $order_state->name = [];
            $order_state->template = [];
            $order_state->module_name = $this->name;
            $order_state->color = $value[0];
            $order_state->invoice = (bool) $value[3][0];
            $order_state->send_email = (bool) $value[3][1];
            $order_state->unremovable = (bool) $value[3][2];
            $order_state->hidden = (bool) $value[3][3];
            $order_state->logable = (bool) $value[3][4];
            $order_state->delivery = (bool) $value[3][5];
            $order_state->shipped = (bool) $value[3][6];
            $order_state->paid = (bool) $value[3][7];
            $order_state->deleted = (bool) $value[3][8];

            $order_state->name = array_fill(0, 10, $value[1]);
            $order_state->template = array_fill(0, 10, $value[2]);

            if ($order_state->add()) {
                $file = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                copy(dirname(__FILE__) . '/views/img/mp_icon.gif', $file);
                Configuration::updateValue('MERCADOPAGO_STATUS_' . $key, $order_state->id);
            }
        }

        return true;
    }

    /**
     * Check if the state exist before create another one
     *
     * @param int $id_order_state
     *
     * @return int
     */
    public static function orderStateAvailable($id_order_state)
    {
        $query = 'SELECT COUNT(*) AS count_state FROM ' . _DB_PREFIX_ . 'order_state
            WHERE id_order_state = ' . (int) $id_order_state;
        $result = Db::getInstance()->getRow($query);

        return (int) $result['count_state'];
    }

    /**
     * Return null for Mercado Envios
     *
     * @return void
     */
    public function getOrderShippingCost()
    {
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO
     *
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pixFront' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pse' . $this->assets_ext_min . '.css');
        $this->context->controller->addJS($this->_path . 'views/js/front' . $this->assets_ext_min . '.js');
    }

    /**
     * Show payment options in version 1.7
     *
     * @param $params
     *
     * @return array|string|void
     */
    public function hookPaymentOptions($params)
    {
        return $this->loadPayments($params);
    }

    /**
     * @param $params
     *
     * @return array|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function loadPayments($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $paymentOptions = [];

        $country = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        $checkouts = [
            'MERCADOPAGO_STANDARD_CHECKOUT' => 'getStandardCheckout',
            'MERCADOPAGO_CUSTOM_CHECKOUT' => 'getCustomCheckout',
            'MERCADOPAGO_TICKET_CHECKOUT' => 'getTicketCheckout',
            'MERCADOPAGO_PIX_CHECKOUT' => 'getPixCheckout',
            'MERCADOPAGO_PSE_CHECKOUT' => 'getPseCheckout',
        ];

        foreach ($checkouts as $checkout => $method) {
            if ($this->isActiveCheckout($checkout) && $this->isAvailableToCountry($checkout, $country)) {
                $paymentOptions[] = $this->{$method}($cart);
            } else {
                $this->disableCheckout($checkout);
            }
        }

        return $paymentOptions;
    }

    /**
     * @param $checkout
     *
     * @return bool
     */
    public function isActiveCheckout($checkout)
    {
        return Configuration::get($checkout) == true;
    }

    /**
     * @param $checkout
     * @param $country
     *
     * @return bool
     */
    public function isAvailableToCountry($checkout, $country)
    {
        $checkoutsWithCountryRestriction = [
            'MERCADOPAGO_PIX_CHECKOUT',
            PseCheckout::PSE_CHECKOUT_NAME,
        ];

        if (!in_array($checkout, $checkoutsWithCountryRestriction)) {
            return true;
        }

        if ($country === 'mlb'
            && $checkout === 'MERCADOPAGO_PIX_CHECKOUT'
            && $this->isEnabledPaymentMethod('pix')
        ) {
            return true;
        }

        if ($this->pseCheckout->isAvailableToCountry($country)
            && $checkout === PseCheckout::PSE_CHECKOUT_NAME
            && $this->isEnabledPaymentMethod(PseCheckout::PAYMENT_METHOD_NAME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $checkout
     *
     * @return bool
     */
    public function isEnabledPaymentMethod($checkout)
    {
        $paymentMethods = $this->mercadopago->getPaymentMethods();
        if (is_array($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if (Tools::strtolower($paymentMethod['id']) == $checkout) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $accessToken
     *
     * @return string
     */
    public function getSiteIdByCredentials($accessToken)
    {
        $response = $this->mercadopago->isValidAccessToken($accessToken);

        return $response ? Tools::strtolower($response['site_id']) : null;
    }

    /**
     * @param $checkout
     *
     * @return void
     */
    public function disableCheckout($checkout)
    {
        Configuration::updateValue($checkout, false);
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getStandardCheckout($cart)
    {
        $frontInformations = $this->standardCheckout->getStandardCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/standard.tpl');
        $standardCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $standardCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Mercado Pago'))
            ->setLogo('https://http2.mlstatic.com/storage/cpp/static-files/306698cd-ff92-4cc0-801c-1ca35d06ed5a.png');

        return $standardCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getCustomCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $str_discount = ' (' . $discount . '% OFF) ';
        $str_discount = ($discount != '') ? $str_discount : '';

        $frontInformations = $this->customCheckout->getCustomCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/custom.tpl');
        $customCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $customCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Credit or debit card') . $str_discount);

        return $customCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getTicketCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $str_discount = ' (' . $discount . '% OFF) ';
        $str_discount = ($discount != '') ? $str_discount : '';

        $frontInformations = $this->ticketCheckout->getTicketCheckoutPS17($cart);
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/ticket.tpl');
        $ticketCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $ticketCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Pay with payment methods in cash') . $str_discount);

        return $ticketCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getPixCheckout($cart)
    {
        $discount = Configuration::get('MERCADOPAGO_PIX_DISCOUNT');

        $strDiscount = ' (' . $discount . '% OFF) ';
        $strDiscount = ($discount != '') ? $strDiscount : '';

        $frontInformations = $this->pixCheckout->getPixCheckoutPS17();
        $this->context->smarty->assign($frontInformations);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/pix.tpl');
        $pixCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $pixCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Pix') . $strDiscount);

        return $pixCheckout;
    }

    /**
     * @param $cart
     *
     * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption
     */
    public function getPseCheckout($cart)
    {
        $pluginInfos = [
            'redirect_link' => $this->context->link->getModuleLink($this->name, PseCheckout::PAYMENT_METHOD_NAME),
            'module_dir' => $this->path,
        ];
        $paymentMethods = $this->mercadopago->getPaymentMethods();
        $templateData = $this->pseCheckout->getPseTemplateData($paymentMethods, $pluginInfos);
        $this->context->smarty->assign($templateData);
        $infoTemplate = $this->context->smarty->fetch('module:mercadopago/views/templates/hook/seven/pse.tpl');
        $psePaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $psePaymentOption->setForm($infoTemplate)
            ->setCallToActionText($this->l('PSE') . ' ' . $this->pseCheckout->getDiscountBanner())
            ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

        return $psePaymentOption;
    }

    /**
     * Check currency
     *
     * @param mixed $cart
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This hook is used to display the order confirmation page.
     *
     * @param mixed $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        $paymentId = Tools::getValue('payment_id');
        $payment = is_string($paymentId) ? $this->mercadopago->getPaymentStandard($paymentId) : [];

        return $this->getPaymentReturn($payment, $params);
    }

    /**
     * Get template of payment confirmation
     *
     * @param mixed $payment
     * @param mixed $params
     *
     * @return string
     */
    public function getPaymentReturn($payment, $params)
    {
        $order = array_key_exists('objOrder', $params) ? $params['objOrder'] : null;
        $products = !is_null($order) ? $order->getProducts() : null;
        // This hook always runs in a web request with a resolved currency context, but we
        // still use the defensive helper for consistency and as a safety net.
        $mp_currency = $this->getContextCurrencyIsoCode();
        if (isset($payment['transaction_details']['total_paid_amount']) && isset($payment['transaction_amount']) && isset($payment['transaction_details']['installment_amount'])) {
            $cost_of_installments = $payment['transaction_details']['total_paid_amount'] - $payment['transaction_amount'];
            $cost_of_installments_formated = $this->context->currentLocale->formatPrice($cost_of_installments, $mp_currency);
            $total_paid_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['total_paid_amount'], $mp_currency);
            $installment_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['installment_amount'], $mp_currency);
        }

        $this->context->smarty->assign(
            [
                'order' => $order,
                'payment' => $payment,
                'order_products' => $products,
                'pix_expiration' => $this->getPixExpiration(),
                'cost_of_installments' => isset($cost_of_installments) ? $cost_of_installments : null,
                'cost_of_installments_formated' => isset($cost_of_installments_formated) ? $cost_of_installments_formated : null,
                'total_paid_amount' => isset($total_paid_amount) ? $total_paid_amount : null,
                'installment_amount' => isset($installment_amount) ? $installment_amount : null,
            ]
        );

        return $this->display(__FILE__, 'views/templates/hook/seven/payment_return.tpl');
    }

    /**
     * Get pix expiration
     *
     * @return string
     */
    public function getPixExpiration()
    {
        $pixExpiration = Configuration::get('MERCADOPAGO_PIX_EXPIRATION');
        $expiration = [
            '30' => '30 ' . $this->l('minutes'),
            '60' => '1 ' . $this->l('hour'),
            '360' => '6 ' . $this->l('hours'),
            '720' => '12 ' . $this->l('hours'),
            '1440' => '1 ' . $this->l('day'),
            '10080' => '7 ' . $this->l('days'),
        ];

        return is_string($pixExpiration) ? $expiration[$pixExpiration] : $expiration['30'];
    }

    /**
     * This hook is used to display in order confirmation page.
     *
     * @param mixed $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = isset($params['order']) ? $params['order'] : $params['objOrder'];
        $checkout_type = Tools::getIsset('checkout_type') ? Tools::getValue('checkout_type') : null;
        // This hook always runs in a web request with a resolved currency context, but we
        // still use the defensive helper for consistency and as a safety net.
        $mp_currency = $this->getContextCurrencyIsoCode();
        $total_paid_amount = $this->context->currentLocale->formatPrice($order->total_paid, $mp_currency);

        $this->context->smarty->assign(
            [
                'checkout_type' => $checkout_type,
                'total_paid_amount' => $total_paid_amount,
            ]
        );

        return $this->display(__FILE__, 'views/templates/hook/seven/order_confirmation.tpl');
    }

    /**
     * Display payment failure on version 1.7
     *
     * @return string
     */
    public function hookDisplayWrapperTop()
    {
        return $this->getDisplayFailure();
    }

    /**
     * @return mixed
     */
    public function getDisplayFailure()
    {
        if (Tools::getValue('typeReturn') == 'failure') {
            $cookie = $this->context->cookie;
            if ($cookie->__isset('redirect_message')) {
                $this->context->smarty->assign(['redirect_message' => $cookie->__get('redirect_message')]);
                $cookie->__unset('redirect_message');
            }

            return $this->display(__FILE__, 'views/templates/hook/failure.tpl');
        }
    }

    /**
     * @param $sql_file
     *
     * @return bool
     */
    public function loadSQLFile($sql_file)
    {
        // Get install SQL file content
        $sql_content = Tools::file_get_contents($sql_file);

        // Replace prefix and store SQL command in array
        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

        // Execute each SQL statement
        $result = true;
        foreach ($sql_requests as $request) {
            if (!empty($request)) {
                $result &= Db::getInstance()->execute(trim($request));
            }
        }

        // Return result
        return $result;
    }
}
