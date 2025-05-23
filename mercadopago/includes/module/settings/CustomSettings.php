<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/includes/module/settings/AbstractSettings.php';

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
     * @return void
     */
    public function generateForm()
    {
        $title = $this->module->l('Basic Configuration', 'CustomSettings');
        $fields = array(
            array(
                'type' => 'switch',
                'label' => $this->module->l('Activate checkout', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_CHECKOUT',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.', 'CustomSettings'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Activate payments with cards saved in Mercado Pago', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON',
                'desc' => $this->module->l('With this feature, clients pay faster and you increase your sales.', 'CustomSettings'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_WALLET_BUTTON_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Binary Mode', 'CustomSettings'),
                'name' => 'MERCADOPAGO_CUSTOM_BINARY_MODE',
                'is_bool' => true,
                'desc' => $this->module->l('Approve or reject payments instantly and automatically, ', 'CustomSettings').
                    $this->module->l('without pending or under review status. Do you want us to activate it?', 'CustomSettings'),
                'hint' => $this->module->l('Activating it can affect fraud prevention. ', 'CustomSettings') .
                    $this->module->l('Leave it inactive so we can take ', 'CustomSettings') .
                    $this->module->l('care of your charges', 'CustomSettings'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'CustomSettings')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'CustomSettings')
                    )
                ),
            ),
            array(
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_CUSTOM_DISCOUNT',
                'label' => $this->module->l('Discount for purchase', 'CustomSettings'),
                'desc' => $this->module->l('Offer a special discount to encourage your ', 'CustomSettings') .
                    $this->module->l('customers to make the purchase with Mercado Pago.', 'CustomSettings'),
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
            'MERCADOPAGO_CUSTOM_DISCOUNT' => 'percentage'
        ]);

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
        return array(
            'MERCADOPAGO_CUSTOM_CHECKOUT' => Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT'),
            'MERCADOPAGO_CUSTOM_WALLET_BUTTON' => Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON'),
            'MERCADOPAGO_CUSTOM_DISCOUNT' => Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT'),
            'MERCADOPAGO_CUSTOM_BINARY_MODE' => Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE'),
        );
    }
}
