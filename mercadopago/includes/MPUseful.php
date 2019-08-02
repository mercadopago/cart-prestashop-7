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

class MPUseful
{
    public function __construct()
    {
    }

    /**
     * Instance the class
     *
     * @return void
     */
    public static function getinstance()
    {
        static $mpuseful = null;
        if (null === $mpuseful) {
            $mpuseful = new MPUseful();
        }
        return $mpuseful;
    }

    /**
     * Get notification payment status
     *
     * @param [string] $state
     * @return void
     */
    public function getNotificationPaymentState($state)
    {
        $payment_states = array(
            'in_process' => 'MERCADOPAGO_STATUS_0',
            'approved' => 'MERCADOPAGO_STATUS_1',
            'cancelled' => 'MERCADOPAGO_STATUS_2',
            'rejected' => 'MERCADOPAGO_STATUS_3',
            'refunded' => 'MERCADOPAGO_STATUS_4',
            'charged_back' => 'MERCADOPAGO_STATUS_5',
            'in_mediation' => 'MERCADOPAGO_STATUS_6',
            'pending' => 'MERCADOPAGO_STATUS_7',
            'authorized' => 'MERCADOPAGO_STATUS_8'
        );

        return Configuration::get($payment_states[$state]);
    }

    /**
     * Get mercadopago categories
     *
     * @return void
     */
    public function getCategories()
    {
        $categories = array();
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

    /**
     * Get default sponsor_id
     *
     * @param [string] $country
     * @return void
     */
    public function getCountryConfigs($country)
    {
        $country_configs = array(
            'MCO' => array(
                'site_id'    => 'MCO',
                'sponsor_id' => 208687643,
                'currency'   => 'COP',
            ),
            'MLA' => array(
                'site_id'    => 'MLA',
                'sponsor_id' => 208682286,
                'currency'   => 'ARS',
            ),
            'MLB' => array(
                'site_id'    => 'MLB',
                'sponsor_id' => 208686191,
                'currency'   => 'BRL',
            ),
            'MLC' => array(
                'site_id'    => 'MLC',
                'sponsor_id' => 208690789,
                'currency'   => 'CLP',
            ),
            'MLM' => array(
                'site_id'    => 'MLM',
                'sponsor_id' => 208692380,
                'currency'   => 'MXN',
            ),
            'MLU' => array(
                'site_id'    => 'MLU',
                'sponsor_id' => 243692679,
                'currency'   => 'UYU',
            ),
            'MLV' => array(
                'site_id'    => 'MLV',
                'sponsor_id' => 208692735,
                'currency'   => 'VEF',
            ),
            'MPE' => array(
                'site_id'    => 'MPE',
                'sponsor_id' => 216998692,
                'currency'   => 'PEN',
            )
        );

        return $country_configs[$country]['sponsor_id'];
    }

    /**
     * Get default country
     *
     * @param [string] $country
     * @return string
     */
    public function setMPCurrency($currency)
    {
        $site_id = array(
            'COP' => 'mco',
            'ARS' => 'mla',
            'BRL' => 'mlb',
            'CLP' => 'mlc',
            'MXN' => 'mlm',
            'UYU' => 'mlu',
            'VEF' => 'mlv',
            'PEN' => 'mpe',
        );

        if (array_key_exists($currency, $site_id)) {
            return $site_id[$currency];
        }

        return 'mld';
    }

    /**
     * Get seller protect link
     *
     * @param [string] $country
     * @return string
     */
    public function setSellerProtectLink($country)
    {
        $protect_link = array(
            'mld' => 'https://www.mercadopago.com/',
            'mco' => 'https://www.mercadopago.com.co/ayuda/seguridad-vendedor_1800',
            'mla' => 'https://www.mercadopago.com.ar/ayuda/dinero-seguridad-ventas_288',
            'mlb' => 'https://www.mercadopago.com.br/ajuda/como-protegemos-vendedores_500',
            'mlc' => 'https://www.mercadopago.cl/ayuda/proteccion-vendedores_1807',
            'mlm' => 'https://www.mercadopago.com.mx/ayuda/dinero-seguridad-ventas_701',
            'mlu' => 'https://www.mercadopago.com.uy/ayuda/dinero-seguridad-ventas_288',
            'mlv' => 'https://www.mercadopago.com.ve/accion-pausada',
            'mpe' => 'https://www.mercadopago.com.pe/ayuda/dinero-seguridad-ventas_288',
        );

        return $protect_link[$country];
    }

    /**
     * Get installments
     *
     * @param [type] $max
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
}
