<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/includes/module/settings/AbstractSettings.php';

class CredentialsSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoCredentials';
        $this->values = $this->getFormValues();
        $this->form = $this->generateForm();
        $this->process = $this->verifyPostProcess();
    }

    /**
     * Generate inputs form
     *
     * @return void
     */
    public function generateForm()
    {
        $title = $this->module->l('Credentials', 'CredentialsSettings');
        $fields = array(
            array(
                'col' => 4,
                'type' => 'switch',
                'label' => $this->module->l('Production', 'CredentialsSettings'),
                'name' => 'MERCADOPAGO_PROD_STATUS',
                'is_bool' => true,
                'desc' => $this->module->l('Select "YES" only when you are ready to sell. ', 'CredentialsSettings') .
                    $this->module->l('Change to NO to activate the Sandbox ', 'CredentialsSettings') .
                    $this->module->l('test environment.', 'CredentialsSettings'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_PROD_STATUS_ON',
                        'value' => true,
                        'label' => $this->module->l('Yes', 'CredentialsSettings')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_PROD_STATUS_OFF',
                        'value' => false,
                        'label' => $this->module->l('No', 'CredentialsSettings')
                    )
                ),
            ),
            array(
                'col' => 8,
                'type' => 'html',
                'name' => '',
                'desc' => '',
                'label' => $this->module->l('Load credentials', 'CredentialsSettings'),
                'html_content' => '<a href="https://www.mercadopago.com/'
                    . Configuration::get('MERCADOPAGO_COUNTRY_LINK') .
                    '/account/credentials" target="_blank" class="btn btn-default mp-btn-credenciais">'
                    . $this->module->l('Search my credentials', 'CredentialsSettings') . '</a>'
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_PUBLIC_KEY',
                'label' => $this->module->l('Public Key', 'CredentialsSettings'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => ' ',
                'name' => 'MERCADOPAGO_ACCESS_TOKEN',
                'label' => $this->module->l('Access token', 'CredentialsSettings'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_PUBLIC_KEY',
                'label' => $this->module->l('Public Key', 'CredentialsSettings'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN',
                'label' => $this->module->l('Access token', 'CredentialsSettings'),
                'required' => true
            )
        );

        return $this->buildForm($title, $fields);
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        $this->validate = ([
            'MERCADOPAGO_PUBLIC_KEY' => 'public_key',
            'MERCADOPAGO_ACCESS_TOKEN' => 'access_token',
            'MERCADOPAGO_SANDBOX_PUBLIC_KEY' => 'public_key',
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => 'access_token',
        ]);

        parent::postFormProcess();

        //activate checkout
        if (Mercadopago::$form_alert != 'alert-danger') {
            $mp_check = Configuration::get('MERCADOPAGO_CHECK_CREDENTIALS');
            $payment_methods = $this->mercadopago->getPaymentMethods();
            foreach ($payment_methods as $payment_method) {
                $pm_name = 'MERCADOPAGO_PAYMENT_' . $payment_method['id'];
                if ($mp_check == "") {
                    Configuration::updateValue($pm_name, 'on');
                }

                if ($payment_method['type'] != 'credit_card' &&
                    $payment_method['type'] != 'debit_card' &&
                    $payment_method['type'] != 'prepaid_card' &&
                    !in_array($payment_method['id'], $this->getTicketExcludedMethods())
                ) {
                    $pm_name = 'MERCADOPAGO_TICKET_PAYMENT_' . $payment_method['id'];
                    if ($mp_check == "") {
                        Configuration::updateValue($pm_name, 'on');
                    }
                }
            }

            Mercadopago::$form_message = $this->module->l('Settings saved successfully. Now you can configure the module.', 'CredentialsSettings');

            Configuration::updateValue('MERCADOPAGO_CHECK_CREDENTIALS', true);
            MPLog::generate('Credentials saved successfully');
        }
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return array(
            'MERCADOPAGO_PROD_STATUS' => Configuration::get('MERCADOPAGO_PROD_STATUS'),
            'MERCADOPAGO_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_PUBLIC_KEY'),
            'MERCADOPAGO_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_ACCESS_TOKEN'),
            'MERCADOPAGO_SANDBOX_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY'),
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN')
        );
    }

    /**
     * Validate credentials and save seller information
     *
     * @param string $input
     * @param string $value
     * @return boolean
     */
    public function validateCredentials($input, $value)
    {
        $token_validation = $this->mercadopago->isValidAccessToken($value);
        if (!$token_validation) {
            return false;
        }

        $credentialsWrapper = $this->mercadopago->getCredentialsWrapper($value);
        if (!$credentialsWrapper) {
            return false;
        }

        if ($input == 'MERCADOPAGO_ACCESS_TOKEN') {
            Configuration::updateValue('MERCADOPAGO_APPLICATION_ID', $credentialsWrapper['client_id']);
            Configuration::updateValue('MERCADOPAGO_SELLER_ID', $token_validation['id']);
            Configuration::updateValue('MERCADOPAGO_SITE_ID', $token_validation['site_id']);
        }

        return true;
    }
}
