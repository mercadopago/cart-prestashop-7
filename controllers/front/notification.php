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

        $merchant_order = $this->mercadopago->getMerchantOrder($transaction_id);
        if ($merchant_order === false || !isset($merchant_order['external_reference'])) {
            $this->getErrorResponse();

            return;
        }

        $cart_id = $merchant_order['external_reference'];

        $cart = new Cart($cart_id);
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;

        if (!hash_equals((string) $customer_secure_key, (string) $secure_key)) {
            $this->getErrorResponse();

            return;
        }

        $notification = new IpnNotification($transaction_id, $merchant_order);
        $notification->receiveNotification($cart);
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

        $payment = $this->mercadopago->getPaymentStandard($transaction_id);
        if ($payment === false || !isset($payment['external_reference'])) {
            $this->getErrorResponse();

            return;
        }

        $cart_id = $payment['external_reference'];

        $cart = new Cart($cart_id);
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;

        if (!hash_equals((string) $customer_secure_key, (string) $secure_key)) {
            $this->getErrorResponse();

            return;
        }

        $notification = new WebhookNotification($transaction_id, $payment);
        $notification->receiveNotification($cart);
    }

    /**
     * Get error response
     *
     * @return void
     */
    public function getErrorResponse()
    {
        MPLog::generate('The notification does not have the necessary parameters to create an order');
        WebhookNotification::getNotificationResponse(
            'The notification does not have the necessary parameters',
            200
        );
    }
}
