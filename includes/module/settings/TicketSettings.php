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

class TicketSettings extends AbstractSettings
{
    public $ticket_payments;

    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoTicket';
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
                'name' => 'MERCADOPAGO_TICKET_CHECKOUT',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_TICKET_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_TICKET_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 4,
                'type' => 'checkbox',
                'label' => $this->module->l('Payment methods'),
                'name' => 'MERCADOPAGO_TICKET_PAYMENT',
                'hint' => $this->module->l('Select the payment methods available in your store.'),
                'class' => 'payment-ticket-checkbox',
                'desc' => ' ',
                'values' => array(
                    'query' => $this->ticket_payments,
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 2,
                'suffix' => 'days',
                'label' => $this->module->l('Payment due'),
                'type' => 'text',
                'name' => 'MERCADOPAGO_TICKET_EXPIRATION',
                'desc' => 'In how many days payments will expire.',
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Discount coupons'),
                'name' => 'MERCADOPAGO_TICKET_COUPON',
                'is_bool' => true,
                'desc' => $this->module->l('Will you offer discount coupons to customers who buy with Mercado Pago?'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_TICKET_COUPON_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_TICKET_COUPON_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 4,
                'type' => 'switch',
                'label' => $this->module->l('Reduce inventory'),
                'name' => 'MERCADOPAGO_TICKET_INVENTORY',
                'is_bool' => true,
                'desc' => $this->module->l('Active the inventory reduction while creating an order, ').
                    $this->module->l('if approved or not the final payment. '). 
                    $this->module->l('Disable this option to reduce it only when payments are approved.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_TICKET_COUPON_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_TICKET_COUPON_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
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
        $this->validate = (['MERCADOPAGO_TICKET_EXPIRATION' => 'expiration_preference']);

        parent::postFormProcess();

        $this->sendSettingsInfo();
        MPLog::generate('Ticket checkout configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $form_values = array(
            'MERCADOPAGO_TICKET_CHECKOUT' => Configuration::get('MERCADOPAGO_TICKET_CHECKOUT'),
            'MERCADOPAGO_TICKET_EXPIRATION' => Configuration::get('MERCADOPAGO_TICKET_EXPIRATION'),
            'MERCADOPAGO_TICKET_COUPON' => Configuration::get('MERCADOPAGO_TICKET_COUPON'),
            'MERCADOPAGO_TICKET_INVENTORY' => Configuration::get('MERCADOPAGO_TICKET_INVENTORY'),
        );

        $payment_methods = $this->mercadopago->getPaymentMethods();
        foreach ($payment_methods as $payment_method) {
            $pm_id = $payment_method['id'];
            $pm_name = 'MERCADOPAGO_TICKET_PAYMENT_' . $pm_id;

            if (
                $payment_method['type'] != 'credit_card' &&
                $payment_method['type'] != 'debit_card' &&
                $payment_method['type'] != 'prepaid_card'
            ) {
                $this->ticket_payments[] = array(
                    'id' => $pm_id,
                    'name' => $payment_method['name'],
                );
            }

            $form_values[$pm_name] = Configuration::get($pm_name);
        }

        return $form_values;
    }
}
