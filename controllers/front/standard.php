<?php
/**
 * 2007-2022 PrestaShop
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
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/preference/StandardPreference.php';

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
    public function postProcess()
    {
        $preference = new StandardPreference();
        try {
            $preference->verifyModuleParameters();

            //modal checkout
            if ($preference->settings['MERCADOPAGO_STANDARD_MODAL'] != "") {
                $this->standardModalCheckout($preference);
                return;
            }

            //redirect checkout
            $this->standardRedirectCheckout($this->context->cart, $preference);
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
        }
    }

    /**
     * @param $cart
     * @param StandardPreference $preference
     * @throws Exception
     */
    public function standardRedirectCheckout($cart, StandardPreference $preference)
    {
        $createPreference = $preference->createPreference($cart);
        if (is_array($createPreference) && array_key_exists('init_point', $createPreference)) {
            $preference->saveCreatePreferenceData($cart, $createPreference['notification_url']);
            Tools::redirectLink($createPreference['init_point']);
        }

        $preference->redirectError();
    }

    /**
     * @param StandardPreference $preference
     */
    public function standardModalCheckout(StandardPreference $preference)
    {
        $back_url = Tools::getValue('back_url');
        if (isset($back_url)) {
            Tools::redirectLink($back_url);
        }

        $preference->redirectError();
    }
}
