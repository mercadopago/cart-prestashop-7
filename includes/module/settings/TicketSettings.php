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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/AbstractSettings.php';

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
     * @return array
     */
    public function generateForm()
    {
        $title = $this->module->l('Basic Configuration', 'TicketSettings');
        $fields = [
            [
                'type' => 'switch',
                'label' => $this->module->l('Activate Checkout of face to face payments', 'TicketSettings'),
                'name' => 'MERCADOPAGO_TICKET_CHECKOUT',
                'desc' => $this->module->l('Activate the option of face to face payments in your store.', 'TicketSettings'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'MERCADOPAGO_TICKET_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'TicketSettings'),
                    ],
                    [
                        'id' => 'MERCADOPAGO_TICKET_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'TicketSettings'),
                    ],
                ],
            ],
            [
                'col' => 4,
                'type' => 'checkbox',
                'label' => $this->module->l('Payment methods', 'TicketSettings'),
                'name' => 'MERCADOPAGO_TICKET_PAYMENT',
                'hint' => $this->module->l('Enable the payment methods available to your customers.', 'TicketSettings'),
                'class' => 'payment-ticket-checkbox',
                'desc' => ' ',
                'values' => [
                    'query' => $this->ticket_payments,
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'col' => 2,
                'suffix' => $this->module->l('days', 'TicketSettings'),
                'label' => $this->module->l('Payment due', 'TicketSettings'),
                'type' => 'text',
                'name' => 'MERCADOPAGO_TICKET_EXPIRATION',
                'desc' => $this->module->l('In how many days will the face to face payments expire.', 'TicketSettings'),
            ],
            [
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_TICKET_DISCOUNT',
                'label' => $this->module->l('Discount for purchase', 'TicketSettings'),
                'desc' => $this->module->l('Offer a special discount to encourage your ', 'TicketSettings') .
                    $this->module->l('customers to make the purchase with Mercado Pago.', 'TicketSettings'),
            ],
        ];

        return $this->buildForm($title, $fields);
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        $this->validate = [
            'MERCADOPAGO_TICKET_DISCOUNT' => 'percentage',
            'MERCADOPAGO_TICKET_EXPIRATION' => 'payment_due',
        ];

        if ($this->validatePaymentMethods()) {
            parent::postFormProcess();
            MPLog::generate('Ticket checkout configuration saved successfully');
        }
    }

    /**
     * Validates if at least one payment method is checked
     *
     * @return bool
     */
    public function validatePaymentMethods()
    {
        $count_total = 0;
        $count_checked = 0;
        $payment_methods = array_keys($this->values);

        foreach ($payment_methods as $key) {
            if (strstr($key, 'MERCADOPAGO_TICKET_PAYMENT_')) {
                ++$count_total;
                if (Tools::getValue($key) == '') {
                    ++$count_checked;
                }
            }
        }

        if ($count_checked == $count_total) {
            Mercadopago::$form_alert = 'alert-danger';
            Mercadopago::$form_message = $this->module->l('It is not possible to remove ', 'TicketSettings') .
                $this->module->l('all payment methods for ticket checkout.', 'TicketSettings');

            return false;
        }

        return true;
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $form_values = [
            'MERCADOPAGO_TICKET_CHECKOUT' => Configuration::get('MERCADOPAGO_TICKET_CHECKOUT'),
            'MERCADOPAGO_TICKET_DISCOUNT' => Configuration::get('MERCADOPAGO_TICKET_DISCOUNT'),
            'MERCADOPAGO_TICKET_EXPIRATION' => Configuration::get('MERCADOPAGO_TICKET_EXPIRATION'),
        ];

        $payment_methods = $this->mercadopago->getPaymentMethods();
        foreach ($payment_methods as $payment_method) {
            if (in_array($payment_method['type'], TicketCheckout::ALLOW_PAYMENT_METHOD_TYPES)
                && Tools::strtolower($payment_method['id']) != 'meliplace'
                && !in_array($payment_method['id'], $this->getTicketExcludedMethods())
            ) {
                $pm_id = $payment_method['id'];
                $pm_name = 'MERCADOPAGO_TICKET_PAYMENT_' . $pm_id;
                $payment_places = [];

                if (isset($payment_method['payment_places']) && is_array($payment_method['payment_places'])) {
                    foreach ($payment_method['payment_places'] as $payment_place) {
                        $payment_places[] = $payment_place['name'];
                    }
                    $payment_places = implode(', ', $payment_places);
                }

                $this->ticket_payments[] = [
                    'id' => $pm_id,
                    'name' => $payment_places ? $payment_method['name'] . ' ( ' . $payment_places . ' )' : $payment_method['name'],
                ];

                $form_values[$pm_name] = Configuration::get($pm_name);
            }
        }

        return $form_values;
    }
}
