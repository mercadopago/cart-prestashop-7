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
                'label' => $this->module->l('Activate Checkout of face to face payments'),
                'name' => 'MERCADOPAGO_TICKET_CHECKOUT',
                'desc' => $this->module->l('Activate the option of face to face payments in your store.'),
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
                'hint' => $this->module->l('Enable the payment methods available to your customers.'),
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
                'desc' => $this->module->l('In how many days will the face to face payments expire.'),
            ),
            array(
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_TICKET_DISCOUNT',
                'label' => $this->module->l('Discount for purchase'),
                'desc' => $this->module->l('Offer a special discount to encourage your ') .
                    $this->module->l('customers to make the purchase with Mercado Pago.'),
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
        $this->validate = ([
            'MERCADOPAGO_TICKET_DISCOUNT' => 'percentage',
            'MERCADOPAGO_TICKET_EXPIRATION' => 'payment_due',
        ]);

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
            'MERCADOPAGO_TICKET_DISCOUNT' => Configuration::get('MERCADOPAGO_TICKET_DISCOUNT'),
            'MERCADOPAGO_TICKET_EXPIRATION' => Configuration::get('MERCADOPAGO_TICKET_EXPIRATION'),
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
