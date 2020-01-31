<?php

/**
 * Class TicketCheckout
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
     */
    public function getTicketCheckoutPS16($cart)
    {
        $checkoutInfo = $this->getTicketCheckout($cart);
        $frontInformations = array_merge($checkoutInfo,
            array("mp_logo" => _MODULE_DIR_ . 'mercadopago/views/img/mpinfo_checkout.png'));
        return $frontInformations;
    }

    /**
     * @param $cart
     * @return array
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
     */
    public function getTicketCheckout($cart)
    {
        $ticket = array();
        $tarjetas = $this->payment->mercadopago->getPaymentMethods();
        foreach ($tarjetas as $tarjeta) {
            if (Configuration::get('MERCADOPAGO_TICKET_PAYMENT_' . $tarjeta['id']) != "") {
                if (
                    $tarjeta['type'] != 'credit_card' &&
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
        $redirect = $this->payment->context->link->getModuleLink($this->payment->name, 'ticket');

        $info = array(
            "ticket" => $ticket,
            "site_id" => $site_id,
            "address" => $address,
            "customer" => $customer,
            "redirect" => $redirect,
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