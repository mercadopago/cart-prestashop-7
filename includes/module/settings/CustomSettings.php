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
        $title = $this->module->l('Basic Configuration');
        $fields = array(
            array(
                'type' => 'switch',
                'label' => $this->module->l('Activate checkout'),
                'name' => 'MERCADOPAGO_CUSTOM_CHECKOUT',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Binary Mode'),
                'name' => 'MERCADOPAGO_CUSTOM_BINARY_MODE',
                'is_bool' => true,
                'desc' => $this->module->l('Accept and reject payments automatically. Do you want us to activate it? '),
                'hint' => $this->module->l('If you activate the binary mode ') .
                    $this->module->l('you will not be able to leave pending payments. ') .
                    $this->module->l('This can affect the prevention of fraud. ') .
                    $this->module->l('Leave it inactive to be protected by our own tool.'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_BINARY_MODE_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'type' => 'switch',
                'label' => $this->module->l('Discount coupons'),
                'name' => 'MERCADOPAGO_CUSTOM_COUPON',
                'is_bool' => true,
                'desc' => $this->module->l('Will you offer discount coupons to customers who buy with Mercado Pago?'),
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_COUPON_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CUSTOM_COUPON_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_CUSTOM_DISCOUNT',
                'label' => $this->module->l('Discounts for purchase with Mercado Pago'),
                'desc' => $this->module->l('Choose a percentage value that you want to discount ') . $this->module->l('your customers for paying with Mercado Pago.'),
            ),
            array(
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_CUSTOM_COMISSION',
                'label' => $this->module->l('Commission for purchase with Mercado Pago'),
                'desc' => $this->module->l('Choose an additional percentage value that you want to charge ') . $this->module->l('as commission to your customers for paying with Mercado Pago.'),
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
            'MERCADOPAGO_CUSTOM_DISCOUNT' => 'percentage',
            'MERCADOPAGO_CUSTOM_COMISSION' => 'percentage'
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
            'MERCADOPAGO_CUSTOM_BINARY_MODE' => Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE'),
            'MERCADOPAGO_CUSTOM_COUPON' => Configuration::get('MERCADOPAGO_CUSTOM_COUPON'),
            'MERCADOPAGO_CUSTOM_DISCOUNT' => Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT'),
            'MERCADOPAGO_CUSTOM_COMISSION' => Configuration::get('MERCADOPAGO_CUSTOM_COMISSION'),
        );
    }
}
