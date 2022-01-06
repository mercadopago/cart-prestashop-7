<?php
/**
 * 2007-2021 PrestaShop
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
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/settings/AbstractSettings.php';

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
     * @return void
     */
    public function generateForm()
    {
        $title = $this->module->l('Basic Configuration', 'PixSettings');
        $fields = array(
            array(
                'type' => 'switch',
                'label' => $this->module->l('Pagamentos por Pix', 'PixSettings'),
                'name' => 'MERCADOPAGO_PIX_CHECKOUT',
                'desc' => $this->module->l(
                    'Permita que clientes possam pagar com Pix no checkout da loja.',
                    'PixSettings'
                ),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_PIX_CHECKOUT_ON',
                        'value' => true,
                        'label' => $this->module->l('Active', 'PixSettings')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_PIX_CHECKOUT_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive', 'PixSettings')
                    )
                ),
            ),
            array(
                'col' => 2,
                'type' => 'select',
                'label' => $this->module->l('Payment due', 'PixSettings'),
                'name' => 'MERCADOPAGO_PIX_EXPIRATION',
                'desc' => $this->module->l('Ajuste o prazo que seus clientes terão para fazer a transferência via Pix.', 'PixSettings'),
                'options' => array(
                    'query' => $this->getDueDate(),
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 2,
                'suffix' => '%',
                'type' => 'text',
                'name' => 'MERCADOPAGO_PIX_DISCOUNT',
                'label' => $this->module->l('Desconto por compra com Pix', 'PixSettings'),
                'desc' => $this->module->l('Insira o percentual do desconto para incentivar seus clientes a pagarem com Pix.', 'PixSettings') ,
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
            'MERCADOPAGO_PIX_DISCOUNT' => 'percentage',
            'MERCADOPAGO_PIX_EXPIRATION' => 'payment_due',
        ]);

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
        $form_values = array(
            'MERCADOPAGO_PIX_CHECKOUT' => Configuration::get('MERCADOPAGO_PIX_CHECKOUT'),
            'MERCADOPAGO_PIX_DISCOUNT' => Configuration::get('MERCADOPAGO_PIX_DISCOUNT'),
            'MERCADOPAGO_PIX_EXPIRATION' => Configuration::get('MERCADOPAGO_PIX_EXPIRATION'),
        );

        return $form_values;
    }

    /**
     * Get due date
     *
     * @return array
     */
    public function getDueDate()
    {
        $due_date = array();
        $due_date[] = array('id' => '30', 'name' => '30 ' . $this->module->l('minutes', 'PixSettings'));
        $due_date[] = array('id' => '60', 'name' => '1 ' . $this->module->l('hour', 'PixSettings'));
        $due_date[] = array('id' => '360', 'name' => '6 ' . $this->module->l('hours', 'PixSettings'));
        $due_date[] = array('id' => '720', 'name' => '12 ' . $this->module->l('hours', 'PixSettings'));
        $due_date[] = array('id' => '1440', 'name' => '1 ' . $this->module->l('day', 'PixSettings'));
        $due_date[] = array('id' => '10080', 'name' => '7 ' . $this->module->l('days', 'PixSettings'));

        return $due_date;
    }
}
