<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
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
        $cart = $this->context->cart;
        $preference = new StandardPreference();
        $preference->verifyModuleParameters();

        //modal checkout
        if ($preference->settings['MERCADOPAGO_STANDARD_MODAL'] != "") {
            return $this->standardModalCheckout($preference);
        }

        //redirect checkout
        return $this->standardRedirectCheckout($cart, $preference);
    }

    /**
     * Verify if standard checkout is redirect
     *
     * @param mixed $cart
     * @return void
     */
    public function standardRedirectCheckout($cart, $preference)
    {
        $createPreference = $preference->createPreference($cart);

        //save data in mercadopago table
        $mp_module = new MPModule();
        $mp_module = $mp_module->where('version', '=', MP_VERSION)->get();
        $payment_test = Configuration::get('MERCADOPAGO_SANDBOX_STATUS');

        $mp_transaction = new MPTransaction();
        $count = $mp_transaction->where('cart_id', '=', $cart->id)->count();

        if ($count == 0) {
            $mp_transaction->create([
                'total' => $cart->getOrderTotal(),
                'cart_id' => $cart->id,
                'customer_id' => $cart->id_customer,
                'notification_url' => $createPreference['notification_url'],
                'is_payment_test' => $payment_test,
                'mp_module_id' => $mp_module['id_mp_module']
            ]);
        } else {
            $mp_transaction->where('cart_id', '=', $cart->id)->update([
                'total' => $cart->getOrderTotal(),
                'customer_id' => $cart->id_customer,
                'notification_url' => $createPreference['notification_url'],
                'is_payment_test' => $payment_test
            ]);
        }

        //success redirect link for sandbox and production mode
        if (array_key_exists('init_point', $createPreference)) {
            MPLog::generate('Cart id ' . $cart->id . ' - Preference created successfully');
            return Tools::redirectLink($createPreference['init_point']);
        }

        //failure redirect link
        return $preference->redirectError();
    }

    /**
     * Verify if standard checkout is modal
     *
     * @param mixed $cart
     * @return void
     */
    public function standardModalCheckout($preference)
    {
        $back_url = Tools::getValue('back_url');

        if (isset($back_url)) {
            return Tools::redirectLink($back_url);
        }

        return $preference->redirectError();
    }
}
