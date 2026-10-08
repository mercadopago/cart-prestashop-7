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
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/WebhookNotification.php';

class MercadoPagoNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * @var MPApi
     */
    public $mercadopago;

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
        MPLog::generate('--------NOTIFICATION--------');

        $topic = Tools::getValue('topic');
        $checkout = Tools::getValue('checkout');
        $secure_key = Tools::getValue('customer');
        $transaction_id = Tools::getValue('id');

        // Validate checkout notification
        if ($checkout == 'standard' && $topic == 'merchant_order') {
            $this->processIpnNotification($transaction_id, $secure_key);
        } elseif ($checkout == 'custom' && $topic == 'payment') {
            $this->processWebhookNotification($transaction_id, $secure_key);
        } else {
            $this->getErrorResponse();
        }
    }

    /**
     * Process IPN Notification
     *
     * @param int $transaction_id
     * @param string $secure_key
     *
     * @return void
     */
    public function processIpnNotification($transaction_id, $secure_key)
    {
        MPLog::generate('Entered the IpnNotification rule');

        try {
            $merchant_order = $this->mercadopago->getMerchantOrder($transaction_id, true);
        } catch (Throwable $th) {
            $this->sendNotificationResponse('Could not retrieve notification data; retry later', 503);

            return;
        }
        if ($merchant_order === false || !isset($merchant_order['external_reference'])) {
            $this->getErrorResponse();
        }

        $cart_id = $merchant_order['external_reference'];

        $cart = new Cart($cart_id);
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;

        if (!hash_equals((string) $customer_secure_key, (string) $secure_key)) {
            $this->getErrorResponse();
        }

        $notification = new IpnNotification($transaction_id, $merchant_order);
        try {
            $notification->receiveNotification($cart);
        } catch (Throwable $th) {
            MPLog::generate('Notification processing failed before completion', 'error');
            $this->sendNotificationResponse('Could not complete notification processing; retry later', 503);

            return;
        }

        $this->sendNotificationResult($notification);
    }

    /**
     * Process Webhook Notification
     *
     * @param int $transaction_id
     * @param string $secure_key
     *
     * @return void
     */
    public function processWebhookNotification($transaction_id, $secure_key)
    {
        MPLog::generate('Entered the WebhookNotification rule');

        try {
            $payment = $this->mercadopago->getPaymentStandard($transaction_id, true);
        } catch (Throwable $th) {
            $this->sendNotificationResponse('Could not retrieve notification data; retry later', 503);

            return;
        }
        if ($payment === false || !isset($payment['external_reference'])) {
            $this->getErrorResponse();
        }

        $cart_id = $payment['external_reference'];

        $cart = new Cart($cart_id);
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;

        if (!hash_equals((string) $customer_secure_key, (string) $secure_key)) {
            $this->getErrorResponse();
        }

        $notification = new WebhookNotification($transaction_id, $payment);
        try {
            $notification->receiveNotification($cart);
        } catch (Throwable $th) {
            MPLog::generate('Notification processing failed before completion', 'error');
            $this->sendNotificationResponse('Could not complete notification processing; retry later', 503);

            return;
        }

        $this->sendNotificationResult($notification);
    }

    /**
     * Get error response
     *
     * @return void
     */
    public function getErrorResponse()
    {
        MPLog::generate('The notification does not have the necessary parameters to create an order');
        AbstractNotification::getNotificationResponse(
            'The notification does not have the necessary parameters',
            200
        );
    }

    /**
     * Sends the single response for this notification request, once all processing —
     * including any multi-order loop under the same reference — has finished (PPSP-1573).
     * Terminates the request; must be the last thing called for a given request.
     *
     * Every current branch of updateOrder()/createOrder()/createStandardOrder()/
     * receiveNotification() calls setNotificationResponse() before returning, so the null
     * check below should be unreachable today. It stays as a defensive net for whoever adds
     * the next branch: failing loudly with a non-2xx (so Mercado Pago retries and the gap gets
     * noticed) is deliberately safer here than defaulting to a 2xx that reads as success and
     * lets the omission hide indefinitely — the exact failure mode this ticket closed.
     *
     * @param AbstractNotification $notification
     *
     * @return void
     */
    public function sendNotificationResult(AbstractNotification $notification)
    {
        $message = $notification->responseMessage;
        $code = $notification->responseCode;

        if ($message === null || $code === null) {
            MPLog::generate(
                'Notification processing finished without recording an explicit response outcome '
                . '(a code path is missing a setNotificationResponse() call) — see PPSP-1573',
                'error'
            );
            $message = 'Notification processed without a recorded outcome';
            $code = 500;
        }

        $this->sendNotificationResponse($message, $code);
    }

    /**
     * Sends the terminal HTTP response for a notification request.
     *
     * Kept in its own method so the aggregation contract can be tested without
     * terminating the PHP process. Production always delegates to the existing
     * responder, which writes the JSON response and exits.
     *
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    protected function sendNotificationResponse($message, $code)
    {
        AbstractNotification::getNotificationResponse($message, $code);
    }
}
