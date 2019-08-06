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

define('MP_VERSION', '4.0.1');
define('MP_ROOT_URL', dirname(__FILE__));
include MP_ROOT_URL . '/includes/MPApi.php';
include MP_ROOT_URL . '/includes/MPLog.php';
include MP_ROOT_URL . '/includes/MPUseful.php';
include MP_ROOT_URL . '/includes/MPRestCli.php';
include MP_ROOT_URL . '/model/MPModule.php';
include MP_ROOT_URL . '/model/MPTransaction.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mercadopago extends PaymentModule
{
    public $mercadopago;
    public $mpuseful;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $confirmUninstall;
    public $module_key;
    public $ps_versions_compliancy;
    private static $form_alert;
    private static $form_message;

    public function __construct()
    {
        $this->mercadopago = MPApi::getInstance();
        $this->mpuseful = MPUseful::getInstance();
        $this->name = 'mercadopago';
        $this->tab = 'payments_gateways';
        $this->author = 'mercadopago';
        $this->need_instance = 1;
        $this->bootstrap = true;

        //Always update, because prestashop doesn't accept version coming from another variable (MP_VERSION)
        $this->version = '4.0.1';

        parent::__construct();

        $this->displayName = $this->l('Mercado Pago');
        $this->description = $this->l('Customize the payment experience of your customers in your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
        $this->module_key = '4380f33bbe84e7899aacb0b7a601376f';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Install the module
     *
     * @return void
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
        Configuration::updateValue('MERCADOPAGO_AUTO_RETURN', true);
        Configuration::updateValue('MERCADOPAGO_SANDBOX_STATUS', true);
        Configuration::updateValue('MERCADOPAGO_INSTALLMENTS', 24);
        Configuration::updateValue('MERCADOPAGO_STANDARD', false);
        Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', false);

        //Mercadopago configurations
        include(dirname(__FILE__) . '/sql/install.php');
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
     * @return void
     */
    public function uninstall()
    {
        MPLog::generate('Mercadopago plugin uninstalled in the store');
        include(dirname(__FILE__) . '/sql/uninstall.php');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return void
     */
    public function getContent()
    {
        //add css to configuration page
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        //process the forms
        if (((bool) Tools::isSubmit('submitMercadopagoCountry')) == true) {
            $this->postProcessCountry();
        } elseif (((bool) Tools::isSubmit('submitMercadopagoCredentials')) == true) {
            $this->postProcessCredentials();
        } elseif (((bool) Tools::isSubmit('submitMercadopagoStandard')) == true) {
            $this->postProcessStandard();
        } elseif (((bool) Tools::isSubmit('submitMercadopagoAdvanced')) == true) {
            $this->postProcessAdvanced();
        } elseif (((bool) Tools::isSubmit('submitMercadopagoRating')) == true) {
            $this->postProcessRating();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        //test flow
        $mp_transaction = new MPTransaction();
        $count_test = $mp_transaction->where('is_payment_test', '=', 1)->andWhere('received_webhook', '=', 1)->count();

        //verify if seller is homologated
        if (Configuration::get('MERCADOPAGO_HOMOLOGATION') == false) {
            if (in_array('payments', $this->mercadopago->homologValidate())) {
                Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', true);
            }
        }

        //return forms for admin views
        $country_link = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        $output = $this->context->smarty->assign(array(
            'alert' => self::$form_alert,
            'message' => self::$form_message,
            'url_base' => __PS_BASE_URI__,
            'count_test' => $count_test,
            'seller_homolog' => Configuration::get('MERCADOPAGO_HOMOLOGATION'),
            'country_form' => $this->renderFormCountry(),
            'standard_form' => $this->renderFormStandard(),
            'homolog_form' => $this->renderFormHomolog(),
            'credentials' => $this->renderFormCredentials(),
            'advanced_form' => $this->renderFormAdvanced(),
            'access_token' => Configuration::get('MERCADOPAGO_ACCESS_TOKEN'),
            'sandbox_status' => Configuration::get('MERCADOPAGO_SANDBOX_STATUS'),
            'sandbox_access_token' => Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN'),
            'standard_test' => Configuration::get('MERCADOPAGO_STANDARD'),
            'country_link' => $country_link,
            'application' => Configuration::get('MERCADOPAGO_APPLICATION_ID'),
            'seller_protect_link' => $this->mpuseful->setSellerProtectLink($country_link)
        ))
            ->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Render country form
     *
     * @return void
     */
    protected function renderFormCountry()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMercadopagoCountry';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigCountryFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormCountry()));
    }

    /**
     * Country form
     *
     * @return void
     */
    protected function getConfigFormCountry()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Localization'),
                    'icon' => 'icon-cogs',
                ),
                'class' => 'credentials',
                'input' => array(
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Choose your country'),
                        'name' => 'MERCADOPAGO_COUNTRY_LINK',
                        'desc' => $this->l('Select the country which your Mercado Pago account operates.'),
                        'options' => array(
                            'query' => $this->getCountryLinks(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                ),
            ),
        );
    }

    /**
     * Set values for the inputs of country form
     *
     * @return array
     */
    protected function getConfigCountryFormValues()
    {
        return array(
            'MERCADOPAGO_COUNTRY_LINK' => Configuration::get('MERCADOPAGO_COUNTRY_LINK')
        );
    }

    /**
     * Save country form data
     *
     * @return void
     */
    protected function postProcessCountry()
    {
        $form_values = $this->getConfigCountryFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        self::$form_alert = 'alert-success';
        self::$form_message = $this->l('Settings saved successfully. Now you can configure the module.');

        $this->sendSettingsInfo();
        MPLog::generate('Country saved successfully');

        return true;
    }

    /**
     * Render homolog form
     *
     * @return void
     */
    protected function renderFormHomolog()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormHomolog()));
    }

    /**
     * Homolog form
     *
     * @return void
     */
    protected function getConfigFormHomolog()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Homologation'),
                    'icon' => 'icon-cogs',
                ),
                'class' => 'credentials',
                'input' => '',
            ),
        );
    }

    /**
     * Render credentials form
     *
     * @return void
     */
    protected function renderFormCredentials()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMercadopagoCredentials';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigCredentialsFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormCredentials()));
    }

    /**
     * Credentials form
     *
     * @return void
     */
    protected function getConfigFormCredentials()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Credentials'),
                    'icon' => 'icon-cogs',
                ),
                'class' => 'credentials',
                'input' => array(
                    array(
                        'col' => 4,
                        'type' => 'switch',
                        'label' => $this->l('Sandbox Mode'),
                        'name' => 'MERCADOPAGO_SANDBOX_STATUS',
                        'is_bool' => true,
                        'desc' => $this->l('Choose "YES" to test your store before selling. ') .
                            $this->l('Switch to "NO" to disable test mode ') .
                            $this->l('and start receiving online payments.'),
                        'values' => array(
                            array(
                                'id' => 'MERCADOPAGO_SANDBOX_STATUS_ON',
                                'value' => true,
                                'label' => $this->l('Active')
                            ),
                            array(
                                'id' => 'MERCADOPAGO_SANDBOX_STATUS_OFF',
                                'value' => false,
                                'label' => $this->l('Inactive')
                            )
                        ),
                    ),
                    array(
                        'col' => 8,
                        'type' => 'html',
                        'desc' => ' ',
                        'label' => $this->l('Upload credentials'),
                        'html_content' => '<a href="https://www.mercadopago.com/'
                            . Configuration::get('MERCADOPAGO_COUNTRY_LINK') .
                            '/account/credentials" target="_blank" class="btn btn-default btn-credenciais">'
                            . $this->l('Search my credentials') . '</a>'
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'desc' => $this->l('Do the tests you want.'),
                        'name' => 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN',
                        'label' => $this->l('Access token - Sandbox'),
                        'required' => true
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'desc' => $this->l('With this key you can receive real payments from your customers.'),
                        'name' => 'MERCADOPAGO_ACCESS_TOKEN',
                        'label' => $this->l('Access token - Production'),
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'credentials'
                ),
            ),
        );
    }

    /**
     * Set values for the inputs of credentials form
     *
     * @return array
     */
    protected function getConfigCredentialsFormValues()
    {
        return array(
            'MERCADOPAGO_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_ACCESS_TOKEN'),
            'MERCADOPAGO_SANDBOX_STATUS' => Configuration::get('MERCADOPAGO_SANDBOX_STATUS'),
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN'),
        );
    }

    /**
     * Save standard form data
     *
     * @return void
     */
    protected function postProcessCredentials()
    {
        $form_values = $this->getConfigCredentialsFormValues();
        $access_token = Tools::getValue('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_access_token = Tools::getValue('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        //validate the tokens
        $token_validation = $this->mercadopago->isValidAccessToken($access_token);
        $sandbox_token_validation = $this->mercadopago->isValidAccessToken($sandbox_access_token);

        foreach (array_keys($form_values) as $key) {
            if ($key == 'MERCADOPAGO_ACCESS_TOKEN') {
                if ($access_token != '' && $token_validation != false) {
                    $application_id = explode('-', $access_token);
                    Configuration::updateValue('MERCADOPAGO_APPLICATION_ID', $application_id[1]);
                    Configuration::updateValue('MERCADOPAGO_SITE_ID', $token_validation['site_id']);
                    Configuration::updateValue('MERCADOPAGO_SELLER_ID', $token_validation['id']);
                } else {
                    self::$form_alert = 'alert-danger';
                    self::$form_message = $this->l('Credentials can not be empty and must be valid. ') .
                        $this->l('Please complete your credentials to enable the module.');
                    MPLog::generate('Invalid APP_USR credentials submitted', 'warning');
                    continue;
                }
            } elseif ($key == 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN') {
                if ($sandbox_access_token == '' || $sandbox_token_validation == false) {
                    self::$form_alert = 'alert-danger';
                    self::$form_message = $this->l('Credentials can not be empty and must be valid. ') .
                        $this->l('Please complete your credentials to enable the module.');
                    MPLog::generate('Invalid TEST credentials submitted', 'warning');
                    continue;
                }
            }

            Configuration::updateValue($key, Tools::getValue($key));
        }

        if (self::$form_alert != 'alert-danger') {
            if (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == '') {
                Configuration::updateValue('MERCADOPAGO_CHECKOUT_STATUS', true);
                $payment_methods = $this->mercadopago->getPaymentMethods();
                foreach ($payment_methods as $payment_method) {
                    $pm_name = 'MERCADOPAGO_PAYMENT_' . $payment_method['id'];
                    Configuration::updateValue($pm_name, 'on');
                }
            }

            self::$form_alert = 'alert-success';
            self::$form_message = $this->l('Settings saved successfully. Now you can configure the module.');

            $this->sendSettingsInfo();
            MPLog::generate('Credentials saved successfully');
        }

        return true;
    }

    /**
     * Render standard checkout form
     *
     * @return void
     */
    protected function renderFormStandard()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMercadopagoStandard';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigStandardFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormStandard()));
    }

    /**
     * Checkout standard form
     *
     * @return void
     */
    protected function getConfigFormStandard()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Basic configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate checkout'),
                        'name' => 'MERCADOPAGO_CHECKOUT_STATUS',
                        'desc' => $this->l('Activate the Mercado Pago experience at the checkout of your store.'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'MERCADOPAGO_CHECKOUT_STATUS_ON',
                                'value' => true,
                                'label' => $this->l('Active')
                            ),
                            array(
                                'id' => 'MERCADOPAGO_CHECKOUT_STATUS_OFF',
                                'value' => false,
                                'label' => $this->l('Inactive')
                            )
                        ),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Name'),
                        'name' => 'MERCADOPAGO_INVOICE_NAME',
                        'desc' => $this->l('This is the name that will appear on the customers invoice.'),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Category'),
                        'name' => 'MERCADOPAGO_STORE_CATEGORY',
                        'desc' => $this->l('What category are your products?') .
                            $this->l('Choose the one that best characterizes them ') .
                            $this->l('(choose "other" if your product is too specific)'),
                        'options' => array(
                            'query' => $this->getCategories(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 4,
                        'type' => 'checkbox',
                        'label' => $this->l('Payment methods'),
                        'name' => 'MERCADOPAGO_PAYMENT',
                        'hint' => $this->l('Select the payment methods available in your store.'),
                        'class' => 'payment-online-checkbox',
                        'desc' => ' ',
                        'values' => array(
                            'query' => $this->mercadopago->getOnlinePaymentMethods(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 4,
                        'type' => 'checkbox',
                        'name' => 'MERCADOPAGO_PAYMENT',
                        'class' => 'payment-offline-checkbox',
                        'desc' => $this->l('Activate the payment alternatives you prefer for your customers.'),
                        'values' => array(
                            'query' => $this->mercadopago->getOfflinePaymentMethods(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Maximum of installments'),
                        'name' => 'MERCADOPAGO_INSTALLMENTS',
                        'desc' => $this->l('What is the maximum of installments which a customer can buy?'),
                        'options' => array(
                            'query' => $this->mpuseful->getInstallments(24),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'standard'
                ),
            ),
        );
    }

    /**
     * Set values for the inputs of standard form
     *
     * @return array
     */
    protected function getConfigStandardFormValues()
    {
        $standard_configs = array(
            'MERCADOPAGO_CHECKOUT_STATUS' => Configuration::get('MERCADOPAGO_CHECKOUT_STATUS'),
            'MERCADOPAGO_STORE_CATEGORY' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
            'MERCADOPAGO_INVOICE_NAME' => Configuration::get('MERCADOPAGO_INVOICE_NAME'),
            'MERCADOPAGO_INSTALLMENTS' => Configuration::get('MERCADOPAGO_INSTALLMENTS'),
        );

        $payment_methods = $this->mercadopago->getPaymentMethods();
        foreach ($payment_methods as $payment_method) {
            $pm_name = 'MERCADOPAGO_PAYMENT_' . $payment_method['id'];
            $standard_configs[$pm_name] = Configuration::get($pm_name);
        }

        return $standard_configs;
    }

    /**
     * Save standard form data
     *
     * @return void
     */
    protected function postProcessStandard()
    {
        $form_values = $this->getConfigStandardFormValues();
        Configuration::updateValue('MERCADOPAGO_STANDARD', true);

        foreach (array_keys($form_values) as $key) {
            if ($key == 'MERCADOPAGO_CHECKOUT_STATUS' && Tools::getValue($key) == '') {
                Configuration::updateValue($key, 0);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }

        self::$form_alert = 'alert-success';
        self::$form_message = $this->l('Settings saved successfully.');

        $this->sendSettingsInfo();
        MPLog::generate('Basic configuration saved successfully');

        return true;
    }

    /**
     * Render standard checkout form
     *
     * @return void
     */
    protected function renderFormAdvanced()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMercadopagoAdvanced';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigAdvancedFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormAdvanced()));
    }

    /**
     * Checkout standard form
     *
     * @return void
     */
    protected function getConfigFormAdvanced()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Advanced configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Return to the store'),
                        'name' => 'MERCADOPAGO_AUTO_RETURN',
                        'is_bool' => true,
                        'desc' => $this->l('Do you want your client to come back to ') .
                            $this->l('the store after finishing the purchase?'),
                        'values' => array(
                            array(
                                'id' => 'MERCADOPAGO_AUTO_RETURN_ON',
                                'value' => true,
                                'label' => $this->l('Active')
                            ),
                            array(
                                'id' => 'MERCADOPAGO_AUTO_RETURN_OFF',
                                'value' => false,
                                'label' => $this->l('Inactive')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Binary Mode'),
                        'name' => 'MERCADOPAGO_BINARY_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Accept and reject payments automatically. Do you want us to activate it? '),
                        'hint' => $this->l('If you activate the binary mode ') .
                            $this->l('you will not be able to leave pending payments. ') .
                            $this->l('This can affect the prevention of fraud. ') .
                            $this->l('Leave it inactive to be protected by our own tool.'),
                        'values' => array(
                            array(
                                'id' => 'MERCADOPAGO_BINARY_MODE_ON',
                                'value' => true,
                                'label' => $this->l('Active')
                            ),
                            array(
                                'id' => 'MERCADOPAGO_BINARY_MODE_OFF',
                                'value' => false,
                                'label' => $this->l('Inactive')
                            )
                        ),
                    ),
                    array(
                        'col' => 2,
                        'suffix' => 'horas',
                        'type' => 'text',
                        'name' => 'MERCADOPAGO_EXPIRATION_DATE_TO',
                        'label' => $this->l('Save payment preferences during '),
                        'hint' => $this->l('Payment links are generated every time we receive ') .
                            $this->l('data of a purchase intention of your customers. ') .
                            $this->l('We keep that information for a period of time not to ') .
                            $this->l('ask for the data each time you return to the purchase process. ') .
                            $this->l('Choose when you want us to forget it.'),
                        'desc' => ' ',
                    ),
                    array(
                        'col' => 2,
                        'type' => 'text',
                        'name' => 'MERCADOPAGO_SPONSOR_ID',
                        'label' => $this->l('Sponsor ID'),
                        'desc' => $this->l('With this number we identify all your transactions ') .
                            $this->l('and we know how many sales we process with your account.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'standard'
                ),
            ),
        );
    }

    /**
     * Set values for the inputs of standard form
     *
     * @return array
     */
    protected function getConfigAdvancedFormValues()
    {
        $advanced_configs = array(
            'MERCADOPAGO_AUTO_RETURN' => Configuration::get('MERCADOPAGO_AUTO_RETURN'),
            'MERCADOPAGO_BINARY_MODE' => Configuration::get('MERCADOPAGO_BINARY_MODE'),
            'MERCADOPAGO_EXPIRATION_DATE_TO' => Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO'),
            'MERCADOPAGO_SPONSOR_ID' => Configuration::get('MERCADOPAGO_SPONSOR_ID'),
        );

        return $advanced_configs;
    }

    /**
     * Save advanced form data
     *
     * @return void
     */
    protected function postProcessAdvanced()
    {
        $form_values = $this->getConfigAdvancedFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key == 'MERCADOPAGO_EXPIRATION_DATE_TO') {
                if (Tools::getValue($key) != '' && !is_numeric(Tools::getValue($key))) {
                    self::$form_alert = 'alert-danger';
                    self::$form_message .= $this->l('The time to save payment preferences ') .
                        $this->l('must be an integer.');
                    MPLog::generate('Invalid expiration_date_to submitted', 'warning');
                    continue;
                }
            } elseif ($key == 'MERCADOPAGO_SPONSOR_ID') {
                if (Tools::getValue($key) != '' && !$this->mercadopago->isValidSponsorId(Tools::getValue($key))) {
                    self::$form_alert = 'alert-danger';
                    self::$form_message .= $this->l('Sponsor ID must be valid and ');
                        $this->l('must be from the same country as the seller.');
                    MPLog::generate('Invalid sponsor_id submitted', 'warning');
                    continue;
                }
            }

            Configuration::updateValue($key, Tools::getValue($key));
        }

        if (self::$form_alert != 'alert-danger') {
            self::$form_alert = 'alert-success';
            self::$form_message = $this->l('Settings saved successfully.');
            MPLog::generate('Advanced configuration saved successfully');
        }

        return true;
    }

    /**
     * Save advanced form data
     *
     * @return void
     */
    protected function postProcessRating()
    {
        //retrieve data from form
        $rating = Tools::getValue('mercadopago-rating');
        $comments = Tools::getValue('mercadopago-comments');

        //update data
        $mp_module = new MPModule();
        $count = $mp_module->where('version', '=', $this->version)->count();

        if ($count != 0) {
            $mp_module->update([
                "evaluation" => $rating,
                "comments" => $comments
            ]);
        }

        self::$form_alert = 'alert-success';
        self::$form_message = $this->l('Thanks for rating us!');
        MPLog::generate('Evaluation saved successfully');

        return true;
    }

    /**
     * Send info to settings api
     *
     * @return void
     */
    protected function sendSettingsInfo()
    {
        $checkout_basic = (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == true) ? 'true' : 'false';

        $data = array(
            "platform" => "PrestaShop",
            "platform_version" => _PS_VERSION_,
            "module_version" => MP_VERSION,
            "code_version" => phpversion(),
            "checkout_basic" => $checkout_basic
        );

        $this->mercadopago->saveApiSettings($data);

        return true;
    }

    /**
     * Create the payment states
     *
     * @return void
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
                    copy((dirname(__file__) . '/views/img/mp_icon.gif'), $file);
                    Configuration::updateValue('MERCADOPAGO_STATUS_' . $key, $order_state->id);
                }
            }
        }

        return true;
    }

    /**
     * Check if the state exist before create another one
     *
     * @param [integer] $id_order_state
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
     * Get mercadopago country links
     *
     * @return array
     */
    public function getCountryLinks()
    {
        $country_links = array();
        $country_links[] = array('id' => 'mld', 'name' => $this->l('Select country'));
        $country_links[] = array('id' => 'mla', 'name' => $this->l('Argentina'));
        $country_links[] = array('id' => 'mlb', 'name' => $this->l('Brazil'));
        $country_links[] = array('id' => 'mlc', 'name' => $this->l('Chile'));
        $country_links[] = array('id' => 'mco', 'name' => $this->l('Colombia'));
        $country_links[] = array('id' => 'mlm', 'name' => $this->l('Mexico'));
        $country_links[] = array('id' => 'mpe', 'name' => $this->l('Peru'));
        $country_links[] = array('id' => 'mlu', 'name' => $this->l('Uruguay'));
        $country_links[] = array('id' => 'mlv', 'name' => $this->l('Venezuela'));

        return $country_links;
    }

    /**
     * Get mercadopago categories
     *
     * @return array
     */
    public function getCategories()
    {
        $ps_categories = array(array('id' => 'no_category', 'name' => $this->l('Select the category')));
        $mp_categories = $this->mpuseful->getCategories();
        $categories = array_merge($ps_categories, $mp_categories);

        return $categories;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO
     *
     * @return void
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Show payment options in version 1.6
     *
     * @param [mixed] $params
     * @return void
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign('module_dir', $this->_path);

        if (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == true) {
            $mp_logo = _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png';
            $redirect = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
                '?fc=module&module=mercadopago&controller=standard&checkout=standard';

            $debito = 0;
            $credito = 0;
            $efectivo = 0;
            $tarjetas = $this->mercadopago->getPaymentMethods();
            foreach ($tarjetas as $tarjeta) {
                if (Configuration::get($tarjeta['config']) != "") {
                    if ($tarjeta['type'] == 'credit_card') {
                        $credito += 1;
                    } elseif ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                        $debito += 1;
                    } else {
                        $efectivo += 1;
                    }
                }
            }

            $this->context->smarty->assign(array(
                "debito" => $debito,
                "mp_logo" => $mp_logo,
                "credito" => $credito,
                "efectivo" => $efectivo,
                "tarjetas" => $tarjetas,
                "redirect" => $redirect,
                "installments" => Configuration::get('MERCADOPAGO_INSTALLMENTS')
            ));

            return $this->display(__file__, 'views/templates/hook/payment_six.tpl');
        }
    }

    /**
     * Show payment options in version 1.7
     *
     * @param [mixed] $params
     * @return void
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == true) {
            $debito = 0;
            $credito = 0;
            $efectivo = 0;
            $tarjetas = $this->mercadopago->getPaymentMethods();
            foreach ($tarjetas as $tarjeta) {
                if (Configuration::get($tarjeta['config']) != "") {
                    if ($tarjeta['type'] == 'credit_card') {
                        $credito += 1;
                    } elseif ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                        $debito += 1;
                    } else {
                        $efectivo += 1;
                    }
                }
            }

            $infoTemplate = $this->context->smarty->assign(array(
                "debito" => $debito,
                "credito" => $credito,
                "efectivo" => $efectivo,
                "tarjetas" => $tarjetas,
                "module_dir" => $this->_path,
                "installments" => Configuration::get('MERCADOPAGO_INSTALLMENTS')
            ))
                ->fetch('module:mercadopago/views/templates/hook/payment_seven.tpl');

            $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $newOption->setCallToActionText($this->l('I want to pay with Mercado Pago without additional cost.'))
                ->setLogo(_MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png')
                ->setAdditionalInformation($infoTemplate)
                ->setAction($this->context->link->getModuleLink($this->name, 'standard'));

            return [$newOption];
        }
    }

    /**
     * Check currency
     *
     * @param [mixed] $cart
     * @return boolean
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
     * @param [mixed] $params
     * @return void
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }
    }

    /**
     * Display payment failure on version 1.6
     *
     * @return void
     */
    public function hookDisplayTopColumn()
    {
        if (Tools::getValue('typeReturn') == 'failure') {
            return $this->display(__FILE__, 'views/templates/hook/failure.tpl');
        }
    }

    /**
     * Display payment failure on version 1.7
     *
     * @return void
     */
    public function hookDisplayWrapperTop()
    {
        if (Tools::getValue('typeReturn') == 'failure') {
            return $this->display(__FILE__, 'views/templates/hook/failure.tpl');
        }
    }
}
