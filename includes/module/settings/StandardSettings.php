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

class StandardSettings extends AbstractSettings
{
    public $online_payments;
    public $offline_payments;

    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoStandard';
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
                'name' => 'MERCADOPAGO_CHECKOUT_STATUS',
                'desc' => $this->module->l('Activate the Mercado Pago experience at the checkout of your store.'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'MERCADOPAGO_CHECKOUT_STATUS_ON',
                        'value' => true,
                        'label' => $this->module->l('Active')
                    ),
                    array(
                        'id' => 'MERCADOPAGO_CHECKOUT_STATUS_OFF',
                        'value' => false,
                        'label' => $this->module->l('Inactive')
                    )
                ),
            ),
            array(
                'col' => 6,
                'type' => 'text',
                'label' => $this->module->l('Name'),
                'name' => 'MERCADOPAGO_INVOICE_NAME',
                'desc' => $this->module->l('This is the name that will appear on the customers invoice.'),
            ),
            array(
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Category'),
                'name' => 'MERCADOPAGO_STORE_CATEGORY',
                'desc' => $this->module->l('What category are your products? ') .
                    $this->module->l('Choose the one that best characterizes them ') .
                    $this->module->l('(choose "other" if your product is too specific).'),
                'options' => array(
                    'query' => $this->getCategories(),
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 4,
                'type' => 'checkbox',
                'label' => $this->module->l('Payment methods'),
                'name' => 'MERCADOPAGO_PAYMENT',
                'hint' => $this->module->l('Select the payment methods available in your store.'),
                'class' => 'payment-online-checkbox',
                'desc' => ' ',
                'values' => array(
                    'query' => $this->online_payments,
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 4,
                'type' => 'checkbox',
                'name' => 'MERCADOPAGO_PAYMENT',
                'class' => 'payment-offline-checkbox',
                'desc' => $this->module->l('Activate the payment alternatives you prefer for your customers.'),
                'values' => array(
                    'query' => $this->offline_payments,
                    'id' => 'id',
                    'name' => 'name'
                )
            ),
            array(
                'col' => 4,
                'type' => 'select',
                'label' => $this->module->l('Maximum of installments'),
                'name' => 'MERCADOPAGO_INSTALLMENTS',
                'desc' => $this->module->l('What is the maximum of installments which a customer can buy?'),
                'options' => array(
                    'query' => $this->getInstallments(24),
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

        $this->sendSettingsInfo();
        MPLog::generate('Basic configuration saved successfully');
    }

    /**
     * Set values for the form inputs
     *
     * @return array
     */
    public function getFormValues()
    {
        $form_values = array(
            'MERCADOPAGO_CHECKOUT_STATUS' => Configuration::get('MERCADOPAGO_CHECKOUT_STATUS'),
            'MERCADOPAGO_STORE_CATEGORY' => Configuration::get('MERCADOPAGO_STORE_CATEGORY'),
            'MERCADOPAGO_INVOICE_NAME' => Configuration::get('MERCADOPAGO_INVOICE_NAME'),
            'MERCADOPAGO_INSTALLMENTS' => Configuration::get('MERCADOPAGO_INSTALLMENTS'),
        );

        $payment_methods = $this->mercadopago->getPaymentMethods();
        foreach ($payment_methods as $payment_method) {
            $pm_id = $payment_method['id'];
            $pm_name = 'MERCADOPAGO_PAYMENT_' . $pm_id;

            if (
                $payment_method['type'] == 'credit_card' ||
                $payment_method['type'] == 'debit_card' ||
                $payment_method['type'] == 'prepaid_card'
            ) {
                $this->online_payments[] = array(
                    'id' => $pm_id,
                    'name' => $payment_method['name'],
                );
            } else {
                $this->offline_payments[] = array(
                    'id' => $pm_id,
                    'name' => $payment_method['name'],
                );
            }

            $form_values[$pm_name] = Configuration::get($pm_name);
        }

        return $form_values;
    }

    /**
     * Get installments
     *
     * @param int $max
     * @return void
     */
    public function getInstallments($max)
    {
        $installments = array();
        for ($i = $max; $i > 0; $i--) {
            $installments[] = array('id' => $i, 'name' => $i);
        }

        return $installments;
    }

    /**
     * Get mercadopago categories
     *
     * @return array
     */
    public function getCategories()
    {
        $categories = array();
        $categories[] = array('id' => 'no_category', 'name' => $this->module->l('Select the category'));
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
