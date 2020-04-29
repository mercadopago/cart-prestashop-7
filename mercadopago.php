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
 * @author    MercadoPago
 * @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

define('MP_VERSION', '4.1.1');
define('MP_ROOT_URL', dirname(__FILE__));

if (!defined('_PS_VERSION_')) {
    exit;
}

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
    public $customCheckout;
    public $ticketCheckout;
    public $standardCheckout;
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
        $this->version = '4.1.1';

        parent::__construct();

        $this->displayName = $this->l('Mercado Pago');
        $this->description = $this->l('Customize the payment experience of your customers in your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
        $this->module_key = '4380f33bbe84e7899aacb0b7a601376f';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->ps_version = _PS_VERSION_;
        $this->path = $this->_path;
        $this->standardCheckout = new StandardCheckout($this);
        $this->customCheckout = new CustomCheckout($this);
        $this->ticketCheckout = new TicketCheckout($this);
    }


    /**
     * Load files
     *
     * @return void
     */
    public function loadFiles()
    {
        require_once MP_ROOT_URL . '/includes/MPApi.php';
        require_once MP_ROOT_URL . '/includes/MPLog.php';
        require_once MP_ROOT_URL . '/includes/MPUseful.php';
        require_once MP_ROOT_URL . '/includes/MPRestCli.php';
        require_once MP_ROOT_URL . '/includes/module/preference/StandardPreference.php';
        require_once MP_ROOT_URL . '/includes/module/model/MPModule.php';
        require_once MP_ROOT_URL . '/includes/module/model/MPTransaction.php';
        require_once MP_ROOT_URL . '/includes/module/model/MPTransaction.php';
        require_once MP_ROOT_URL . '/includes/module/model/StandardCheckout.php';
        require_once MP_ROOT_URL . '/includes/module/model/CustomCheckout.php';
        require_once MP_ROOT_URL . '/includes/module/model/TicketCheckout.php';
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
        include(MP_ROOT_URL . '/sql/install.php');
        MPLog::generate('Mercadopago plugin installed in the store');

        //install hooks and dependencies
        return parent::install() &&
            $this->createPaymentStates() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
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
        include(MP_ROOT_URL . '/sql/uninstall.php');
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
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        //test flow
        $mp_transaction = new MPTransaction();
        $count_test = $mp_transaction->where('is_payment_test', '=', 1)->andWhere('received_webhook', '=', 1)->count();

        //return forms
        $store = "";
        $custom = "";
        $ticket = "";
        $standard = "";
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

        if ($access_token != '' && $sandbox_access_token != '') {
            //verify if seller is homologated
            if ($homologated == false && in_array('payments', $this->mercadopago->homologValidate())) {
                $homologated = Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', true);
            }

            //return checkout forms
            $store = new StoreSettings();
            $standard = new StandardSettings();
            $custom = new CustomSettings();
            $ticket = new TicketSettings();

            $store = $this->renderForm($store->submit, $store->values, $store->form);
            $standard = $this->renderForm($standard->submit, $standard->values, $standard->form);
            $custom = $this->renderForm($custom->submit, $custom->values, $custom->form);
            $ticket = $this->renderForm($ticket->submit, $ticket->values, $ticket->form);
        }

        $output = $this->context->smarty->assign(array(
            //module requirements
            'alert' => self::$form_alert,
            'message' => self::$form_message,
            'mp_version' => MP_VERSION,
            'url_base' => __PS_BASE_URI__,
            'country_link' => $country_link,
            'application' => Configuration::get('MERCADOPAGO_APPLICATION_ID'),
            'standard_test' => Configuration::get('MERCADOPAGO_STANDARD'),
            'sandbox_status' => Configuration::get('MERCADOPAGO_PROD_STATUS'),
            'seller_protect_link' => $this->mpuseful->setSellerProtectLink($country_link),
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
            'ticket_form' => $ticket
        ))->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Load settings
     *
     * @return void
     */
    public function loadSettings()
    {
        require_once MP_ROOT_URL . '/includes/module/settings/StoreSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/RatingSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/StandardSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/CustomSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/TicketSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/CredentialsSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/LocalizationSettings.php';
        require_once MP_ROOT_URL . '/includes/module/settings/HomologationSettings.php';
    }

    /**
     * Render forms
     *
     * @param $submit
     * @param $values
     * @param $form
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
    protected function createPaymentStates()
    {
        $order_states = array(
            array('#ccfbff', $this->l('Transaction in Process'), 'in_process', '110010000'),
            array('#c9fecd', $this->l('Transaction Completed'), 'payment', '100010010'),
            array('#fec9c9', $this->l('Transaction Canceled'), 'order_canceled', '100010000'),
            array('#fec9c9', $this->l('Transaction Declined'), 'payment_error', '100010000'),
            array('#ffeddb', $this->l('Transaction Refunded'), 'refund', '100010000'),
            array('#c28566', $this->l('Transaction Chargedback'), 'charged_back', '110010000'),
            array('#b280b2', $this->l('Transaction in Mediation'), 'in_mediation', '110010000'),
            array('#fffb96', $this->l('Transaction Pending'), 'pending', '110010000'),
            array('#ccfbff', $this->l('Transaction Authorized'), 'authorized', '100010000'),
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
     * @param integer $id_order_state
     * @return void
     */
    protected static function orderStateAvailable($id_order_state)
    {
        $result = Db::getInstance()->getRow(
            "SELECT COUNT(*) AS count_state FROM " . _DB_PREFIX_ . "order_state 
            WHERE id_order_state = '" . $id_order_state . "'"
        );
        return $result['count_state'];
    }

    /**
     * Return null for Mercado Envios
     *
     * @return void
     */
    public function getOrderShippingCost()
    {
        return;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO
     *
     * @return void
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    /**
     * Show payment options in version 1.6
     *
     * @param $params
     * @return array|string|mixed
     */
    public function hookPayment($params)
    {
        return $this->loadPayments($params, self::PRESTA16);
    }

    /**
     * Show payment options in version 1.7
     *
     * @param $params
     * @return array|string|void
     */
    public function hookPaymentOptions($params)
    {
        return $this->loadPayments($params, self::PRESTA17);
    }

    public function loadPayments($params, $version)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $cart = $this->context->cart;
        $payment_options = array();

        $version == self::PRESTA16 ? $this->smarty->assign('module_dir', $this->_path) : null;

        if (Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT') == true) {
            $payment_options[] = $this->getStandardCheckout($cart, $version);
        }
        if (Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT') == true) {
            $payment_options[] = $this->getCustomCheckout($cart, $version);
        }

        if (Configuration::get('MERCADOPAGO_TICKET_CHECKOUT') == true) {
            $payment_options[] = $this->getTicketCheckout($cart, $version);
        }

        return $version == self::PRESTA16 ? implode('', $payment_options) : $payment_options;
    }

    /**
     * @param $cart
     * @param $version
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
     * @param $cart
     * @param $version
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
     * @param $cart
     * @param $version
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
     * Check currency
     *
     * @param mixed $cart
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
     * @param mixed $params
     * @return string
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $ticket_url = Tools::getIsset('payment_ticket') ? Tools::getValue('payment_ticket') : null;

        if ($this->getVersionPs() == self::PRESTA17) {
            $this->context->smarty->assign(array(
                "ticket_url" => $ticket_url
            ));
            return $this->display(__FILE__, 'views/templates/hook/seven/ticket_return.tpl');
        }

        $order = $params['objOrder'];
        $products = $order->getProducts();

        $this->context->smarty->assign(array(
            'order' => $order,
            'order_products' => $products,
            "ticket_url" => $ticket_url
        ));

        return $this->display(__FILE__, 'views/templates/hook/six/payment_return.tpl');
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
}
