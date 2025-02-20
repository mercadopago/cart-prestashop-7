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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once MP_ROOT_URL . '/includes/module/preference/TicketPreference.php';
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';

class MercadoPagoTicketModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function postProcess()
    {
        $preference = new TicketPreference();
        try {
            $preference->verifyModuleParameters();

            $ticket_info = Tools::getValue('mercadopago_ticket');
            $ticketPreference = $preference->createPreference($this->context->cart, $ticket_info);

            if (is_array($ticketPreference) && array_key_exists('transaction_details', $ticketPreference)) {
                //payment created
                $transaction_details = $ticketPreference['transaction_details'];
                $preference->saveCreatePreferenceData(
                    $this->context->cart,
                    $transaction_details['external_resource_url']
                );
                MPLog::generate('Cart id ' . $this->context->cart->id . ' - Ticket payment created successfully');

                //create order
                $transaction_id = $ticketPreference['id'];
                $notification = new WebhookNotification($transaction_id, $ticketPreference);
                $notification->createCustomOrder($this->context->cart);
                $preference->disableCartRule();

                //order confirmation redirect
                $old_cart = new Cart($this->context->cart->id);
                $orderId = Order::getIdByCartId($old_cart->id);
                $order = new Order($orderId);

                $uri = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
                $uri .= '&id_cart=' . $order->id_cart;
                $uri .= '&key=' . $order->secure_key;
                $uri .= '&id_order=' . $order->id;
                $uri .= '&id_module=' . $this->module->id;
                $uri .= '&payment_id=' . $ticketPreference['id'];
                $uri .= '&payment_status=' . $ticketPreference['status'];
                $uri .= '&payment_ticket=' . urlencode($transaction_details['external_resource_url']);

                //redirect to order confirmation page
                Tools::redirect($uri);
            }
            if (is_string($ticketPreference)) {
                $message = MPApi::validateMessageApi($ticketPreference);
                if (!empty($message)) {
                    $this->context->cookie->__set('redirect_message', Tools::displayError($message));
                }
            }
        } catch (Exception $e) {
            $this->context->cookie->__set('redirect_message', Tools::displayError());
            MPLog::generate('Exception Message: ' . $e->getMessage());
        }

        $preference->deleteCartRule();
        $preference->redirectError();
    }
}
