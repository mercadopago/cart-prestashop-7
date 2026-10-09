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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/IpnNotification.php';

class MercadoPagoStandardValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @var MPApi
     */
    public $mercadopago;

    public $mp_transaction;

    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
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
        $cartId = Tools::getValue('cart_id');

        if (isset($payment_ids) && $payment_ids != false && $payment_ids != 'null' && $typeReturn != 'failure') {
            $payment_id = explode(',', $payment_ids)[0];
            $this->redirectCheck($payment_id);

            return;
        }

        if (isset($cartId) && $typeReturn != 'failure') {
            $order = $this->mp_transaction->where('cart_id', '=', $cartId)->get();
            $merchant_order_id = $order['merchant_order_id'];

            if ($merchant_order_id || $merchant_order_id === '0') {
                $merchant_order = $this->mercadopago->getMerchantOrder($order['merchant_order_id']);

                if ($merchant_order === false || !isset($merchant_order['payments'][0]['id'])) {
                    $this->redirectError();

                    return;
                }

                $payment_id = $merchant_order['payments'][0]['id'];

                $this->redirectCheck($payment_id);
            } else {
                $this->redirectError();
            }

            return;
        }

        $this->redirectError();
    }

    /**
     * Default function to call redirect
     *
     * @return void
     */
    public function redirectCheck($payment_id)
    {
        $payment = $this->mercadopago->getPaymentStandard($payment_id);

        if ($payment !== false) {
            $cart_id = $payment['external_reference'];
            $transaction_id = $payment['order']['id'];
            $cart = new Cart($cart_id);
            $order = $this->createOrder($cart, $transaction_id);

            if (Validate::isLoadedObject($order)) {
                $this->redirectOrderConfirmation($cart, $order);
            }
        }

        $this->redirectError();
    }

    /**
     * Create order without notification
     *
     * @param mixed $cart
     * @param int $transaction_id
     *
     * @return Order|false
     */
    public function createOrder($cart, $transaction_id)
    {
        $merchant_order = $this->mercadopago->getMerchantOrder($transaction_id);

        if (!is_array($merchant_order)) {
            MPLog::generate(
                'Standard checkout order not created for cart ' . (int) $cart->id . ': merchant order '
                . (int) $transaction_id . ' could not be retrieved from Mercado Pago (verify the access token '
                . 'is authorized for merchant_orders)',
                'error'
            );

            return false;
        }

        $notification = new IpnNotification($transaction_id, $merchant_order);
        try {
            $notification->createStandardOrder($cart);
        } catch (Throwable $th) {
            MPLog::generate('Standard checkout payment lookup failed; order creation deferred', 'error');

            return false;
        }

        $orderId = Order::getIdByCartId($cart->id);
        $order = new Order($orderId);

        return $order;
    }

    /**
     * Redirect to order confirmation page
     *
     * @param mixed $cart
     * @param mixed $order
     *
     * @return mixed
     */
    public function redirectOrderConfirmation($cart, $order)
    {
        $url = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
        $url .= '&key=' . $order->secure_key;
        $url .= '&total=' . $cart->getOrderTotal();
        $url .= '&id_cart=' . $order->id_cart;
        $url .= '&id_order=' . $order->id;
        $url .= '&id_module=' . $this->module->id;

        return Tools::redirect($url);
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
