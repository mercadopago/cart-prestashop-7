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
require_once MP_ROOT_URL . '/includes/module/checkouts/PseCheckout.php';

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
        $fields = array();
        
        if ($this->module->isEnabledPaymentMethod('pse')) {
            $fields = array(
                array(
                    'type' => 'switch',
                    'label' => $this->module->l('Payments via PSE', 'PseSettings'),
                    'name' => 'MERCADOPAGO_PSE_CHECKOUT',
                    'desc' => $this->module->l('By deactivating it, you will disable PSE payments from Mercado Pago Transparent Checkout.', 'PseSettings'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'MERCADOPAGO_PSE_CHECKOUT_ON',
                            'value' => true,
                            'label' => $this->module->l('Active', 'PseSettings')
                        ),
                        array(
                            'id' => 'MERCADOPAGO_PSE_CHECKOUT_OFF',
                            'value' => false,
                            'label' => $this->module->l('Inactive', 'PseSettings')
                        )
                    ),
                ),
                array(
                    'col' => 2,
                    'suffix' => '%',
                    'type' => 'text',
                    'name' => PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME,
                    'label' => $this->module->l('Discount for purchase', 'PseSettings'),
                    'desc' => $this->module->l('Offer a special discount to encourage your ', 'PseSettings') .
                        $this->module->l('customers to make the purchase with Mercado Pago.', 'PseSettings'),
                ),
            );
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
        $this->validate = ([
            PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME => 'percentage',
        ]);

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
        $formValues = array(
            PseCheckout::PSE_CHECKOUT_NAME => Configuration::get(PseCheckout::PSE_CHECKOUT_NAME),
            PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME => Configuration::get(PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME),
        );

        return $formValues;
    }
}
