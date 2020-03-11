<?php
/**
 * 2007-2020 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

require_once MP_ROOT_URL . '/includes/module/notification/IpnNotification.php';

class MercadoPagoStandardValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        $typeReturn = Tools::getValue('typeReturn');
        $payments_id = Tools::getValue('collection_id');

        if (isset($payments_id) && $payments_id != 'null' && $typeReturn != 'failure') {
            $cart_id = Tools::getValue('external_reference');
            $cart = new Cart($cart_id);
            $total = $cart->getOrderTotal();

            $uri = __PS_BASE_URI__ . 'index.php?controller=order-confirmation';
            $uri .= '&id_cart=' . $cart_id;
            $uri .= '&id_module=' . $this->module->id;
            $uri .= '&typeReturn=' . $typeReturn;
            $uri .= '&payment_id=' . implode(',', $payments_id);
            $uri .= '&total=' . $total;

            $this->createOrder($cart, $uri);
        }

        $this->redirectError();
    }

    /**
     * Create order without notification
     *
     * @param mixed $cart
     * @param string $url
     * @return void
     */
    public function createOrder($cart, $url)
    {
        $customer_secure_key = $cart->secure_key;
        $notification = new IpnNotification(null, $customer_secure_key);
        $notification = $notification->createStandardOrder($cart);

        return Tools::redirectLink($url);
    }

    /**
     * Redirect if any errors occurs
     *
     * @return void
     */
    public function redirectError()
    {
        MPLog::generate('The mercadopago checkout callback failed', 'error');
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }
}
