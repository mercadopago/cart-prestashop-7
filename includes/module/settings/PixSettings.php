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

class PixSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoPix';
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
        $title = $this->module->l('Basic Configuration', 'PixSettings');

        if ($this->module->isEnabledPaymentMethod('pix')) {
            $fields = [
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Payments via Pix', 'PixSettings'),
                    'name' => 'MERCADOPAGO_PIX_CHECKOUT',
                    'desc' => $this->module->l('Allow clients to pay via Pix in the store checkout.', 'PixSettings'),
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'MERCADOPAGO_PIX_CHECKOUT_ON',
                            'value' => true,
                            'label' => $this->module->l('Active', 'PixSettings'),
                        ],
                        [
                            'id' => 'MERCADOPAGO_PIX_CHECKOUT_OFF',
                            'value' => false,
                            'label' => $this->module->l('Inactive', 'PixSettings'),
                        ],
                    ],
                ],
                [
                    'col' => 2,
                    'type' => 'select',
                    'label' => $this->module->l('Expiration', 'PixSettings'),
                    'name' => 'MERCADOPAGO_PIX_EXPIRATION',
                    'desc' => $this->module->l('Adjust the deadline that your clients will have to make the transfer via Pix.', 'PixSettings'),
                    'options' => [
                        'query' => $this->getDueDate(),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'col' => 2,
                    'suffix' => '%',
                    'type' => 'text',
                    'name' => 'MERCADOPAGO_PIX_DISCOUNT',
                    'label' => $this->module->l('Discount per purchase via Pix', 'PixSettings'),
                    'desc' => $this->module->l('Enter the percentage of the discount to encourage your clients to pay via Pix.', 'PixSettings'),
                ],
            ];
        } else {
            $fields = [];
        }

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
            'MERCADOPAGO_PIX_DISCOUNT' => 'percentage',
            'MERCADOPAGO_PIX_EXPIRATION' => 'payment_due',
        ];

        parent::postFormProcess();
        MPLog::generate('Pix checkout configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $formValues = [
            'MERCADOPAGO_PIX_CHECKOUT' => Configuration::get('MERCADOPAGO_PIX_CHECKOUT'),
            'MERCADOPAGO_PIX_DISCOUNT' => Configuration::get('MERCADOPAGO_PIX_DISCOUNT'),
            'MERCADOPAGO_PIX_EXPIRATION' => Configuration::get('MERCADOPAGO_PIX_EXPIRATION'),
        ];

        return $formValues;
    }

    /**
     * Get due date
     *
     * @return array
     */
    public function getDueDate()
    {
        $dueDate = [
            ['id' => '30', 'name' => '30 ' . $this->module->l('minutes', 'PixSettings')],
            ['id' => '60', 'name' => '1 ' . $this->module->l('hour', 'PixSettings')],
            ['id' => '360', 'name' => '6 ' . $this->module->l('hours', 'PixSettings')],
            ['id' => '720', 'name' => '12 ' . $this->module->l('hours', 'PixSettings')],
            ['id' => '1440', 'name' => '1 ' . $this->module->l('day', 'PixSettings')],
            ['id' => '10080', 'name' => '7 ' . $this->module->l('days', 'PixSettings')],
        ];

        return $dueDate;
    }
}
