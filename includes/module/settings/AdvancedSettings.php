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

class AdvancedSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoAdvanced';
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
        $title = $this->module->l('Advanced configuration');
        $fields = array(
            array(
                'type' => 'switch',
                'label' => $this->module->l('Return to the store'),
                'name' => 'MERCADOPAGO_AUTO_RETURN',
                'is_bool' => true,
                'desc' => $this->module->l('Do you want your client to come back to ') .
                    $this->module->l('the store after finishing the purchase?'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_AUTO_RETURN_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_AUTO_RETURN_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Binary Mode'),
                'name' => 'MERCADOPAGO_BINARY_MODE',
                'is_bool' => true,
                'desc' => $this->module->l('Accept and reject payments automatically. Do you want us to activate it? '),
                'hint' => $this->module->l('If you activate the binary mode ') .
                    $this->module->l('you will not be able to leave pending payments. ') .
                    $this->module->l('This can affect the prevention of fraud. ') .
                    $this->module->l('Leave it inactive to be protected by our own tool.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_BINARY_MODE_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_BINARY_MODE_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 2,
                'suffix' => 'horas',
                'type' => 'text',
                'name' => 'MERCADOPAGO_EXPIRATION_DATE_TO',
                'label' => $this->module->l('Save payment preferences during '),
                'hint' => $this->module->l('Payment links are generated every time we receive ') .
                    $this->module->l('data of a purchase intention of your customers. ') .
                    $this->module->l('We keep that information for a period of time not to ') .
                    $this->module->l('ask for the data each time you return to the purchase process. ') .
                    $this->module->l('Choose when you want us to forget it.'),
                'desc' => ' ',
            ),
            array(
                'col' => 2,
                'type' => 'text',
                'name' => 'MERCADOPAGO_SPONSOR_ID',
                'label' => $this->module->l('Sponsor ID'),
                'desc' => $this->module->l('With this number we identify all your transactions ') .
                    $this->module->l('and we know how many sales we process with your account.'),
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
        $sponsor_id = Tools::getValue('MERCADOPAGO_SPONSOR_ID');
        $expiration_date = Tools::getValue('MERCADOPAGO_EXPIRATION_DATE_TO');

        if ($expiration_date != '' && !is_numeric($expiration_date)) {
            Mercadopago::$form_alert = 'alert-danger';
            Mercadopago::$form_message .= $this->module->l('The time to save payment preferences ') . $this->module->l('must be an integer.');
            MPLog::generate('Invalid expiration_date_to submitted', 'warning');
        } elseif ($sponsor_id != '' && !$this->mercadopago->isValidSponsorId($sponsor_id)) {
            Mercadopago::$form_alert = 'alert-danger';
            Mercadopago::$form_message .= $this->module->l('Sponsor ID must be valid and ') . $this->module->l('must be from the same country as the seller.');
            MPLog::generate('Invalid sponsor_id submitted', 'warning');
        } else {
            parent::postFormProcess();
        }

        MPLog::generate('Advanced configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return array(
            'MERCADOPAGO_AUTO_RETURN' => Configuration::get('MERCADOPAGO_AUTO_RETURN'),
            'MERCADOPAGO_BINARY_MODE' => Configuration::get('MERCADOPAGO_BINARY_MODE'),
            'MERCADOPAGO_SPONSOR_ID' => Configuration::get('MERCADOPAGO_SPONSOR_ID'),
            'MERCADOPAGO_EXPIRATION_DATE_TO' => Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO'),
        );
    }
}
