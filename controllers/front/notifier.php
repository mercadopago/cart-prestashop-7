<?php

/**
 * 2007-2021 PrestaShop
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
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * <ModuleClassName> => Notifier
 * <FileName> => notifier.php
 * 
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */


class MercadoPagoNotifierModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        MPLog::generate('--------Core Notification--------');

        try {
            $payment_id = Tools::getValue('payment_id');
            $external_reference = Tools::getValue('external_reference');
            $timestamp = Tools::getValue('timestamp');

            if (
                !empty($payment_id)
                && !empty($external_reference)
                && !empty($timestamp)
            ) {
                $this->getNotificationResponse(
                    'ok',
                    200
                );
            }
            else {
                $this->getNotificationResponse('Some parameters are empty', 400);
            }
        } catch (Exception $e) {
            MPLog::generate('Exception Message: ' . $e->getMessage());
        }
    }
    /**
     * Get error response
     *
     * @return void
     */
    public function getErrorResponse()
    {
        $this->getNotificationResponse(
            'The notification does not have the necessary parameters',
            500
        );
    }

    /**
     * Get error response
     *
     * @return void
     */

    public function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "message" => $message,
            "version" => MP_VERSION
        );

        echo Tools::jsonEncode($response);
        return http_response_code($code);
    }
}
