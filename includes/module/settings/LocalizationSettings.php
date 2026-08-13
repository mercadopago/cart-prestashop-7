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
     * @return array
     */
    public function generateForm()
    {
        $title = $this->module->l('Localization', 'LocalizationSettings');
        $fields = [
            [
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Country:', 'LocalizationSettings'),
                'name' => 'MERCADOPAGO_COUNTRY_LINK',
                'desc' => $this->module->l('Select the country in which your Mercado Pago account operates', 'LocalizationSettings'),
                'options' => [
                    'query' => $this->getCountryLinks(),
                    'id' => 'id',
                    'name' => 'name',
                ],
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
        return [
            'MERCADOPAGO_COUNTRY_LINK' => Configuration::get('MERCADOPAGO_COUNTRY_LINK'),
        ];
    }

    /**
     * Get mercadopago country links
     *
     * @return array
     */
    public function getCountryLinks()
    {
        $country_links = [];
        $country_links[] = ['id' => 'mld', 'name' => $this->module->l('Select country', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mla', 'name' => $this->module->l('Argentina', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mlb', 'name' => $this->module->l('Brazil', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mlc', 'name' => $this->module->l('Chile', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mco', 'name' => $this->module->l('Colombia', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mlm', 'name' => $this->module->l('Mexico', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mpe', 'name' => $this->module->l('Peru', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mlu', 'name' => $this->module->l('Uruguay', 'LocalizationSettings')];
        $country_links[] = ['id' => 'mlv', 'name' => $this->module->l('Venezuela', 'LocalizationSettings')];

        return $country_links;
    }
}
