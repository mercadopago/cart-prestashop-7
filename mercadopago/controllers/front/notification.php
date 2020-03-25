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
require_once MP_ROOT_URL . '/includes/module/notification/WebhookNotification.php';

class MercadoPagoNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        $topic = Tools::getValue('topic');
        $checkout = Tools::getValue('checkout');
        $secure_key = Tools::getValue('customer');
        $transaction_id = Tools::getValue('id');

        $cart = new Cart(Tools::getValue('cart_id'));
        $customer = new Customer((int) $cart->id_customer);
        $customer_secure_key = $customer->secure_key;
        $notification = new WebhookNotification($transaction_id, $customer_secure_key);

        //Validate checkout notification
        if ($checkout == 'standard' && $topic == 'merchant_order' && $customer_secure_key == $secure_key) {
            $notification = new IpnNotification($transaction_id, $customer_secure_key);
            $notification->receiveNotification($cart);
        } elseif ($checkout == 'custom' && $topic == 'payment' && $customer_secure_key == $secure_key) {
            $notification->receiveNotification($cart);
        } else {
            MPLog::generate('The notification does not have the necessary parameters to create an order', 'error');
            $notification->getNotificationResponse("The notification does not have the necessary parameters", 422);
        }
    }
}
