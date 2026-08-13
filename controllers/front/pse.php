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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/PsePreference.php';
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/WebhookNotification.php';
require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';

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
     *
     * @throws Exception
     */
    public function postProcess()
    {
        $preference = new PsePreference(new PseCheckout(), $this->context->cart);
        $pseFormData = Tools::getValue('mercadopago_pse');
        if (!is_array($pseFormData)) {
            $pseFormData = [];
        }
        $payerData = [
            'entity_type' => isset($pseFormData['personType']) ? $pseFormData['personType'] : null,
            'document_type' => isset($pseFormData['documentType']) ? $pseFormData['documentType'] : null,
            'document_number' => isset($pseFormData['documentNumber']) ? $pseFormData['documentNumber'] : null,
            'financial_institution' => isset($pseFormData['financialInstitution']) ? $pseFormData['financialInstitution'] : null,
        ];

        try {
            $preference->verifyModuleParameters();
            $payment = $preference->createPayment($payerData, $this->getCallbackPath($this->context->cart));

            if (!is_array($payment)) {
                $this->handleWithPaymentError($preference, $payment, null);

                return;
            }

            $preference->saveCreatePreferenceData(
                $this->context->cart,
                $payment['notification_url']
            );

            $this->createOrder($payment, $this->context->cart);
            $preference->deactivateDiscount();

            Tools::redirect($payment['transaction_details']['external_resource_url']);
        } catch (Exception $err) {
            $this->handleWithPaymentError($preference, null, $err);
        }
    }

    /**
     * @param array $payment
     * @param object $cart
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
    private function getCallbackPath($cart)
    {
        $path = '?id_cart=' . $cart->id;
        $path .= '&key=' . $cart->secure_key;
        $path .= '&id_order=' . $cart->id;
        $path .= '&id_module=' . $this->module->id;
        $path .= '&payment_status=pending';
        $path .= '&checkout_type=pse';

        return $path;
    }

    /**
     * @param PsePreference $preference
     * @param array|string|null $paymentResponse
     * @param Exception|null $err
     *
     * @return void
     */
    private function handleWithPaymentError($preference, $paymentResponse, $err)
    {
        if (is_string($paymentResponse)) {
            $message = MPApi::validateMessageApi($paymentResponse) ?: 'Somenthing went wrong during PSE payment creation';

            $this->redirectToErrorPage($preference, Tools::displayError($message));

            return;
        }

        MPLog::generate('Exception Message: ' . $err->getMessage());
        $this->redirectToErrorPage($preference, Tools::displayError());
    }

    /**
     * @param AbstractPreference $preference
     * @param string $errorMessage
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
