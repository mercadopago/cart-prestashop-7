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

require_once MP_ROOT_URL . '/includes/module/preference/PsePreference.php';
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';
require_once MP_ROOT_URL . '/includes/module/checkouts/PseCheckout.php';

class MercadoPagoPseModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
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
        $preference = new PsePreference(new PseCheckout(), $this->context->cart);
        $pseFormData = Tools::getValue('mercadopago_pse');
        $payerData = array(
            'entity_type' => $pseFormData['personType'],
            'document_type' => $pseFormData['documentType'],
            'document_number' => $pseFormData['documentNumber'],
            'financial_institution' => $pseFormData['financialInstitution']
        );

        try {
            $preference->verifyModuleParameters();
            $payment = $preference->createPayment($payerData, $this->getCallbackPath($this->context->cart));

            if (!is_array($payment)) return $this->handleWithPaymentError($preference, $payment, null);

            $preference->saveCreatePreferenceData(
                $this->context->cart,
                $payment['notification_url'],
            );

            $this->createOrder($payment, $this->context->cart);
            $preference->deactivateDiscount();

            Tools::redirect($payment['transaction_details']['external_resource_url']);
        } catch (Exception $err) {
            $this->handleWithPaymentError($preference, null, $err);
        }
    }

    /**
     * @param array      $payment
     * @param object     $cart
     *
     * @return void
     */
    private function createOrder($payment, $cart)
    {
        $notification = new WebhookNotification($payment['id'], $payment);

        $notification->createCustomOrder($cart);
    }

    /**
     * @param object $cart
     *
     * @return string
     */
    private function getCallbackPath($cart) {
        $path = '?id_cart=' . $cart->id;
        $path .= '&key=' . $cart->secure_key;
        $path .= '&id_order=' . $cart->id;
        $path .= '&id_module=' . $this->module->id;
        $path .= '&payment_status=pending';
        $path .= '&checkout_type=pse';

        return $path;
    }

    /**
     * @param PsePreference  $preference
     * @param array  $paymentResponse
     * @param Exception  $err
     *
     * @return void
    */
    private function handleWithPaymentError($preference, $paymentResponse, $err)
    {
        if (is_string($paymentResponse)) {
            $message = MPApi::validateMessageApi($paymentResponse) || 'Somenthing went wrong during PSE payment creation';

            $this->redirectToErrorPage($preference, Tools::displayError($message));
            return;
        }

        MPLog::generate('Exception Message: ' . $err->getMessage());
        $this->redirectToErrorPage($preference, Tools::displayError());
    }

    /**
     * @param Preference  $preference
     * @param string      $errorMessage
     *
     * @return void
     */
    private function redirectToErrorPage($preference, $errorMessage)
    {
        $this->context->cookie->__set('redirect_message', $errorMessage);
        $preference->deleteCartRule();
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }
}
