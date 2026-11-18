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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/PixPreference.php';
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/WebhookNotification.php';

class MercadoPagoPixModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    /** Keep dependency construction outside the checkout state transitions. */
    protected function createCheckoutPreference()
    {
        return new PixPreference($this->context->cart);
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     *
     * @throws Exception
     */
    public function postProcess()
    {
        $preference = $this->createCheckoutPreference();

        $paymentIssued = false;
        $failurePhase = 'response';
        try {
            $preference->verifyModuleParameters();
            $payment = $preference->createPreference();
            $paymentIssued = is_array($payment) && isset($payment['id']);

            if (is_array($payment)) {
                $failurePhase = 'persistence';
                $preference->saveCreatePreferenceData(
                    $this->context->cart,
                    $payment['notification_url']
                );

                $failurePhase = 'reconciliation';
                $order = $this->createOrder(
                    $payment,
                    $this->context->cart,
                    $preference
                );

                $failurePhase = 'confirmation';
                $link = $this->getSucessRedirectLink($order, $payment);

                Tools::redirect($link);
            }
            if (is_string($payment)) {
                $message = MPApi::validateMessageApi($payment);
                $this->redirectError($preference, empty($message) ? Tools::displayError() : Tools::displayError($message));
            }
        } catch (PaymentInitiationBlocked $held) {
            $preference->redirectPaymentHold();

            return;
        } catch (Throwable $err) {
            if ($paymentIssued) {
                $preference->holdIssuedCheckout($this->context->cart, $err, $failurePhase);

                return;
            }
            MPLog::generate('Exception Message: ' . $err->getMessage());
            $this->redirectError($preference, Tools::displayError());
        }
    }

    /**
     * Create order
     *
     * @param array $payment
     * @param object $cart
     * @param AbstractPreference $preference
     *
     * @return object
     */
    public function createOrder($payment, $cart, $preference)
    {
        $transactionId = $payment['id'];
        $notification = new WebhookNotification($transactionId, $payment);
        $order = $notification->createCustomOrder($cart);
        $preference->disableCartRule();

        return $order;
    }

    /**
     * Get Success Redirect Link
     *
     * @param Order $order
     * @param array $payment
     *
     * @return string
     */
    public function getSucessRedirectLink($order, $payment)
    {
        $queryString = 'index.php?controller=order-confirmation';

        $link = __PS_BASE_URI__ . $queryString;
        $link .= '&id_cart=' . $order->id_cart;
        $link .= '&key=' . $order->secure_key;
        $link .= '&id_order=' . $order->id;
        $link .= '&id_module=' . $this->module->id;
        $link .= '&payment_id=' . $payment['id'];
        $link .= '&payment_status=' . $payment['status'];
        $link .= '&checkout_type=' . $payment['metadata']['checkout_type'];

        return $link;
    }

    /**
     * Redirect to checkout with error
     *
     * @param AbstractPreference $preference
     * @param string $errorMessage
     *
     * @return void
     */
    public function redirectError($preference, $errorMessage)
    {
        $this->context->cookie->__set('redirect_message', $errorMessage);
        $preference->deleteCartRule();
        $preference->redirectError();
    }
}
