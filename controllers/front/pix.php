<?php
/**
 * 2007-2021 PrestaShop
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
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/preference/PixPreference.php';
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';

class MercadoPagoPixModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        MPLog::generate('Exception Message: controller **PIX**');
        parent::__construct();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     * @throws Exception
     */
    public function postProcess()
    {
        MPLog::generate('Exception Message: controller **PIX**');
        $module = Module::getInstanceByName('mercadopago');
        $preference = new PixPreference($this->context->cart);

        try {
            $preference->verifyModuleParameters();
            $payment = $preference->createPreference();

            $transactionDetails = $payment['transaction_details'];
            $preference->saveCreatePreferenceData(
                $this->context->cart,
                $transactionDetails['external_resource_url'],
            );

            $order = $this->_createOrder(
                $payment,
                $this->context->cart,
                $preference
            );

            $link = $this->_getSucessRedirectLink($order, $payment);

            Tools::redirect($link);
        } catch (Exception $err) {
            MPLog::generate('Exception Message: ' . $err->getMessage());
            $this->_redirectError(
                $preference,
                $module->l('An error has occurred. Please try again.', 'mercadopago')
            );
        }
    }

    /**
     * Create order
     *
     * @param Array      $payment       Data about payment
     * @param Object     $cart          Shopping cart data
     * @param Preference $preference    Pix preference
     *
     * @return Array
     */
    private function _createOrder($payment, $cart, $preference)
    {
        $transactionId = $payment['id'];
        $notification = new WebhookNotification($transactionId, $payment);
        $notification->createCustomOrder($cart);
        $preference->disableCartRule();

        $oldCart = new Cart($cart->id);
        $order = Order::getOrderByCartId($oldCart->id);
        $order = new Order($order);

        return $order;
    }

    /**
     * Get Success Redirect Link
     *
     * @param Order $order   Data about order
     * @param Array $payment Data about payment
     *
     * @return void
     */
    private function _getSucessRedirectLink($order, $payment)
    {
        $queryString = 'index.php?controller=order-confirmation';
        
        $link = __PS_BASE_URI__ . $queryString;
        $link .= '&id_cart=' . $order->id_cart;
        $link .= '&key=' . $order->secure_key;
        $link .= '&id_order=' . $order->id;
        $link .= '&id_module=' . $this->module->id;
        $link .= '&payment_id=' . $payment['id'];
        $link .= '&payment_status=' . $payment['status'];

        return $link;
    }

    /**
     * Redirect to checkout with error
     *
     * @param Preference  $preference    Data about payment
     * @param String      $errorMessage  Data about payment
     *
     * @return void
     */
    private function _redirectError($preference, $errorMessage)
    {
        $this->context->cookie->__set('redirect_message', $errorMessage);
        $preference->deleteCartRule();
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }
}
