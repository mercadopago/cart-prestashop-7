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
     * @return array
     */
    public function generateForm()
    {
        $title = $this->module->l('Store Information', 'StoreSettings');
        $fields = [
            [
                'col' => 6,
                'type' => 'text',
                'label' => $this->module->l('Name', 'StoreSettings'),
                'name' => 'MERCADOPAGO_INVOICE_NAME',
                'desc' => $this->module->l('This is the name that will appear on the customers invoice.', 'StoreSettings'),
            ],
            [
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Category', 'StoreSettings'),
                'name' => 'MERCADOPAGO_STORE_CATEGORY',
                'desc' => $this->module->l('What category do your products belong to? ', 'StoreSettings') .
                    $this->module->l('Choose the one that best characterizes them ', 'StoreSettings') .
                    $this->module->l('(choose other if your product is too specific).', 'StoreSettings'),
                'options' => [
                    'query' => $this->getCategories(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'col' => 2,
                'type' => 'text',
                'name' => 'MERCADOPAGO_INTEGRATOR_ID',
                'label' => $this->module->l('Integrator ID', 'StoreSettings'),
                'desc' => $this->module->l('With this number we identify all your transactions ', 'StoreSettings') .
                    $this->module->l('and know how many sales we process with your account.', 'StoreSettings'),
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
        MPLog::generate('Store information saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        return [
            'MERCADOPAGO_INVOICE_NAME' => Configuration::get('MERCADOPAGO_INVOICE_NAME'),
            'MERCADOPAGO_INTEGRATOR_ID' => Configuration::get('MERCADOPAGO_INTEGRATOR_ID'),
            'MERCADOPAGO_STORE_CATEGORY' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
        ];
    }

    /**
     * Get mercadopago categories
     *
     * @return array
     */
    public function getCategories()
    {
        $categories = [];
        $categories[] = ['id' => 'no_category', 'name' => $this->module->l('Category')];
        $categories[] = ['id' => 'others', 'name' => 'Other categories'];
        $categories[] = ['id' => 'art', 'name' => 'Collectibles & Art'];
        $categories[] = [
            'id' => 'baby',
            'name' => 'Toys for Baby, Stroller, Stroller Accessories, Car Safety Seats',
        ];
        $categories[] = ['id' => 'coupons', 'name' => 'Coupons'];
        $categories[] = ['id' => 'donations', 'name' => 'Donations'];
        $categories[] = ['id' => 'computing', 'name' => 'Computers & Tablets'];
        $categories[] = ['id' => 'cameras', 'name' => 'Cameras & Photography'];
        $categories[] = ['id' => 'video_games', 'name' => 'Video Games & Consoles'];
        $categories[] = ['id' => 'television', 'name' => 'LCD, LED, Smart TV, Plasmas, TVs'];
        $categories[] = [
            'id' => 'car_electronics',
            'name' => 'Car Audio, Car Alarm Systems & Security, Car DVRs, Car Video Players, Car PC',
        ];
        $categories[] = ['id' => 'electronics', 'name' => 'Audio & Surveillance, Video & GPS, Others'];
        $categories[] = ['id' => 'automotive', 'name' => 'Parts & Accessories'];
        $categories[] = [
            'id' => 'entertainment',
            'name' => 'Music, Movies & Series, Books, Magazines & Comics, Board Games & Toys',
        ];
        $categories[] = [
            'id' => 'fashion',
            'name' => 'Men\'s, Women\'s, Kids & baby, Handbags & Accessories, Health & Beauty, Shoes, Jewelry & Watches',
        ];
        $categories[] = ['id' => 'games', 'name' => 'Online Games & Credits'];
        $categories[] = ['id' => 'home', 'name' => 'Home appliances. Home & Garden'];
        $categories[] = ['id' => 'musical', 'name' => 'Instruments & Gear'];
        $categories[] = ['id' => 'phones', 'name' => 'Cell Phones & Accessories'];
        $categories[] = ['id' => 'services', 'name' => 'General services'];
        $categories[] = ['id' => 'learnings', 'name' => 'Trainings, Conferences, Workshops'];
        $categories[] = [
            'id' => 'tickets',
            'name' => 'Tickets for Concerts, Sports, Arts, Theater, Family, Excursions tickets, Events & more',
        ];
        $categories[] = ['id' => 'travels', 'name' => 'Plane tickets, Hotel vouchers, Travel vouchers'];
        $categories[] = [
            'id' => 'virtual_goods',
            'name' => 'E-books, Music Files, Software, Digital Images, PDF Files and any item which can be
            electronically stored in a file, Mobile Recharge, DTH Recharge and any Online Recharge',
        ];

        return $categories;
    }
}
