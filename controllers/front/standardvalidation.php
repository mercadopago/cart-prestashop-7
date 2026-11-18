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
    public $confirmationPending = false;
    public $confirmationPartial = false;

    /** @var int|null Validated original cart scope for held buyer recovery. */
    private $authorizedHeldCartId;

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
        if (Tools::getValue('payment_hold')) {
            $cartId = Tools::getValue('cart_id');
            if (!$this->canViewPaymentHold($cartId)) {
                $this->redirectError();

                return;
            }
            $this->authorizedHeldCartId = (int) $cartId;
            if (Tools::getValue('typeReturn') !== 'success') {
                $this->showPaymentHold((int) $cartId);

                return;
            }
        }
        $typeReturn = Tools::getValue('typeReturn');
        $payment_ids = Tools::getValue('collection_id');
        $cartId = Tools::getValue('cart_id');

        if (isset($payment_ids) && $payment_ids != false && $payment_ids != 'null' && $typeReturn != 'failure') {
            try {
                $ids = IpnNotification::normalizeCandidatePaymentIds($payment_ids);
            } catch (InvalidArgumentException $error) {
                $this->redirectError();

                return;
            }
            $this->redirectCheck($ids);

            return;
        }

        if (isset($cartId) && $typeReturn != 'failure') {
            $order = $this->mp_transaction->where('cart_id', '=', $cartId)->get();
            if (!empty($order['payment_initiation']) && !$this->canViewPaymentHold($cartId)) {
                $this->redirectError();

                return;
            }
            if (!empty($order['payment_initiation'])) {
                $this->authorizedHeldCartId = (int) $cartId;
            }
            $merchant_order_id = is_array($order) ? ($order['merchant_order_id'] ?? null) : null;

            if ($merchant_order_id || $merchant_order_id === '0') {
                $merchant_order = $this->mercadopago->getMerchantOrder($order['merchant_order_id']);

                if ($merchant_order === false || !isset($merchant_order['payments'][0]['id'])) {
                    $this->showPendingConfirmation([], (int) $cartId);

                    return;
                }

                $payment_id = $merchant_order['payments'][0]['id'];

                $this->redirectCheck($payment_id);
            } elseif (!empty($order['payment_initiation']) || $this->authorizedHeldCartId !== null) {
                $this->showPaymentHold((int) $cartId);
            } else {
                $this->redirectError();
            }

            return;
        }

        $this->redirectError();
    }

    /** Native shopper authentication remains valid after core replaces an ordered cart. */
    private function canViewPaymentHold($cartId): bool
    {
        if ((!is_string($cartId) && !is_int($cartId))
            || !preg_match('/^[1-9][0-9]*$/D', (string) $cartId)
            || (string) (int) $cartId !== (string) $cartId
        ) {
            return false;
        }
        $customer = $this->context->customer;
        if (!Validate::isLoadedObject($customer) || (int) $customer->id <= 0
            || !$customer->active || $customer->deleted || !$customer->isLogged(true)
            || (int) $this->context->cookie->__get('id_customer') !== (int) $customer->id
        ) {
            return false;
        }
        $cart = new Cart((int) $cartId);

        return Validate::isLoadedObject($cart)
            && (int) $cart->id_customer > 0
            && (int) $cart->id_customer === (int) $customer->id
            && (int) $cart->id_shop === (int) $this->context->shop->id
            && (int) $cart->id_shop_group === (int) $this->context->shop->id_shop_group
            && (string) $customer->secure_key !== ''
            && hash_equals((string) $customer->secure_key, (string) $cart->secure_key);
    }

    /**
     * Default function to call redirect
     *
     * @return void
     */
    public function redirectCheck($payment_id)
    {
        try {
            $ids = IpnNotification::normalizeCandidatePaymentIds(is_array($payment_id) ? $payment_id : [$payment_id]);
        } catch (InvalidArgumentException $error) {
            $this->redirectError();

            return;
        }
        if (!$ids) {
            $this->redirectError();

            return;
        }
        $payment = $this->mercadopago->getPaymentStandard($ids[0]);
        if ($this->authorizedHeldCartId !== null && (!is_array($payment)
            || !isset($payment['external_reference'])
            || (!is_string($payment['external_reference']) && !is_int($payment['external_reference']))
            || (string) $payment['external_reference'] !== (string) $this->authorizedHeldCartId)
        ) {
            $this->showPendingConfirmation($ids, $this->authorizedHeldCartId);

            return;
        }
        if (!is_array($payment) || !isset($payment['id'], $payment['external_reference'], $payment['order']['id'])
            || (string) $payment['id'] !== $ids[0]
        ) {
            $this->showPendingConfirmation($ids);

            return;
        }
        $cart = new Cart($payment['external_reference']);
        $order = $this->createOrder($cart, $payment['order']['id'], $ids);
        if (Validate::isLoadedObject($order)) {
            $this->redirectOrderConfirmation($cart, $order);

            return;
        }
        if ($this->confirmationPartial) {
            $this->showPartialConfirmation($ids);

            return;
        }
        if ($this->confirmationPending) {
            $this->showPendingConfirmation($ids);

            return;
        }
        $this->redirectError();
    }

    public function showPartialConfirmation(array $ids)
    {
        $this->showPendingConfirmation($ids);
        http_response_code(200);
        $this->setTemplate('module:mercadopago/views/templates/front/confirmation_partial.tpl');
    }

    public function showPaymentHold(int $cartId)
    {
        $this->showPendingConfirmation([], $cartId);
        $this->context->smarty->assign('mp_confirmation_retry_url', $this->context->link->getModuleLink(
            'mercadopago',
            'standardvalidation',
            ['cart_id' => $cartId, 'payment_hold' => 1, 'typeReturn' => 'success'],
            true
        ));
        $this->setTemplate('module:mercadopago/views/templates/front/confirmation_hold.tpl');
    }

    /** Confirmation is not evidence of payment and must not invite a second charge. */
    public function showPendingConfirmation(array $ids, $cartId = null)
    {
        parent::initContent();
        http_response_code(503);
        $retry = $ids ? ['collection_id' => implode(',', $ids), 'typeReturn' => 'success']
            : ['cart_id' => (int) $cartId, 'typeReturn' => 'success'];
        if ($this->authorizedHeldCartId !== null) {
            $retry['cart_id'] = $this->authorizedHeldCartId;
            $retry['payment_hold'] = 1;
        }
        $this->context->smarty->assign('mp_confirmation_retry_url', $this->context->link->getModuleLink(
            'mercadopago',
            'standardvalidation',
            $retry,
            true
        ));
        $this->setTemplate('module:mercadopago/views/templates/front/confirmation_pending.tpl');
    }

    /**
     * Create order without notification
     *
     * @param mixed $cart
     * @param int $transaction_id
     *
     * @return Order|false
     */
    public function createOrder($cart, $transaction_id, array $candidateIds = [])
    {
        $merchant_order = $this->mercadopago->getMerchantOrder($transaction_id);

        if (!is_array($merchant_order)) {
            $this->confirmationPending = true;
            MPLog::generate(
                'Standard checkout order not created for cart ' . (int) $cart->id . ': merchant order '
                . (int) $transaction_id . ' could not be retrieved from Mercado Pago (verify the access token '
                . 'is authorized for merchant_orders)',
                'error'
            );

            return false;
        }

        $notification = new IpnNotification($transaction_id, $merchant_order, $candidateIds);
        $notification->mercadopago = $this->mercadopago;
        try {
            return $notification->createStandardOrder($cart);
        } catch (Throwable $th) {
            $this->confirmationPartial = $notification->responseCode === 200 && $notification->checkoutOutcome === 'partial_funds';
            $this->confirmationPending = $notification->responseCode === null || $notification->responseCode >= 400;
            MPLog::generate('Standard checkout payment lookup failed; order creation deferred', 'error');

            return false;
        }
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
