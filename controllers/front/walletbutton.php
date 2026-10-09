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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/preference/WalletButtonPreference.php';

class MercadoPagoWalletButtonModuleFrontController extends ModuleFrontController
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
        $preference = new WalletButtonPreference();
        try {
            $preference->verifyModuleParameters();
            $createPreference = $preference->createPreference($this->context->cart);

            if (is_array($createPreference) && array_key_exists('init_point', $createPreference)) {
                $preference->saveCreatePreferenceData(
                    $this->context->cart,
                    $createPreference['notification_url']
                );

                $this->getResponse($createPreference, 200);
            }

            $this->getResponse($createPreference, 500);
        } catch (Exception $err) {
            MPLog::generate('Exception Message: ' . $err->getMessage());
            $this->redirectError($preference, Tools::displayError());
        }
    }

    /**
     * Get response with preferenceId
     *
     * @param array $preference
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
     * @param AbstractPreference $preference
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
