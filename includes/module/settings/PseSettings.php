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
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';

class PseSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoPse';
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
        $title = $this->module->l('Basic Configuration', 'PseSettings');
        $fields = [];

        if ($this->module->isEnabledPaymentMethod('pse')) {
            $fields = [
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Payments via PSE', 'PseSettings'),
                    'name' => 'MERCADOPAGO_PSE_CHECKOUT',
                    'desc' => $this->module->l('By deactivating it, you will disable PSE payments from Mercado Pago Transparent Checkout.', 'PseSettings'),
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'MERCADOPAGO_PSE_CHECKOUT_ON',
                            'value' => true,
                            'label' => $this->module->l('Active', 'PseSettings'),
                        ],
                        [
                            'id' => 'MERCADOPAGO_PSE_CHECKOUT_OFF',
                            'value' => false,
                            'label' => $this->module->l('Inactive', 'PseSettings'),
                        ],
                    ],
                ],
                [
                    'col' => 2,
                    'suffix' => '%',
                    'type' => 'text',
                    'name' => PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME,
                    'label' => $this->module->l('Discount for purchase', 'PseSettings'),
                    'desc' => $this->module->l('Offer a special discount to encourage your ', 'PseSettings') .
                        $this->module->l('customers to make the purchase with Mercado Pago.', 'PseSettings'),
                ],
            ];
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
            PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME => 'percentage',
        ];

        parent::postFormProcess();
        MPLog::generate('PSE checkout configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $formValues = [
            PseCheckout::PSE_CHECKOUT_NAME => Configuration::get(PseCheckout::PSE_CHECKOUT_NAME),
            PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME => Configuration::get(PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME),
        ];

        return $formValues;
    }
}
