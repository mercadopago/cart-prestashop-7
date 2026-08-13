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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/CustomPreference.php';
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/WebhookNotification.php';

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
                // payment created
                $preference->saveCreatePreferenceData($this->context->cart, $customPreference['notification_url']);
                MPLog::generate('Cart id ' . $this->context->cart->id . ' - Custom payment created successfully');

                // create order
                $transaction_id = $customPreference['id'];
                $notification = new WebhookNotification($transaction_id, $customPreference);
                $notification->createCustomOrder($this->context->cart);
                $preference->disableCartRule();

                // order confirmation redirect
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

                // redirect to order confirmation page
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
