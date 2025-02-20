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

class MPUseful
{
    const SEPARATOR = '|';

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
     * @param string $country
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
     * @param string $country
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
     * Set Country Link PSJ
     *
     * @param  string $country
     * @return string
     */
    public function getCountryPsjLink($country)
    {
        $psj_link = array(
            'mld' => 'https://www.mercadopago.com/',
            'mco' => 'https://www.mercadopago.com.co/costs-section#from-section=menu',
            'mla' => 'https://www.mercadopago.com.ar/costs-section#from-section=menu',
            'mlb' => 'https://www.mercadopago.com.br/costs-section#from-section=menu',
            'mlc' => 'https://www.mercadopago.cl/costs-section#from-section=menu',
            'mlm' => 'https://www.mercadopago.com.mx/costs-section#from-section=menu',
            'mlu' => 'https://www.mercadopago.com.uy/costs-section#from-section=menu',
            'mlv' => 'https://www.mercadopago.com.ve/costs-section#from-section=menu',
            'mpe' => 'https://www.mercadopago.com.pe/costs-section#from-section=menu',
        );

        return $psj_link[$country];
    }

    /**
     * Set the terms and policies link
     *
     * @param  string $country
     * @return string
     */
    public function getTermsAndPoliciesLink($country)
    {
        $terms_link = array(
            'MCO' => 'https://www.mercadopago.com.co/ayuda/terminos-y-politicas_194',
            'MLA' => 'https://www.mercadopago.com.ar/ayuda/terminos-y-politicas_194',
            'MLB' => 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194',
            'MLC' => 'https://www.mercadopago.cl/ayuda/terminos-y-politicas_194',
            'MLM' => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
            'MLU' => 'https://www.mercadopago.com.uy/ayuda/terminos-y-politicas_194',
            'MLV' => 'https://www.mercadopago.com.ve/ayuda/terminos-y-politicas_194',
            'MPE' => 'https://www.mercadopago.com.pe/ayuda/terminos-y-politicas_194',
        );

        return array_key_exists($country, $terms_link) ? $terms_link[$country] : $terms_link['MLA'];
    }

    /**
     * Separate payment id from payment place
     *
     * @param  string $compositeId
     * @return array
     */
    private function parse($compositeId)
    {
        $exploded = explode(self::SEPARATOR, $compositeId);

        return [
            'payment_method_id' => $exploded[0],
            'payment_place_id' => isset($exploded[1]) ? $exploded[1] : null,
        ];
    }

    /**
     * Returns payment method id
     *
     * @param  string $compositeId
     * @return string
     */
    public function getPaymentMethodId($compositeId)
    {
        return $this->parse($compositeId)['payment_method_id'];
    }

    /**
     * Returns payment place id
     *
     * @param  string $compositeId
     * @return string
     */
    public function getPaymentPlaceId($compositeId)
    {
        return $this->parse($compositeId)['payment_place_id'];
    }

    /**
     * Calculate the discounted total
     *
     * @param  mixed $cart
     * @param  string $strDiscount
     * @return float
     */
    public function getTheTotalDiscounted($cart, $strDiscount)
    {
        $products = (float) $cart->getOrderTotal(true, 4);
        $discount = $products * ((float) $strDiscount / 100);

        return $discount;
    }

    /**
     * Get round
     *
     * @return bool
     */
    public function getRound()
    {
        $round = false;
        $localization = Configuration::get('MERCADOPAGO_SITE_ID');

        if ($localization == 'MCO' || $localization == 'MLC') {
            $round = true;
        }

        return $round;
    }

    /**
     * Get corrected total amount
     *
     * @return array
     */
    public function getCorrectedTotal($cart, $checkout)
    {
        $round       = $this->getRound();
        $strDiscount = $this->getDiscountByCheckoutType($checkout);

        $shipping  = (float) $cart->getOrderTotal(true, 5);
        $products  = (float) $cart->getOrderTotal(true, 4);
        $cartTotal = (float) $cart->getOrderTotal();

        $discount = $products * ((float) $strDiscount / 100);
        $products = ($discount != 0) ? $products - $discount : $products;

        $subtotal   = $products + $shipping;
        $difference = $cartTotal - $subtotal - $discount;
        $amount     = $subtotal + $difference;

        $amountWithRound  = $round ? Tools::ps_round($amount) : Tools::ps_round($amount, 2);
        $amountDifference = $amountWithRound - $amount;

        return [
            "amount"            => $amount,
            "discount"          => $round ? Tools::ps_round($discount) : Tools::ps_round($discount, 2),
            "str_discount"      => $strDiscount,
            "amount_with_round" => $amountWithRound,
            "amount_difference" => $round ? Tools::ps_round($amountDifference) : Tools::ps_round($amountDifference, 2),
        ];
    }

    /**
     * Get discount based on checkout type
     *
     * @return int
     */
    public function getDiscountByCheckoutType($checkout)
    {
        switch ($checkout) {
            case 'credit_card':
            case 'wallet_button':
                return Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');

            case 'ticket':
                return Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');

            case 'pix':
                return Configuration::get('MERCADOPAGO_PIX_DISCOUNT');

            default:
                return 0.00;
        }
    }
}
