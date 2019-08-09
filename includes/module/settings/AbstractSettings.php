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

class AbstractSettings
{
    public $form;
    public $module;
    public $values;
    public $submit;
    public $process;
    public $mercadopago;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Build Config Form
     *
     * @return void
     */
    public function buildForm($title, $fields)
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $title,
                    'icon' => 'icon-cogs',
                ),
                'class' => 'credentials',
                'input' => $fields,
                'submit' => array(
                    'title' => $this->module->l('Save')
                ),
            ),
        );
    }

    /**
     * Verify form submit
     *
     * @return void
     */
    public function verifyPostProcess(){
        if (((bool) Tools::isSubmit($this->submit)) == true) {
            return $this->postFormProcess();
        }
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        $form_values = array();

        foreach (array_keys($this->values) as $key) {
            $value = Tools::getValue($key);
            $form_values[$key] = $value;
            Configuration::updateValue($key, $value);
        }

        Mercadopago::$form_alert = 'alert-success';
        Mercadopago::$form_message = $this->module->l('Settings saved successfully.');

        $this->values = $form_values;
    }

    /**
     * Send info to settings api
     *
     * @return void
     */
    protected function sendSettingsInfo()
    {
        $checkout_basic = (Configuration::get('MERCADOPAGO_CHECKOUT_STATUS') == true) ? 'true' : 'false';

        $data = array(
            "platform" => "PrestaShop",
            "platform_version" => _PS_VERSION_,
            "module_version" => MP_VERSION,
            "code_version" => phpversion(),
            "checkout_basic" => $checkout_basic
        );

        $this->mercadopago->saveApiSettings($data);

        return true;
    }
}