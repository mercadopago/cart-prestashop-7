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

class AbstractNotification
{
    public $total;
    public $module;
    public $status;
    public $amount;
    public $aproved;
    public $pending;
    public $order_id;
    public $mercadopago;
    public $order_state;
    public $payments_data;
    public $transaction_id;
    public $mp_transaction;
    public $customer_secure_key;

    public function __construct($transaction_id = null, $customer_secure_key)
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
        $this->transaction_id = $transaction_id;
        $this->customer_secure_key = $customer_secure_key;

        $this->amount = 0;
        $this->pending = 0;
        $this->approved = 0;
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
     * @return mixed
     */
    public function validateOrderState()
    {
        if ($this->status != null) {
            if ($this->approved >= $this->total) {
                $this->amount = $this->approved;
                $this->order_state = $this->getNotificationPaymentState('approved');
            } elseif ($this->pending >= $this->total) {
                $this->amount = $this->pending;
                $this->order_state = $this->getNotificationPaymentState('in_process');
            } else {
                $this->order_state = $this->getNotificationPaymentState($this->status);
            }

            return $this->order_state;
        }
    }

    /**
     * Create order on Prestashop database
     *
     * @param mixed $cart
     * @param float $total
     * @return void
     */
    public function createOrder($cart, $custom_create_order = false)
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
                false
            );

            $this->order_id = Order::getOrderByCartId($cart->id);
            $order = new Order($this->order_id);

            $payments = $order->getOrderPaymentCollection();
            $payments[0]->transaction_id = $this->transaction_id;
            $payments[0]->update();

            $this->saveCreateOrderData($cart);

            MPLog::generate('Order created successfully on cart id ' . $cart->id);

            if ($custom_create_order != true) {
                $this->getNotificationResponse('The order has been created', 201);
            }
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been created on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );

            if ($custom_create_order != true) {
                $this->getNotificationResponse('The order has not been created', 422);
            }
        }
    }

    public function updateOrder($cart)
    {
        $order = new Order($this->order_id);
        $actual_status = (int) $order->getCurrentState();
        $validate_actual = $this->validateActualStatus($actual_status);
        $status_approved = $this->getNotificationPaymentState('approved');
        $status_refunded = $this->getNotificationPaymentState('refunded');
        $status_charged = $this->getNotificationPaymentState('charged_back');
        $status_mediation = $this->getNotificationPaymentState('in_mediation');

        if ($this->status != null &&
            $validate_actual == true &&
            $actual_status == $status_approved &&
            $this->order_state != $status_refunded &&
            $this->order_state != $status_charged &&
            $this->order_state != $status_mediation
        ) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->getNotificationResponse('It is not possible to update this approved payment', 422);
        } elseif ($validate_actual == false) {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->getNotificationResponse('The order has been updated to a status that does not belong to MP', 422);
        } else {
            if ($this->order_id != 0 && $this->order_state != $actual_status && $validate_actual == true) {
                try {
                    $order->setCurrentState($this->order_state);
                    $this->saveUpdateOrderData($cart);
                    MPLog::generate('Updated order ' . $this->order_id . ' for the status of ' . $this->order_state);
                    $this->getNotificationResponse('The order has been updated', 201);
                } catch (Exception $e) {
                    MPLog::generate(
                        'The order has not been updated on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                        'error'
                    );
                    $this->getNotificationResponse('The order has not been updated', 422);
                }
            } else {
                MPLog::generate('Order does not exist or Order status is the same', 'warning');
                $this->getNotificationResponse('Order does not exist or Order status is the same', 422);
            }
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
        $payments_id = $this->payments_data['payments_id'];
        $payments_type = $this->payments_data['payments_type'];
        $payments_method = $this->payments_data['payments_method'];
        $payments_status = $this->payments_data['payments_status'];
        $payments_amount = $this->payments_data['payments_amount'];

        $this->mp_transaction->where('cart_id', '=', $cart->id)->update([
            "order_id" => $this->order_id,
            "payment_id" => is_array($payments_id) ? implode(',', $payments_id) : $payments_id,
            "payment_type" => is_array($payments_type) ? implode(',', $payments_type) : $payments_type,
            "payment_method" => is_array($payments_method) ? implode(',', $payments_method) : $payments_method,
            "payment_status" => is_array($payments_status) ? implode(',', $payments_status) : $payments_status,
            "payment_amount" => is_array($payments_amount) ? implode(',', $payments_amount) : $payments_amount,
            "notification_url" => $_SERVER['REQUEST_URI'],
            "merchant_order_id" => $this->transaction_id,
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
        $payments_status = $this->payments_data['payments_status'];

        $this->mp_transaction->where('cart_id', '=', $cart->id)->update([
            "payment_status" => is_array($payments_status) ? implode(',', $payments_status) : $payments_status
        ]);
    }

    /**
     * @param $state
     * @return mixed
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
     * @param integer $actual
     * @return bool
     */
    public function validateActualStatus($actual)
    {
        $query = 'SELECT module_name FROM `' . _DB_PREFIX_ . 'order_state` WHERE id_order_state = ' . (int) $actual;
        $sql = Db::getInstance()->getRow($query);

        if ($sql['module_name'] === 'mercadopago') {
            return true;
        }

        return false;
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

        echo Tools::jsonEncode($response);
        return var_dump(http_response_code($code));
    }

    /**
     * @return mixed
     */
    public function getTotal($cart)
    {
        $total = (float) $cart->getOrderTotal();
        $localization = Configuration::get('MERCADOPAGO_SITE_ID');
        if ($localization == 'MCO' || $localization == 'MLC') {
            return Tools::ps_round($total, 2);
        }

        return $total;
    }
}
