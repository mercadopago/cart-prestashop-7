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

class LocalizationSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoCountry';
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
        $title = $this->module->l('Localization', 'LocalizationSettings');
        $fields = array(
            array(
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Country:', 'LocalizationSettings'),
                'name' => 'MERCADOPAGO_COUNTRY_LINK',
                'desc' => $this->module->l('Select the country in which your Mercado Pago account operates', 'LocalizationSettings'),
                'options' => array(
                    'query' => $this->getCountryLinks(),
                    'id' => 'id',
                    'name' => 'name'
                )
            )
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
        parent::postFormProcess();

        Mercadopago::$form_message = $this->module->l(
            'Settings saved successfully. Now you can configure the module.',
            'LocalizationSettings'
        );
        MPLog::generate('Localization saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return array(
            'MERCADOPAGO_COUNTRY_LINK' => Configuration::get('MERCADOPAGO_COUNTRY_LINK')
        );
    }

    /**
     * Get mercadopago country links
     *
     * @return array
     */
    public function getCountryLinks()
    {
        $country_links = array();
        $country_links[] = array('id' => 'mld', 'name' => $this->module->l('Select country', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mla', 'name' => $this->module->l('Argentina', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mlb', 'name' => $this->module->l('Brazil', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mlc', 'name' => $this->module->l('Chile', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mco', 'name' => $this->module->l('Colombia', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mlm', 'name' => $this->module->l('Mexico', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mpe', 'name' => $this->module->l('Peru', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mlu', 'name' => $this->module->l('Uruguay', 'LocalizationSettings'));
        $country_links[] = array('id' => 'mlv', 'name' => $this->module->l('Venezuela', 'LocalizationSettings'));

        return $country_links;
    }
}
