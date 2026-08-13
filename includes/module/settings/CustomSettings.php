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

class CustomSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoCustom';
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
        $title = $this->module->l('Basic Configuration', 'CustomSettings');
        $fields = [
            [
                'type' => 'switch',
                'label' => $this->module->l('Activate checkout', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_CHECKOUT',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.', 'CustomSettings'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings'),
                    ],
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->module->l('Activate payments with cards saved in Mercado Pago', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON',
                'desc' => $this->module->l('With this feature, clients pay faster and you increase your sales.', 'CustomSettings'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings'),
                    ],
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->module->l('Binary Mode', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_BINARY_MODE',
                'is_bool' => true,
                'desc' => $this->module->l('Approve or reject payments instantly and automatically, ', 'CustomSettings') .
                    $this->module->l('without pending or under review status. Do you want us to activate it?', 'CustomSettings'),
                'hint' => $this->module->l('Activating it can affect fraud prevention. ', 'CustomSettings') .
                    $this->module->l('Leave it inactive so we can take ', 'CustomSettings') .
                    $this->module->l('care of your charges', 'CustomSettings'),
                'values' => [
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings'),
                    ],
                    [
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings'),
                    ],
                ],
            ],
            [
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_CUSTOM_DISCOUNT',
                'label' => $this->module->l('Discount for purchase', 'CustomSettings'),
                'desc' => $this->module->l('Offer a special discount to encourage your ', 'CustomSettings') .
                    $this->module->l('customers to make the purchase with Mercado Pago.', 'CustomSettings'),
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
            'MERCADOPAGO_CUSTOM_DISCOUNT' => 'percentage',
        ];

        parent::postFormProcess();

        MPLog::generate('Custom checkout configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return [
            'MERCADOPAGO_CUSTOM_CHECKOUT' => Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT'),
            'MERCADOPAGO_CUSTOM_WALLET_BUTTON' => Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON'),
            'MERCADOPAGO_CUSTOM_DISCOUNT' => Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT'),
            'MERCADOPAGO_CUSTOM_BINARY_MODE' => Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE'),
        ];
    }
}
