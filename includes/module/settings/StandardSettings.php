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

class StandardSettings extends AbstractSettings
{
    public $online_payments;
    public $offline_payments;

    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoStandard';
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
        $title = $this->module->l('Basic Configuration');
        $fields = array(
            array(
                'type' => 'switch',
                'label' => $this->module->l('Activate checkout'),
                'name' => 'MERCADOPAGO_STANDARD_CHECKOUT',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_STANDARD_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_STANDARD_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 4,
                'type' => 'checkbox',
                'label' => $this->module->l('Payment methods'),
                'name' => 'MERCADOPAGO_PAYMENT',
                'hint' => $this->module->l('Select the payment methods available in your store.'),
                'class' => 'payment-online-checkbox',
                'desc' => ' ',
                'values' => array(
                    'query' => $this->online_payments,
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 4,
                'type' => 'checkbox',
                'name' => 'MERCADOPAGO_PAYMENT',
                'class' => 'payment-offline-checkbox',
                'desc' => $this->module->l('Activate the payment alternatives you prefer for your customers.'),
                'values' => array(
                    'query' => $this->offline_payments,
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Maximum of installments'),
                'name' => 'MERCADOPAGO_INSTALLMENTS',
                'desc' => $this->module->l('What is the maximum of installments which a customer can buy?'),
                'options' => array(
                    'query' => $this->getInstallments(24),
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
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
                'name' => 'MERCADOPAGO_STANDARD_BINARY_MODE',
                'is_bool' => true,
                'desc' => $this->module->l('Accept and reject payments automatically. Do you want us to activate it? '),
                'hint' => $this->module->l('If you activate the binary mode ') .
                    $this->module->l('you will not be able to leave pending payments. ') .
                    $this->module->l('This can affect the prevention of fraud. ') .
                    $this->module->l('Leave it inactive to be protected by our own tool.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_STANDARD_BINARY_MODE_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_STANDARD_BINARY_MODE_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 2,
                'suffix' => 'hours',
                'type' => 'text',
                'name' => 'MERCADOPAGO_EXPIRATION_DATE_TO',
                'label' => $this->module->l('Save payment preferences during '),
                'hint' => $this->module->l('Payment links are generated every time we receive ') .
                    $this->module->l('data of a purchase intention of your customers. ') .
                    $this->module->l('We keep that information for a period of time not to ') .
                    $this->module->l('ask for the data each time you return to the purchase process. ') .
                    $this->module->l('Choose when you want us to forget it.'),
                'desc' => ' ',
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
        $this->validate = (['MERCADOPAGO_EXPIRATION_DATE_TO' => 'expiration_preference']);

        parent::postFormProcess();

        Configuration::updateValue('MERCADOPAGO_STANDARD', true);

        $this->sendSettingsInfo();
        MPLog::generate('Standard checkout configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $form_values = array(
            'MERCADOPAGO_INSTALLMENTS' => Configuration::get('MERCADOPAGO_INSTALLMENTS'),
            'MERCADOPAGO_STANDARD_CHECKOUT' => Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT'),
            'MERCADOPAGO_AUTO_RETURN' => Configuration::get('MERCADOPAGO_AUTO_RETURN'),
            'MERCADOPAGO_STANDARD_BINARY_MODE' => Configuration::get('MERCADOPAGO_STANDARD_BINARY_MODE'),
            'MERCADOPAGO_EXPIRATION_DATE_TO' => Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO'),
        );

        $payment_methods = $this->mercadopago->getPaymentMethods();
        foreach ($payment_methods as $payment_method) {
            $pm_id = $payment_method['id'];
            $pm_name = 'MERCADOPAGO_PAYMENT_' . $pm_id;

            if (
                $payment_method['type'] == 'credit_card' ||
                $payment_method['type'] == 'debit_card' ||
                $payment_method['type'] == 'prepaid_card'
            ) {
                $this->online_payments[] = array(
                    'id' => $pm_id,
                    'name' => $payment_method['name'],
                );
            } else {
                $this->offline_payments[] = array(
                    'id' => $pm_id,
                    'name' => $payment_method['name'],
                );
            }

            $form_values[$pm_name] = Configuration::get($pm_name);
        }

        return $form_values;
    }

    /**
     * Get installments
     *
     * @param int $max
     * @return void
     */
    public function getInstallments($max)
    {
        $installments = array();
        for ($i = $max; $i > 0; $i--) {
            $installments[] = array('id' => $i, 'name' => $i);
        }

        return $installments;
    }
}
