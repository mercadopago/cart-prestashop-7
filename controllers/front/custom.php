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

require_once MP_ROOT_URL . '/includes/module/preference/CustomPreference.php';
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';

class MercadoPagoCustomModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function postProcess()
    {
        $preference = new CustomPreference();
        try {
            $preference->verifyModuleParameters();
            $custom_info = Tools::getValue('mercadopago_custom');
            $customPreference = $preference->createPreference($this->context->cart, $custom_info);

            if (is_array($customPreference) && array_key_exists('notification_url', $customPreference)
                && $customPreference['status'] != 'rejected') {
                //payment created
                $preference->saveCreatePreferenceData($this->context->cart, $customPreference['notification_url']);
                MPLog::generate('Cart id ' . $this->context->cart->id . ' - Custom payment created successfully');

                //create order
                $transaction_id = $customPreference['id'];
                $notification = new WebhookNotification($transaction_id, $customPreference);
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
                $uri .= '&payment_id=' . $customPreference['id'];
                $uri .= '&payment_status=' . $customPreference['status'];

                //redirect to order confirmation page
                Tools::redirect($uri);
            }

            if (is_string($customPreference)) {
                $message = MPApi::validateMessageApi($customPreference);
                if (!empty($message)) {
                    $this->context->cookie->__set('redirect_message', Tools::displayError($message));
                }
            }
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
        }

        $preference->deleteCartRule();
        $preference->redirectError();
    }
}
