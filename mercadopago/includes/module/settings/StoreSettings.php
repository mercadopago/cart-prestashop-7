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

class StoreSettings extends AbstractSettings
{
    public $online_payments;
    public $offline_payments;

    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoStore';
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
        $title = $this->module->l('Store Information', 'StoreSettings');
        $fields = array(
            array(
                'col' => 6,
                'type' => 'text',
                'label' => $this->module->l('Name', 'StoreSettings'),
                'name' => 'MERCADOPAGO_INVOICE_NAME',
                'desc' => $this->module->l('This is the name that will appear on the customers invoice.', 'StoreSettings'),
            ),
            array(
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Category', 'StoreSettings'),
                'name' => 'MERCADOPAGO_STORE_CATEGORY',
                'desc' => $this->module->l('What category do your products belong to? ', 'StoreSettings') .
                    $this->module->l('Choose the one that best characterizes them ', 'StoreSettings') .
                    $this->module->l('(choose other if your product is too specific).', 'StoreSettings'),
                'options' => array(
                    'query' => $this->getCategories(),
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 2,
                'type' => 'text',
                'name' => 'MERCADOPAGO_INTEGRATOR_ID',
                'label' => $this->module->l('Integrator ID', 'StoreSettings'),
                'desc' => $this->module->l('With this number we identify all your transactions ', 'StoreSettings') .
                    $this->module->l('and know how many sales we process with your account.', 'StoreSettings'),
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
        parent::postFormProcess();
        MPLog::generate('Store information saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return array(
            'MERCADOPAGO_INVOICE_NAME' => Configuration::get('MERCADOPAGO_INVOICE_NAME'),
            'MERCADOPAGO_INTEGRATOR_ID' => Configuration::get('MERCADOPAGO_INTEGRATOR_ID'),
            'MERCADOPAGO_STORE_CATEGORY' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
        );
    }

    /**
     * Get mercadopago categories
     *
     * @return array
     */
    public function getCategories()
    {
        $categories = array();
        $categories[] = array('id' => 'no_category', 'name' => $this->module->l('Category'));
        $categories[] = array('id' => 'others', 'name' => 'Other categories');
        $categories[] = array('id' => 'art', 'name' => 'Collectibles & Art');
        $categories[] = array(
            'id' => 'baby',
            'name' => 'Toys for Baby, Stroller, Stroller Accessories, Car Safety Seats'
        );
        $categories[] = array('id' => 'coupons', 'name' => 'Coupons');
        $categories[] = array('id' => 'donations', 'name' => 'Donations');
        $categories[] = array('id' => 'computing', 'name' => 'Computers & Tablets');
        $categories[] = array('id' => 'cameras', 'name' => 'Cameras & Photography');
        $categories[] = array('id' => 'video_games', 'name' => 'Video Games & Consoles');
        $categories[] = array('id' => 'television', 'name' => 'LCD, LED, Smart TV, Plasmas, TVs');
        $categories[] = array(
            'id' => 'car_electronics',
            'name' => 'Car Audio, Car Alarm Systems & Security, Car DVRs, Car Video Players, Car PC'
        );
        $categories[] = array('id' => 'electronics', 'name' => 'Audio & Surveillance, Video & GPS, Others');
        $categories[] = array('id' => 'automotive', 'name' => 'Parts & Accessories');
        $categories[] = array(
            'id' => 'entertainment',
            'name' => 'Music, Movies & Series, Books, Magazines & Comics, Board Games & Toys'
        );
        $categories[] = array(
            'id' => 'fashion',
            'name' => 'Men\'s, Women\'s, Kids & baby, Handbags & Accessories, Health & Beauty, Shoes, Jewelry & Watches'
        );
        $categories[] = array('id' => 'games', 'name' => 'Online Games & Credits');
        $categories[] = array('id' => 'home', 'name' => 'Home appliances. Home & Garden');
        $categories[] = array('id' => 'musical', 'name' => 'Instruments & Gear');
        $categories[] = array('id' => 'phones', 'name' => 'Cell Phones & Accessories');
        $categories[] = array('id' => 'services', 'name' => 'General services');
        $categories[] = array('id' => 'learnings', 'name' => 'Trainings, Conferences, Workshops');
        $categories[] = array(
            'id' => 'tickets',
            'name' => 'Tickets for Concerts, Sports, Arts, Theater, Family, Excursions tickets, Events & more'
        );
        $categories[] = array('id' => 'travels', 'name' => 'Plane tickets, Hotel vouchers, Travel vouchers');
        $categories[] = array(
            'id' => 'virtual_goods',
            'name' => 'E-books, Music Files, Software, Digital Images, PDF Files and any item which can be
            electronically stored in a file, Mobile Recharge, DTH Recharge and any Online Recharge'
        );

        return $categories;
    }
}
