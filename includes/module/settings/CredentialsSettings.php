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
        $title = $this->module->l('Credentials');
        $fields = array(
            array(
                'col' => 4,
                'type' => 'switch',
                'label' => $this->module->l('Sandbox Mode'),
                'name' => 'MERCADOPAGO_SANDBOX_STATUS',
                'is_bool' => true,
                'desc' => $this->module->l('Choose "YES" to test your store before selling. ') .
                    $this->module->l('Switch to "NO" to disable test mode ') .
                    $this->module->l('and start receiving online payments.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_SANDBOX_STATUS_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_SANDBOX_STATUS_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 8,
                'type' => 'html',
                'name' => '',
                'desc' => '',
                'label' => $this->module->l('Upload credentials'),
                'html_content' => '<a href="https://www.mercadopago.com/'
                    . Configuration::get('MERCADOPAGO_COUNTRY_LINK') .
                    '/account/credentials" target="_blank" class="btn btn-default btn-credenciais">'
                    . $this->module->l('Search my credentials') . '</a>'
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_PUBLIC_KEY',
                'label' => $this->module->l('Public Key'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN',
                'label' => $this->module->l('Access token'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => '',
                'name' => 'MERCADOPAGO_PUBLIC_KEY',
                'label' => $this->module->l('Public Key'),
                'required' => true
            ),
            array(
                'col' => 8,
                'type' => 'text',
                'desc' => ' ',
                'name' => 'MERCADOPAGO_ACCESS_TOKEN',
                'label' => $this->module->l('Access token'),
                'required' => true
            ),
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
        $access_token = Tools::getValue('MERCADOPAGO_ACCESS_TOKEN');
        $sandbox_access_token = Tools::getValue('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        //validate the tokens
        $token_validation = $this->mercadopago->isValidAccessToken($access_token);
        $sandbox_token_validation = $this->mercadopago->isValidAccessToken($sandbox_access_token);

        if ($access_token == '' || $token_validation == false) {
            Mercadopago::$form_alert = 'alert-danger';
            MPLog::generate('Invalid APP_USR credentials submitted', 'warning');
        } elseif ($sandbox_access_token == '' || $sandbox_token_validation == false) {
            Mercadopago::$form_alert = 'alert-danger';
            MPLog::generate('Invalid TEST credentials submitted', 'warning');
        } else {
            parent::postFormProcess();
        }

        //activate checkout
        if (Mercadopago::$form_alert == 'alert-danger') {
            Mercadopago::$form_message = $this->module->l('Credentials can not be empty and must be valid. ') . $this->module->l('Please complete your credentials to enable the module.');
        } else {
            if (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == '') {

                Configuration::updateValue('MERCADOPAGO_CHECKOUT_STATUS', true);
                $payment_methods = $this->mercadopago->getPaymentMethods();
                foreach ($payment_methods as $payment_method) {
                    $pm_name = 'MERCADOPAGO_PAYMENT_' . $payment_method['id'];
                    Configuration::updateValue($pm_name, 'on');
                }
            }

            Mercadopago::$form_alert = 'alert-success';
            Mercadopago::$form_message = $this->module->l('Settings saved successfully. Now you can configure the module.');

            $this->sendSettingsInfo();
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
            'MERCADOPAGO_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_PUBLIC_KEY'),
            'MERCADOPAGO_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_ACCESS_TOKEN'),
            'MERCADOPAGO_SANDBOX_STATUS' => Configuration::get('MERCADOPAGO_SANDBOX_STATUS'),
            'MERCADOPAGO_SANDBOX_PUBLIC_KEY' => Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY'),
            'MERCADOPAGO_SANDBOX_ACCESS_TOKEN' => Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN')
        );
    }
}
