<?php
/**
* 2007-2020 PrestaShop
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
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

class MPUseful
{
    public function __construct()
    {
    }

    /**
     * Instance the class
     *
     * @return MPUseful
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
                'sponsor_id' => 237788769,
                'currency'   => 'COP',
            ),
            'MLA' => array(
                'site_id'    => 'MLA',
                'sponsor_id' => 237788409,
                'currency'   => 'ARS',
            ),
            'MLB' => array(
                'site_id'    => 'MLB',
                'sponsor_id' => 236914421,
                'currency'   => 'BRL',
            ),
            'MLC' => array(
                'site_id'    => 'MLC',
                'sponsor_id' => 237788173,
                'currency'   => 'CLP',
            ),
            'MLM' => array(
                'site_id'    => 'MLM',
                'sponsor_id' => 237793014,
                'currency'   => 'MXN',
            ),
            'MLU' => array(
                'site_id'    => 'MLU',
                'sponsor_id' => 241729464,
                'currency'   => 'UYU',
            ),
            'MLV' => array(
                'site_id'    => 'MLV',
                'sponsor_id' => 237789083,
                'currency'   => 'VEF',
            ),
            'MPE' => array(
                'site_id'    => 'MPE',
                'sponsor_id' => 237791025,
                'currency'   => 'PEN',
            )
        );

        return $country_configs[$country]['sponsor_id'];
    }

    /**
     * Get default currency
     *
     * @param string $currency
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
     * Get modal link
     *
     * @param string $localization
     * @return string
     */
    public function getModalLink($localization)
    {
        $site_id = array(
            'MCO' => 'https://www.mercadopago.com.co/integrations/v1/web-payment-checkout.js',
            'MLA' => 'https://www.mercadopago.com.ar/integrations/v1/web-payment-checkout.js',
            'MLB' => 'https://www.mercadopago.com.br/integrations/v1/web-payment-checkout.js',
            'MLC' => 'https://www.mercadopago.cl/integrations/v1/web-payment-checkout.js',
            'MLM' => 'https://www.mercadopago.com.mx/integrations/v1/web-payment-checkout.js',
            'MLU' => 'https://www.mercadopago.com.uy/integrations/v1/web-payment-checkout.js',
            'MLV' => 'https://www.mercadopago.com.ve/integrations/v1/web-payment-checkout.js',
            'MPE' => 'https://www.mercadopago.com.pe/integrations/v1/web-payment-checkout.js',
        );

        if (array_key_exists($localization, $site_id)) {
            return $site_id[$localization];
        }

        return 'ar';
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
}
