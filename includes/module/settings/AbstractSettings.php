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

class AbstractSettings
{
    public $form;
    public $module;
    public $values;
    public $submit;
    public $process;
    public $mercadopago;
    protected $validate;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Build Config Form
     *
     * @return void
     */
    public function buildForm($title, $fields)
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $title,
                    'icon' => 'icon-cogs',
                ),
                'class' => 'credentials',
                'input' => $fields,
                'submit' => array(
                    'title' => $this->module->l('Save')
                ),
            ),
        );
    }

    /**
     * Verify form submit
     *
     * @return void
     */
    public function verifyPostProcess()
    {
        if (((bool) Tools::isSubmit($this->submit)) == true) {
            return $this->postFormProcess();
        }
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        $form_alert = false;

        foreach (array_keys($this->values) as $key) {
            $value = Tools::getValue($key);

            if (!$this->validateInput($key, $value)) {
                $form_alert = true;
                continue;
            }

            $this->values[$key] = $value;
            Configuration::updateValue($key, $value);
        }

        if ($form_alert == false) {
            Mercadopago::$form_alert = 'alert-success';
            Mercadopago::$form_message = $this->module->l('Settings saved successfully.');
        }
    }

    /**
     * Validate input for submit
     *
     * @param mixed $input
     * @return void
     */
    public function validateInput($input, $value)
    {
        if ($this->validate != null && array_key_exists($input, $this->validate)) {
            switch ($this->validate[$input]) {
                case "sponsor_id":
                    if ($value != '' && !$this->mercadopago->isValidSponsorId($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message .= $this->module->l('Sponsor ID must be valid and ') . $this->module->l('must be from the same country as the seller.');
                        MPLog::generate('Invalid sponsor_id submitted', 'warning');
                        return false;
                    }
                    break;

                case "expiration_preference";
                    if ($value != '' && !is_numeric($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message .= $this->module->l('The time to save payment preferences ') . $this->module->l('must be an integer.');
                        MPLog::generate('Invalid expiration_date_to submitted', 'warning');
                        return false;
                    }
                    break;

                case "public_key":
                    if ($value == '') {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l('Credentials can not be empty and must be valid. ') . $this->module->l('Please complete your credentials to enable the module.');
                        MPLog::generate('Invalid ' . $input . ' submitted', 'warning');
                        return false;
                    }
                    break;

                case "access_token":
                    if (!$this->validateCredentials($input, $value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l('Credentials can not be empty and must be valid. ') . $this->module->l('Please complete your credentials to enable the module.');
                        MPLog::generate('Invalid ' . $input . ' submitted', 'warning');
                        return false;
                    }
                    break;

                case "percentage":
                    if ($value != '' && !is_int($value) && $value > 99) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l('Commission and discount must be an integer and less than 100%');
                        MPLog::generate('Invalid commission or discount submitted', 'warning');
                        return false;
                    }
                    break;

                case "payment_due";
                    if ($value != '' && !is_numeric($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message .= $this->module->l('The payment due must be an integer.');
                        MPLog::generate('Invalid payment_due submitted', 'warning');
                        return false;
                    }
                    break;

                default:
                    return true;
            }
        }

        return true;
    }

    /**
     * Send info to settings api
     *
     * @return void
     */
    public function sendSettingsInfo()
    {
        $checkout_basic = (Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT') == true) ? 'true' : 'false';

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
}
