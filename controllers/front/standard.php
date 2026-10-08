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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/StandardPreference.php';

class MercadoPagoStandardModuleFrontController extends ModuleFrontController
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
    public function initContent()
    {
        $preference = new StandardPreference();
        try {
            $preference->verifyModuleParameters();
            $createPreference = $this->createPreference($preference, $this->context->cart);
            $checkoutType = $this->getCheckoutType($createPreference);

            if ($createPreference && $checkoutType) {
                if ($checkoutType == 'modal') {
                    $this->getResponse($createPreference, 200);
                    $this->standardModalCheckout($preference);
                }

                Tools::redirect($createPreference['init_point']);
            }

            $this->redirectError($preference, Tools::displayError());
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
            $this->redirectError($preference, Tools::displayError());
        }
    }

    /**
     * Get checkout type
     *
     * @param StandardPreference $preference
     *
     * @return mixed
     */
    public function getCheckoutType($preference)
    {
        $checkoutType = isset($preference['metadata']['checkout_type']) ? $preference['metadata']['checkout_type'] : false;

        if ($checkoutType) {
            return $checkoutType === 'modal' ? 'modal' : 'redirect';
        }

        return false;
    }

    /**
     * Create a Standard Preference
     *
     * @param StandardPreference $preference
     * @param Cart $cart
     *
     * @return mixed
     */
    public function createPreference($preference, $cart)
    {
        $createPreference = $preference->createPreference($cart);

        if (is_array($createPreference) && array_key_exists('init_point', $createPreference)) {
            $preference->saveCreatePreferenceData(
                $cart,
                $createPreference['notification_url']
            );

            return $createPreference;
        }

        return false;
    }

    /**
     * @param StandardPreference $preference
     */
    public function standardModalCheckout($preference)
    {
        $backUrl = Tools::getValue('back_url');
        if (isset($backUrl)) {
            Tools::redirect($backUrl);
        }

        $preference->redirectError();
    }

    /**
     * Get response with preference
     *
     * @param StandardPreference $preference
     * @param int $code
     */
    public function getResponse($preference, $code)
    {
        header('Content-type: application/json');
        $response = [
            'code' => $code,
            'preference' => $preference,
        ];

        http_response_code($code);
        echo json_encode($response);
        exit;
    }

    /**
     * Redirect to checkout with error
     *
     * @param StandardPreference $preference
     * @param string $errorMessage
     *
     * @return void
     */
    public function redirectError($preference, $errorMessage)
    {
        $this->context->cookie->__set('redirect_message', $errorMessage);
        $preference->deleteCartRule();
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }
}
