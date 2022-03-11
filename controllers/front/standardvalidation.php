<?php
/**
 * 2007-2022 PrestaShop
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
 *  @copyright 2007-2022 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/notification/IpnNotification.php';

class MercadoPagoStandardValidationModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        $typeReturn = Tools::getValue('typeReturn');
        $payment_ids = Tools::getValue('collection_id');

        if (isset($payment_ids) && $payment_ids != 'null' && $typeReturn != 'failure') {
            $payment_id = explode(',', $payment_ids)[0];
            $payment = $this->mercadopago->getPaymentStandard($payment_id);

            if ($payment !== false) {
                $cart_id = $payment['external_reference'];
                $transaction_id = $payment['order']['id'];

                $cart = new Cart($cart_id);
                $order = $this->createOrder($cart, $transaction_id);

                $this->redirectOrderConfirmation($cart, $order);
            }
        }

        $this->redirectError();
    }

    /**
     * Create order without notification
     *
     * @param mixed $cart
     * @param integer $transaction_id
     * @return void
     */
    public function createOrder($cart, $transaction_id)
    {
        $merchant_order = $this->mercadopago->getMerchantOrder($transaction_id);
        $notification = new IpnNotification($transaction_id, $merchant_order);
        $notification = $notification->createStandardOrder($cart);

        $order = Order::getOrderByCartId($cart->id);
        $order = new Order($order);

        return $order;
    }

    /**
     * Redirect to order confirmation page
     *
     * @param mixed $cart
     * @param mixed $order
     * @return void
     */
    public function redirectOrderConfirmation($cart, $order)
    {
        $url = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
        $url .= '&key=' . $order->secure_key;
        $url .= '&total=' . $cart->getOrderTotal();
        $url .= '&id_cart=' . $order->id_cart;
        $url .= '&id_order=' . $order->id;
        $url .= '&id_module=' . $this->module->id;

        return Tools::redirectLink($url);
    }

    /**
     * Redirect if any errors occurs
     *
     * @return void
     */
    public function redirectError()
    {
        MPLog::generate('The mercadopago checkout callback failed', 'error');
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }
}
