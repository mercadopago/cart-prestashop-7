<?php
/**
 * 2007-2025 PrestaShop.
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

define('MP_VERSION', '4.17.3');
define('MP_ROOT_URL', dirname(__FILE__));

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/vendor/autoload.php';

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

    const PRESTA16 = "1.6";
    const PRESTA17 = "1.7";

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

        //Always update, because prestashop doesn't accept version coming from another variable (MP_VERSION)
        $this->version = '4.17.3';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

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
        $this->pseCheckout = new PseCheckout($this);
    }


    /**
     * Load files
     *
     * @return void
     */
    public function loadFiles()
    {
        include_once MP_ROOT_URL . '/includes/MPApi.php';
        include_once MP_ROOT_URL . '/includes/MPLog.php';
        include_once MP_ROOT_URL . '/includes/MPUseful.php';
        include_once MP_ROOT_URL . '/includes/MPRestCli.php';
        include_once MP_ROOT_URL . '/includes/module/preference/StandardPreference.php';
        include_once MP_ROOT_URL . '/includes/module/preference/WalletButtonPreference.php';
        include_once MP_ROOT_URL . '/includes/module/model/MPModule.php';
        include_once MP_ROOT_URL . '/includes/module/model/MPTransaction.php';
        include_once MP_ROOT_URL . '/includes/module/model/MPTransaction.php';
        include_once MP_ROOT_URL . '/includes/module/model/PSCartRule.php';
        include_once MP_ROOT_URL . '/includes/module/model/PSCartRuleRule.php';
        include_once MP_ROOT_URL . '/includes/module/model/PSOrderState.php';
        include_once MP_ROOT_URL . '/includes/module/model/PSOrderStateLang.php';
        include_once MP_ROOT_URL . '/includes/module/checkouts/StandardCheckout.php';
        include_once MP_ROOT_URL . '/includes/module/checkouts/CustomCheckout.php';
        include_once MP_ROOT_URL . '/includes/module/checkouts/TicketCheckout.php';
        include_once MP_ROOT_URL . '/includes/module/checkouts/PixCheckout.php';
        include_once MP_ROOT_URL . '/includes/module/checkouts/PseCheckout.php';
    }

    /**
     * Install the module
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension ') .
                $this->l('on your server to install this module.');
            return false;
        }

        //Prestashop configuration table
        $mp_currency = $this->context->currency->iso_code;
        Configuration::updateValue('MERCADOPAGO_COUNTRY_LINK', $this->mpuseful->setMPCurrency($mp_currency));

        //Validate if is a new seller or a plugin upgrade
        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        if ($access_token != '' && $sandbox_access_token != '') {
            Configuration::updateValue('MERCADOPAGO_STANDARD_CHECKOUT', true);
        }

        //Mercadopago configurations
        include MP_ROOT_URL . '/sql/install.php';
        MPLog::generate(sprintf('Mercadopago plugin %s installed in the store', MP_VERSION));

        //install hooks and dependencies
        return parent::install() &&
            $this->createPaymentStates() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('displayWrapperTop') &&
            $this->registerHook('displayTopColumn');
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        MPLog::generate('Mercadopago plugin uninstalled in the store');
        include MP_ROOT_URL . '/sql/uninstall.php';
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return mixed
     * @throws Exception
     */
    public function getContent()
    {
        //add css to configuration page
        $this->context->controller->addCSS($this->_path . 'views/css/back' . $this->assets_ext_min . '.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        //test flow
        $mp_transaction = new MPTransaction();
        $count_test = $mp_transaction->where('is_payment_test', '=', 1)->andWhere('received_webhook', '=', 1)->count();

        //return forms
        $store = "";
        $custom = "";
        $ticket = "";
        $standard = "";
        $pix = "";
        $pse = "";
        $this->loadSettings();
        new RatingSettings();

        $localization = new LocalizationSettings();
        $credentials = new CredentialsSettings();
        $homologation = new HomologationSettings();

        $localization = $this->renderForm($localization->submit, $localization->values, $localization->form);
        $credentials = $this->renderForm($credentials->submit, $credentials->values, $credentials->form);
        $homologation = $this->renderForm($homologation->submit, $homologation->values, $homologation->form);

        //variables for admin configuration
        $public_key = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $homologated = Configuration::get('MERCADOPAGO_HOMOLOGATION');
        $country_link = Configuration::get('MERCADOPAGO_COUNTRY_LINK');
        $access_token = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_public_key = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $sandbox_access_token = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        $pix_enabled = null;
        $country_id = null;

        if ($access_token != '' && $sandbox_access_token != '') {
            //verify if seller is homologated
            $credentialsWrapper = $this->mercadopago->getCredentialsWrapper($access_token);

            if ($homologated == false && $credentialsWrapper['homologated'] == true) {
                $homologated = Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', true);
            }

            //return checkout forms
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

        $output = $this->context->smarty->assign(
            array(
                //module requirements
                'alert' => self::$form_alert,
                'message' => self::$form_message,
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
                //credentials
                'public_key' => $public_key,
                'access_token' => $access_token,
                'sandbox_public_key' => $sandbox_public_key,
                'sandbox_access_token' => $sandbox_access_token,
                //test flow
                'count_test' => $count_test,
                'seller_homolog' => $homologated,
                //forms
                'country_form' => $localization,
                'credentials' => $credentials,
                'homolog_form' => $homologation,
                'store_form' => $store,
                'standard_form' => $standard,
                'custom_form' => $custom,
                'ticket_form' => $ticket,
                'pix_form' => $pix,
                'pse_form' => $pse,
            )
        )->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Load settings
     *
     * @return void
     */
    public function loadSettings()
    {
        include_once MP_ROOT_URL . '/includes/module/settings/StoreSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/RatingSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/StandardSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/CustomSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/TicketSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/PixSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/CredentialsSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/LocalizationSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/HomologationSettings.php';
        include_once MP_ROOT_URL . '/includes/module/settings/PseSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/CoreSdkSettings.php';
    }

    /**
     * Render forms
     *
     * @param  $submit
     * @param  $values
     * @param  $form
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

        $helper->tpl_vars = array(
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($form));
    }

    /**
     * Create the payment states
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createPaymentStates()
    {
        $order_states = array(
            array('#ccfbff', $this->l('Transaction in Process'), 'in_process', '110010000'),
            array('#c9fecd', $this->l('Transaction Completed'), 'payment', '110010010'),
            array('#fec9c9', $this->l('Transaction Canceled'), 'order_canceled', '100010000'),
            array('#fec9c9', $this->l('Transaction Declined'), 'payment_error', '100010000'),
            array('#ffeddb', $this->l('Transaction Refunded'), 'refund', '100010000'),
            array('#c28566', $this->l('Transaction Chargedback'), 'charged_back', '100010000'),
            array('#b280b2', $this->l('Transaction in Mediation'), 'in_mediation', '100010000'),
            array('#fffb96', $this->l('Transaction Pending'), 'pending', '110010000'),
            array('#ccfbff', $this->l('Transaction Authorized'), 'authorized', '100010000'),
            array('#ffb0d9', $this->l('Transaction in Possible Fraud'), 'payment_error', '100010000'),
        );

        foreach ($order_states as $key => $value) {
            if ($this->orderStateAvailable(Configuration::get('MERCADOPAGO_STATUS_' . $key)) == 1) {
                continue;
            } else {
                $order_state = new OrderState();
                $order_state->name = array();
                $order_state->template = array();
                $order_state->module_name = $this->name;
                $order_state->color = $value[0];
                $order_state->invoice = $value[3][0];
                $order_state->send_email = $value[3][1];
                $order_state->unremovable = $value[3][2];
                $order_state->hidden = $value[3][3];
                $order_state->logable = $value[3][4];
                $order_state->delivery = $value[3][5];
                $order_state->shipped = $value[3][6];
                $order_state->paid = $value[3][7];
                $order_state->deleted = $value[3][8];

                $order_state->name = array_fill(0, 10, $value[1]);
                $order_state->template = array_fill(0, 10, $value[2]);

                if ($order_state->add()) {
                    $file = _PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
                    copy((dirname(__FILE__) . '/views/img/mp_icon.gif'), $file);
                    Configuration::updateValue('MERCADOPAGO_STATUS_' . $key, $order_state->id);
                }
            }
        }

        return true;
    }

    /**
     * Check if the state exist before create another one
     *
     * @param  integer $id_order_state
     * @return void
     */
    public static function orderStateAvailable($id_order_state)
    {
        $query = "SELECT COUNT(*) AS count_state FROM " . _DB_PREFIX_ . "order_state
            WHERE id_order_state = '" . pSQL($id_order_state) . "'";
        $result = Db::getInstance()->getRow($query);
        return $result['count_state'];
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
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pixFront' . $this->assets_ext_min . '.css');
        $this->context->controller->addCSS($this->_path . 'views/css/pse' . $this->assets_ext_min . '.css');
        $this->context->controller->addJS($this->_path . 'views/js/front' . $this->assets_ext_min . '.js');
    }

    /**
     * Show payment options in version 1.6
     *
     * @param  $params
     * @return array|string|mixed
     */
    public function hookPayment($params)
    {
        return $this->loadPayments($params, self::PRESTA16);
    }

    /**
     * Show payment options in version 1.7
     *
     * @param  $params
     * @return array|string|void
     */
    public function hookPaymentOptions($params)
    {
        return $this->loadPayments($params, self::PRESTA17);
    }

    /**
     * @param $params
     * @param $version
     * @return array|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function loadPayments($params, $version)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $paymentOptions = array();

        $version == self::PRESTA16 ? $this->smarty->assign('module_dir', $this->_path) : null;
        $country = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        $checkouts = array(
            'MERCADOPAGO_STANDARD_CHECKOUT' => 'getStandardCheckout',
            'MERCADOPAGO_CUSTOM_CHECKOUT' => 'getCustomCheckout',
            'MERCADOPAGO_TICKET_CHECKOUT' => 'getTicketCheckout',
            'MERCADOPAGO_PIX_CHECKOUT' => 'getPixCheckout',
            'MERCADOPAGO_PSE_CHECKOUT' => 'getPseCheckout',
        );

        foreach ($checkouts as $checkout => $method) {
            if ($this->isActiveCheckout($checkout) && $this->isAvailableToCountry($checkout, $country)) {
                $paymentOptions[] = $this->{$method}($cart, $version);
            } else {
                $this->disableCheckout($checkout);
            }
        }

        return $version == self::PRESTA16 ? implode('', $paymentOptions) : $paymentOptions;
    }

    /**
     * @param $checkout
     * @return bool
     */
    public function isActiveCheckout($checkout)
    {
        return (Configuration::get($checkout) == true);
    }

    /**
     * @param $checkout
     * @param $country
     * @return bool
     */
    public function isAvailableToCountry($checkout, $country)
    {
        $checkoutsWithCountryRestriction = array(
            'MERCADOPAGO_PIX_CHECKOUT',
            PseCheckout::PSE_CHECKOUT_NAME
        );

        if (!in_array($checkout, $checkoutsWithCountryRestriction)) {
            return true;
        }

        if (
            $country === 'mlb'
            && $checkout === 'MERCADOPAGO_PIX_CHECKOUT'
            && $this->isEnabledPaymentMethod('pix')
        ) {
            return true;
        }

        if (
            $this->pseCheckout->isAvailableToCountry($country)
            && $checkout === PseCheckout::PSE_CHECKOUT_NAME
            && $this->isEnabledPaymentMethod(PseCheckout::PAYMENT_METHOD_NAME)
        ) {
            return true;
        }

        return false;
    }


    /**
     * @param $checkout
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
     * @return string
     */
    public function getSiteIdByCredentials($accessToken)
    {
        $response = $this->mercadopago->isValidAccessToken($accessToken);

        return $response ? Tools::strtolower($response['site_id']) : null;
    }

    /**
     * @param $checkout
     * @return void
     */
    public function disableCheckout($checkout)
    {
        Configuration::updateValue($checkout, false);
    }

    /**
     * @param  $cart
     * @param  $version
     * @return PaymentOption | string
     */
    public function getStandardCheckout($cart, $version)
    {
        if ($version == self::PRESTA16) {
            $frontInformations = $this->standardCheckout->getStandardCheckoutPS16($cart);
            $this->context->smarty->assign($frontInformations);
            return $this->display(__FILE__, 'views/templates/hook/six/standard.tpl');
        } else {
            $frontInformations = $this->standardCheckout->getStandardCheckoutPS17($cart);
            $infoTemplate = $this->context->smarty->assign($frontInformations)
                ->fetch('module:mercadopago/views/templates/hook/seven/standard.tpl');
            $standardCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $standardCheckout->setForm($infoTemplate)
                ->setCallToActionText($this->l('I want to pay with Mercado Pago at no additional cost.'))
                ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

            return $standardCheckout;
        }
    }

    /**
     * @param  $cart
     * @param  $version
     * @return PaymentOption | string
     */
    public function getCustomCheckout($cart, $version)
    {
        if ($version == self::PRESTA16) {
            $frontInformations = $this->customCheckout->getCustomCheckoutPS16($cart);
            $this->context->smarty->assign($frontInformations);
            return $this->display(__FILE__, 'views/templates/hook/six/custom.tpl');
        } else {
            $discount = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
            $str_discount = ' (' . $discount . '% OFF) ';
            $str_discount = ($discount != "") ? $str_discount : '';

            $frontInformations = $this->customCheckout->getCustomCheckoutPS17($cart);
            $infoTemplate = $this->context->smarty->assign($frontInformations)
                ->fetch('module:mercadopago/views/templates/hook/seven/custom.tpl');
            $customCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $customCheckout->setForm($infoTemplate)
                ->setCallToActionText($this->l(' Pay with credit and debit cards') . $str_discount)
                ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

            return $customCheckout;
        }
    }

    /**
     * @param  $cart
     * @param  $version
     * @return PaymentOption | string
     */
    public function getTicketCheckout($cart, $version)
    {
        if ($version == self::PRESTA16) {
            $frontInformations = $this->ticketCheckout->getTicketCheckoutPS16($cart);
            $this->context->smarty->assign($frontInformations);
            return $this->display(__FILE__, 'views/templates/hook/six/ticket.tpl');
        } else {
            $discount = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
            $str_discount = ' (' . $discount . '% OFF) ';
            $str_discount = ($discount != "") ? $str_discount : '';

            $frontInformations = $this->ticketCheckout->getTicketCheckoutPS17($cart);
            $infoTemplate = $this->context->smarty->assign($frontInformations)
                ->fetch('module:mercadopago/views/templates/hook/seven/ticket.tpl');
            $ticketCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $ticketCheckout->setForm($infoTemplate)
                ->setCallToActionText($this->l('Pay with payment methods in cash') . $str_discount)
                ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

            return $ticketCheckout;
        }
    }

    /**
     * @param  $cart
     * @param  $version
     * @return PaymentOption | string
     */
    public function getPixCheckout($cart, $version)
    {
        $discount = Configuration::get('MERCADOPAGO_PIX_DISCOUNT');

        if ($version == self::PRESTA16) {
            $frontInformations = $this->pixCheckout->getPixCheckoutPS16();
            $frontInformations['discount'] = $discount;

            $this->context->smarty->assign($frontInformations);
            return $this->display(__FILE__, 'views/templates/hook/six/pix.tpl');
        }

        $strDiscount = ' (' . $discount . '% OFF) ';
        $strDiscount = ($discount != "") ? $strDiscount : '';

        $frontInformations = $this->pixCheckout->getPixCheckoutPS17();
        $infoTemplate = $this->context->smarty->assign($frontInformations)
            ->fetch('module:mercadopago/views/templates/hook/seven/pix.tpl');
        $pixCheckout = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $pixCheckout->setForm($infoTemplate)
            ->setCallToActionText($this->l('Pix') . $strDiscount)
            ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

        return $pixCheckout;
    }

    /**
     * @param  $cart
     * @param  $version
     * @return PaymentOption | string
     */
    public function getPseCheckout($cart, $version)
    {
        $pluginInfos = array(
            'redirect_link' => $this->context->link->getModuleLink($this->name, PseCheckout::PAYMENT_METHOD_NAME),
            'module_dir' => $this->path,
        );
        $paymentMethods = $this->mercadopago->getPaymentMethods();
        $templateData = $this->pseCheckout->getPseTemplateData($paymentMethods, $pluginInfos);
        $infoTemplate = $this->context->smarty->assign($templateData)
            ->fetch('module:mercadopago/views/templates/hook/seven/pse.tpl');
        $psePaymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $psePaymentOption->setForm($infoTemplate)
            ->setCallToActionText($this->l('PSE') . ' ' . $this->pseCheckout->getDiscountBanner())
            ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png');

        return $psePaymentOption;
    }

    /**
     * Check currency
     *
     * @param  mixed $cart
     * @return boolean
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
     * @param  mixed $params
     * @return string
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $paymentId = Tools::getValue('payment_id');
        $payment = is_string($paymentId) ? $this->mercadopago->getPaymentStandard($paymentId) : [];

        return $this->getPaymentReturn($payment, $params);
    }

    /**
     * Get template of payment confirmation
     *
     * @param  mixed $payment
     * @param  mixed $params
     * @return string
     */
    public function getPaymentReturn($payment, $params)
    {
        $order = array_key_exists('objOrder', $params) ? $params['objOrder'] : null;
        $products = !is_null($order) ? $order->getProducts() : null;
        $mp_currency = $this->context->currency->iso_code;
        if (isset($payment['transaction_details']['total_paid_amount']) && isset($payment['transaction_amount']) && isset($payment['transaction_details']['installment_amount'])) {
            $cost_of_installments = $payment['transaction_details']['total_paid_amount'] - $payment['transaction_amount'];
            $cost_of_installments_formated = $this->context->currentLocale->formatPrice($cost_of_installments, $mp_currency);
            $total_paid_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['total_paid_amount'], $mp_currency);
            $installment_amount = $this->context->currentLocale->formatPrice($payment['transaction_details']['installment_amount'], $mp_currency);
        }

        $this->context->smarty->assign(
            array(
                'order' => $order,
                'payment' => $payment,
                'order_products' => $products,
                'pix_expiration' => $this->getPixExpiration(),
                'cost_of_installments' => isset($cost_of_installments) ? $cost_of_installments : null,
                'cost_of_installments_formated' => isset($cost_of_installments_formated) ? $cost_of_installments_formated : null,
                'total_paid_amount' => isset($total_paid_amount) ? $total_paid_amount : null,
                'installment_amount' => isset($installment_amount) ? $installment_amount : null,
            )
        );

        $versions = array(
            self::PRESTA16 => 'six',
            self::PRESTA17 => 'seven',
        );

        return $this->display(__FILE__, 'views/templates/hook/' . $versions[$this->getVersionPs()] . '/payment_return.tpl');
    }

    /**
     * Get pix expiration
     *
     * @return string
     */
    public function getPixExpiration()
    {
        $pixExpiration = Configuration::get('MERCADOPAGO_PIX_EXPIRATION');
        $expiration = array(
            '30' => '30 ' . $this->l('minutes'),
            '60' => '1 ' . $this->l('hour'),
            '360' => '6 ' . $this->l('hours'),
            '720' => '12 ' . $this->l('hours'),
            '1440' => '1 ' . $this->l('day'),
            '10080' => '7 ' . $this->l('days'),
        );

        return is_string($pixExpiration) ? $expiration[$pixExpiration] : $expiration['30'];
    }

    /**
     * This hook is used to display in order confirmation page.
     *
     * @param  mixed $params
     * @return string
     */
    public function hookDisplayOrderConfirmation($params)
    {
        $order = isset($params['order']) ? $params['order'] : $params['objOrder'];
        $checkout_type = Tools::getIsset('checkout_type') ? Tools::getValue('checkout_type') : null;
        $mp_currency = $this->context->currency->iso_code;
        $total_paid_amount = $this->context->currentLocale->formatPrice($order->total_paid, $mp_currency);

        $this->context->smarty->assign(
            array(
                'checkout_type' => $checkout_type,
                'total_paid_amount' => $total_paid_amount,
            )
        );

        $versions = array(
            self::PRESTA16 => 'six',
            self::PRESTA17 => 'seven',
        );

        return $this->display(__FILE__, 'views/templates/hook/' . $versions[$this->getVersionPs()] . '/order_confirmation.tpl');
    }

    /**
     * Display payment failure on version 1.6
     *
     * @return string
     */
    public function hookDisplayTopColumn()
    {
        return $this->getDisplayFailure();
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
                $this->context->smarty->assign(array('redirect_message' => $cookie->__get('redirect_message')));
                $cookie->__unset('redirect_message');
            }

            return $this->display(__FILE__, 'views/templates/hook/failure.tpl');
        }
    }

    /**
     * @return string
     */
    public function getVersionPs()
    {
        if ($this->ps_version >= 1.7) {
            return self::PRESTA17;
        } else {
            return self::PRESTA16;
        }
    }

    /**
     * @param $sql_file
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
