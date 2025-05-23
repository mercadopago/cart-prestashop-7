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

class StandardCheckout
{
    const METHOD_CREDIT_CARD = 1;
    const METHOD_DEBIT_CARD = 2;
    const METHOD_TICKET = 3;

    /**
     * @var Mercadopago
     */
    public $payment;

    public $mpuseful;

    /**
     * Standard Checkout constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->mpuseful = MPUseful::getInstance();
    }

    /**
     * @param $cart
     * @return array
     */
    public function getStandardCheckoutPS16($cart)
    {
        $informations = $this->getStandard($cart);
        $frontInformations = array_merge(
            $informations,
            array("mp_logo" => _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png')
        );
        return $frontInformations;
    }

    /**
     * @param $cart
     * @return array
     */
    public function getStandardCheckoutPS17($cart)
    {
        $informations = $this->getStandard($cart);
        $frontInformations = array_merge($informations, array("module_dir" => $this->payment->path));
        return $frontInformations;
    }

    /**
     * @param $cart
     * @return array
     */
    public function getStandard($cart)
    {
        $count = 0;
        $debit = array();
        $credit = array();
        $ticket = array();
        $tarjetas = $this->payment->mercadopago->getPaymentMethods();

        foreach ($tarjetas as $tarjeta) {
            if (Configuration::get($tarjeta['config']) != "") {
                $count++;
                if ($this->paymentMethodsCheck($tarjeta) === self::METHOD_CREDIT_CARD) {
                    $credit[] = $tarjeta;
                }
                if ($this->paymentMethodsCheck($tarjeta) === self::METHOD_DEBIT_CARD) {
                    $debit[] = $tarjeta;
                }
                if ($this->paymentMethodsCheck($tarjeta) === self::METHOD_TICKET) {
                    $ticket[] = $tarjeta;
                }
            }
        }

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $modal = Configuration::get('MERCADOPAGO_STANDARD_MODAL');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'standard');

        $informations = array(
            "count" => $count,
            "debit" => $debit,
            "credit" => $credit,
            "ticket" => $ticket,
            "modal" => $modal,
            "redirect" => $redirect,
            "public_key" => $this->payment->mercadopago->getPublicKey(),
            "installments" => Configuration::get('MERCADOPAGO_INSTALLMENTS'),
            "terms_url" => $this->mpuseful->getTermsAndPoliciesLink($site_id),
        );
        return $informations;
    }

    /**
     * Payment Methods check
     *
     * @param mixed $tarjeta
     * @return int
     */
    private function paymentMethodsCheck($tarjeta)
    {
        if (Tools::strtolower($tarjeta['id']) != 'meliplace' && $tarjeta['type'] != 'account_money') {
            if ($tarjeta['type'] == 'credit_card') {
                return self::METHOD_CREDIT_CARD;
            }
            if ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                return self::METHOD_DEBIT_CARD;
            } else {
                return self::METHOD_TICKET;
            }
        }
    }
}
