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

class AbstractPreference
{
    public $module;
    public $mpuseful;
    public $mercadopago;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mpuseful = MPUseful::getInstance();
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Get notification payment status
     *
     * @param string $state
     * @return void
     */
    public function getNotificationPaymentState($state)
    {
        $payment_states = array(
            'in_process' => 'MERCADOPAGO_STATUS_0',
            'approved' => 'MERCADOPAGO_STATUS_1',
            'cancelled' => 'MERCADOPAGO_STATUS_2',
            'rejected' => 'MERCADOPAGO_STATUS_3',
            'refunded' => 'MERCADOPAGO_STATUS_4',
            'charged_back' => 'MERCADOPAGO_STATUS_5',
            'in_mediation' => 'MERCADOPAGO_STATUS_6',
            'pending' => 'MERCADOPAGO_STATUS_7',
            'authorized' => 'MERCADOPAGO_STATUS_8'
        );

        return Configuration::get($payment_states[$state]);
    }

    /**
     * Get responses to send for notification
     *
     * @param string $message
     * @param integer $code
     * @return void
     */
    public function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "message" => $message
        );
        
        echo json_encode($response);
        return var_dump(http_response_code($code));
    }
}