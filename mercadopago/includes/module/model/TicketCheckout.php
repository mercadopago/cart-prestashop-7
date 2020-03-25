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

class TicketCheckout
{
    /**
     * @var Mercadopago
     */
    public $payment;

    /**
     * StandardCheckout constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @param $cart
     * @return array
     * @throws PrestaShopException
     */
    public function getTicketCheckoutPS16($cart)
    {
        $checkoutInfo = $this->getTicketCheckout($cart);
        $frontInformations = array_merge(
            $checkoutInfo,
            array("mp_logo" => _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png')
        );
        return $frontInformations;
    }

    /**
     * @param $cart
     * @return array
     * @throws PrestaShopException
     */
    public function getTicketCheckoutPS17($cart)
    {
        $checkoutInfo = $this->getTicketCheckout($cart);
        $frontInformations = array_merge($checkoutInfo, array("module_dir" => $this->payment->path));
        return $frontInformations;
    }

    /**
     * @param $cart
     * @return array
     * @throws PrestaShopException
     */
    public function getTicketCheckout($cart)
    {
        $this->getTicketJS();
        $ticket = array();
        $tarjetas = $this->payment->mercadopago->getPaymentMethods();
        foreach ($tarjetas as $tarjeta) {
            if (Configuration::get('MERCADOPAGO_TICKET_PAYMENT_' . $tarjeta['id']) != "") {
                if ($tarjeta['type'] != 'credit_card' &&
                    $tarjeta['type'] != 'debit_card' &&
                    $tarjeta['type'] != 'prepaid_card'
                ) {
                    $ticket[] = $tarjeta;
                }
            }
        }

        $site_id = Configuration::get('MERCADOPAGO_SITE_ID');
        $address = new Address((int) $cart->id_address_invoice);
        $customer = Context::getContext()->customer->getFields();
        $discount = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'ticket');

        $info = array(
            "ticket" => $ticket,
            "site_id" => $site_id,
            "address" => $address,
            "customer" => $customer,
            "redirect" => $redirect,
            "discount" => $discount,
            "module_dir" => $this->payment->path,
        );

        return $info;
    }

    /**
     * Get ticket JS
     */
    public function getTicketJS()
    {
        $this->payment->context->controller->addJS($this->payment->path . '/views/js/ticket.js');
    }
}
