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

require_once MP_ROOT_URL . '/includes/module/preference/WalletButtonPreference.php';

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
     * @param  string  $preferenceId
     * @param  integer $code
     */
    public function getResponse($preference, $code)
    {
        header('Content-type: application/json');
        $response = array(
            'code' => $code,
            'preference' => $preference,
        );

        echo json_encode($response);
        http_response_code($code);
        exit();
    }

    /**
     * Redirect to checkout with error
     *
     * @param Preference  $preference
     * @param string      $errorMessage
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
