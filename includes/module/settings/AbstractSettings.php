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
     * @return array
     */
    public function buildForm($title, $fields)
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $title,
                    'icon' => 'icon-cogs',
                ],
                'class' => 'credentials',
                'input' => $fields,
                'submit' => [
                    'title' => $this->module->l('Save', 'AbstractSettings'),
                ],
            ],
        ];
    }

    /**
     * Verify form submit
     *
     * @return bool
     */
    public function verifyPostProcess()
    {
        if (((bool) Tools::isSubmit($this->submit)) == true) {
            $this->postFormProcess();

            return true;
        }

        return false;
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
            $value = htmlentities(strip_tags(Tools::getValue($key)), ENT_QUOTES, 'UTF-8');

            if (!$this->validateInput($key, $value)) {
                $form_alert = true;
                continue;
            }

            $this->values[$key] = $value;
            Configuration::updateValue($key, $value);
        }

        if ($form_alert == false) {
            Mercadopago::$form_alert = 'alert-success';
            Mercadopago::$form_message = $this->module->l('Settings saved successfully.', 'AbstractSettings');
        }
    }

    /**
     * Get ticket excluded payment methods
     * Enter the ids (uppercase) of the payment methods that must be removed to avoid errors
     *
     * @return array
     */
    public function getTicketExcludedMethods()
    {
        return [
            'PAYPAL', 'PSE',
        ];
    }

    /**
     * Validate input for submit
     *
     * @param mixed $input
     *
     * @return bool
     */
    public function validateInput($input, $value)
    {
        if ($this->validate != null && array_key_exists($input, $this->validate)) {
            switch ($this->validate[$input]) {
                case 'expiration_preference':
                    if ($value != '' && !is_numeric($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message .= $this->module->l(
                            'The time to save payment preferences ',
                            'AbstractSettings'
                        ) . $this->module->l('must be an integer.', 'AbstractSettings');
                        MPLog::generate('Invalid expiration_date_to submitted', 'warning');

                        return false;
                    }
                    break;

                case 'public_key':
                    if ($value == '') {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l('Credentials can not be empty and must be valid. ', 'AbstractSettings') .
                        $this->module->l('Please complete your credentials to enable the module.', 'AbstractSettings');
                        MPLog::generate('Invalid ' . $input . ' submitted', 'warning');

                        return false;
                    }
                    break;

                case 'access_token':
                    if (!$this->validateCredentials($input, $value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l('Credentials can not be empty and must be valid. ', 'AbstractSettings') .
                        $this->module->l('Please complete your credentials to enable the module.', 'AbstractSettings');
                        MPLog::generate('Invalid ' . $input . ' submitted', 'warning');

                        return false;
                    }
                    break;

                case 'percentage':
                    if ($value != '' && is_numeric($value) && $value > 99 || $value != '' && !is_numeric($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message = $this->module->l(
                            'Discount must be an integer and less than 100%',
                            'AbstractSettings'
                        );
                        MPLog::generate('Invalid discount submitted', 'warning');

                        return false;
                    }
                    break;

                case 'payment_due':
                    if ($value != '' && !is_numeric($value)) {
                        Mercadopago::$form_alert = 'alert-danger';
                        Mercadopago::$form_message .= $this->module->l(
                            'The payment due must be an integer.',
                            'AbstractSettings'
                        );
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
     * Validate credentials
     *
     * Base no-op that treats the value as valid. Only CredentialsSettings maps
     * the 'access_token' rule to real credential validation, so it overrides this.
     *
     * @param string $input
     * @param string $value
     *
     * @return bool
     */
    public function validateCredentials($input, $value)
    {
        return true;
    }
}
