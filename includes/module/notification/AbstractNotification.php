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
    public $mercadopago;
    public $mp_transaction;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
    }

    /**
     * Verify if received notification and save on BD
     *
     * @param mixed $cart
     * @return void
     */
    public function verifyWebhook($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update([
            "received_webhook" => true
        ]);
        MPLog::generate('Notification received on cart id ' . $cart->id);
    }

    /**
     * Verify payments
     *
     * @param mixed $payments
     * @return void
     */
    public function verifyPayments($payments)
    {
        $this->payments_data['payments_id'] = array();
        $this->payments_data['payments_type'] = array();
        $this->payments_data['payments_method'] = array();
        $this->payments_data['payments_status'] = array();
        $this->payments_data['payments_amount'] = array();

        foreach ($payments as $payment) {
            $payment_info = $this->mercadopago->getPaymentStandard($payment['id']);
            $payment_info = $payment_info['response'];
            $this->status = $payment_info['status'];

            $this->payments_data['payments_id'][] = $payment_info['id'];
            $this->payments_data['payments_type'][] = $payment_info['payment_type_id'];
            $this->payments_data['payments_method'][] = $payment_info['payment_method_id'];
            $this->payments_data['payments_status'][] = $payment_info['transaction_amount'];
            $this->payments_data['payments_amount'][] = $this->status;

            if ($this->status == 'approved') {
                $this->amount['apro'] += $payment_info['transaction_amount'];
            } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
                $this->amount['pend'] += $payment_info['transaction_amount'];
            }
        }
    }

    /**
     * Validate order state
     *
     * @param string $status
     * @return string
     */
    public function validateOrderState()
    {
        if ($this->amount['apro'] >= $this->total) {
            $this->amount['total'] = $this->amount['apro'];
            $this->order_state = $this->getNotificationPaymentState('approved');
        } elseif ($this->amount['pend'] >= $this->total) {
            $this->amount['total'] = $this->amount['pend'];
            $this->order_state = $this->getNotificationPaymentState('in_process');
        } else {
            $this->order_state = $this->getNotificationPaymentState($this->status);
        }

        return $this->order_state;
    }

    /**
     * Create order on Prestashop database
     *
     * @param mixed $cart
     * @param float $total
     * @return void
     */
    public function createOrder($cart)
    {
        try {
            $this->module->validateOrder(
                $cart->id,
                $this->order_state,
                $this->total,
                "Mercado Pago",
                null,
                array(),
                (int) $cart->id_currency,
                false,
                $this->customer_secure_key
            );

            $this->saveCreateOrderData($cart);

            $order = new Order($this->order_id);
            $payments = $order->getOrderPaymentCollection();
            $payments[0]->transaction_id = $this->merchant_order_id;
            $payments[0]->update();

            MPLog::generate('Order created successfully on cart id ' . $cart->id);
            $this->getNotificationResponse("The order has been created", 201);
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been created on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );
            $this->getNotificationResponse("The order has not been created", 422);
        }
    }

    /**
     * Update order on Prestashop database
     *
     * @param mixed $cart
     * @return void
     */
    public function updateOrder($cart)
    {
        $order = new Order($this->order_id);
        $actual_status = (int) $order->getCurrentState();
        
        if ($this->order_state != $actual_status) {
            try {
                $order->setCurrentState($this->order_state);
                $this->saveUpdateOrderData($cart);
                MPLog::generate('Updated order '.$this->order_id.' for the status of '.$this->order_state);
                $this->getNotificationResponse("The order has been updated", 201);
            } catch (Exception $e) {
                MPLog::generate(
                    'The order has not been updated on cart id '.$cart->id.' - '.$e->getMessage(),
                    'error'
                );
                $this->getNotificationResponse("The order has not been updated", 422);
            }
        } else {
            MPLog::generate('The order status is the same', 'warning');
            $this->getNotificationResponse("The order status is the same", 422);
        }
    }

    /**
     * Save payments info on mp_transaction table
     *
     * @param mixed $cart
     * @param mixed $data
     * @param int $order_id
     * @return void
     */
    public function saveCreateOrderData($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update([
            "order_id" => $this->order_id,
            "payment_id" => implode(',', $this->payments_data['payments_id']),
            "payment_type" => implode(',', $this->payments_data['payments_type']),
            "payment_method" => implode(',', $this->payments_data['payments_method']),
            "payment_status" => implode(',', $this->payments_data['payments_status']),
            "payment_amount" => implode(',', $this->payments_data['payments_amount']),
            "notification_url" => $_SERVER['REQUEST_URI'],
            "merchant_order_id" => $this->merchant_order_id,
            "received_webhook" => true,
        ]);
    }

    /**
     * Update payments info on mp_transaction table
     *
     * @param mixed $cart
     * @param mixed $data
     * @return void
     */
    public function saveUpdateOrderData($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update([
            "payment_status" => implode(',', $this->payments_data['payments_status'])
        ]);
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
