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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

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
    public $ps_order_state;
    public $order_state_lang;
    public $customer_secure_key;
    public $mpuseful;
    public $checkout;
    public $mp_transaction_amount;

    public function __construct($transaction_id)
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
        $this->ps_order_state = new PSOrderState();
        $this->ps_order_state_lang = new PSOrderStateLang();
        $this->transaction_id = $transaction_id;
        $this->mpuseful = MPUseful::getInstance();

        $this->amount = 0;
        $this->pending = 0;
        $this->approved = 0;
    }

    /**
     * Verify if received notification and save on BD
     *
     * @param  mixed $cart
     * @return void
     */
    public function verifyWebhook($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update(
            [
                "received_webhook" => true
            ]
        );
        MPLog::generate('Notification received on cart id ' . $cart->id);
    }

    /**
     * @return mixed
     */
    public function validateOrderState()
    {
        if ($this->status != null) {
            if ($this->total > 0 && $this->approved >= $this->total) {
                $this->amount = $this->approved;
                $this->order_state = $this->getNotificationPaymentState('approved');
            } elseif ($this->total > 0 && $this->pending >= $this->total) {
                $this->amount = $this->pending;
                $this->order_state = $this->getNotificationPaymentState('in_process');
            } else {
                $this->order_state = $this->getNotificationPaymentState($this->status);
            }

            return $this->order_state;
        }
    }

    /**
     * Update order transaction
     *
     * @param mixed $order
     * @return void
     */
    public function updateOrderTransaction($order)
    {
        try {
            $order_payments = $order->getOrderPaymentCollection();
            $order_payments[0]->amount = $this->approved;
            $order_payments[0]->update();
        } catch (Exception $e) {
            MPLog::generate('Error on update order transaction: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Create order on Prestashop database
     *
     * @param  mixed $cart
     * @param  float $total
     * @return void
     */
    public function createOrder($cart, $custom_create_order = false)
    {
        try {
            $payment_amount = $this->mp_transaction_amount;
            if ($this->mp_transaction_amount > $this->amount) {
                $payment_amount = $this->amount;
            }
            $this->module->validateOrder(
                $cart->id,
                $this->order_state,
                $payment_amount,
                "Mercado Pago",
                null,
                array(),
                (int) $cart->id_currency,
                false
            );

            $this->order_id = Order::getIdByCartId($cart->id);
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

    /**
     * Validate status to update order
     *
     * @param  mixed $cart
     * @return void
     */
    public function updateOrder($cart)
    {
        $order = new Order($this->order_id);
        $actual_status = (int) $order->getCurrentState();
        $validate_actual = $this->validateActualStatus($actual_status);

        $status_approved = $this->getNotificationPaymentState('approved');
        $status_pending = $this->getNotificationPaymentState('pending');
        $status_inprocess = $this->getNotificationPaymentState('in_process');
        $status_authorized = $this->getNotificationPaymentState('authorized');
        $status_cancelled = $this->getNotificationPaymentState('cancelled');
        $status_rejected = $this->getNotificationPaymentState('rejected');
        $status_refunded = $this->getNotificationPaymentState('refunded');
        $status_charged = $this->getNotificationPaymentState('charged_back');
        $status_mediation = $this->getNotificationPaymentState('in_mediation');

        if ($this->order_id != 0 && $this->status != null) {
            switch ($this->order_state) {
                case $status_approved:
                    MPLog::generate('Entered the APPROVED rule');
                    $this->ruleApproved($cart, $order, $status_approved, $actual_status, $validate_actual);
                    break;

                case $status_pending:
                    MPLog::generate('Entered the PENDING rule');
                    $this->ruleProcessing($cart, $order, $status_pending, $actual_status, $validate_actual);
                    break;

                case $status_inprocess:
                    MPLog::generate('Entered the IN_PROCESS rule');
                    $this->ruleProcessing($cart, $order, $status_inprocess, $actual_status, $validate_actual);
                    break;

                case $status_authorized:
                    MPLog::generate('Entered the AUTHORIZED rule');
                    $this->ruleProcessing($cart, $order, $status_authorized, $actual_status, $validate_actual);
                    break;

                case $status_cancelled:
                    MPLog::generate('Entered the CANCELLED rule');
                    $this->ruleFailed($cart, $order, $status_cancelled, $actual_status, $validate_actual);
                    break;

                case $status_rejected:
                    MPLog::generate('Entered the REJECTED rule');
                    $this->ruleFailed($cart, $order, $status_rejected, $actual_status, $validate_actual);
                    break;

                case $status_refunded:
                    MPLog::generate('Entered the REFUNDED rule');
                    $this->ruleDevolution($cart, $order, $status_refunded, $actual_status);
                    break;

                case $status_charged:
                    MPLog::generate('Entered the CHARGED_BACK rule');
                    $this->ruleDevolution($cart, $order, $status_charged, $actual_status);
                    break;

                case $status_mediation:
                    MPLog::generate('Entered the MEDIATION rule');
                    $this->ruleDevolution($cart, $order, $status_mediation, $actual_status);
                    break;

                default:
                    break;
            }
        } else {
            MPLog::generate('Order does not exist', 'error');
            $this->getNotificationResponse('Order does not exist', 404);
        }
    }

    /**
     * Rule to update approved order
     *
     * @return void
     */
    public function ruleApproved($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($actual_status == $status) {
            MPLog::generate('Order status is the same', 'warning');
            $this->getNotificationResponse('Order status is the same', 202);
        } elseif ($this->total > $this->approved) {
            $this->ruleFraud($cart, $order, $actual_status, $validate_actual);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->getNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleProcessing($cart, $order, $status, $actual_status, $validate_actual)
    {
        $status_approved = $this->getNotificationPaymentState('approved');

        if ($actual_status == $status) {
            MPLog::generate('Order status is the same', 'warning');
            $this->getNotificationResponse('Order status is the same', 202);
        } elseif ($actual_status == $status_approved) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->getNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->getNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleFailed($cart, $order, $status, $actual_status, $validate_actual)
    {
        $status_approved = $this->getNotificationPaymentState('approved');

        if ($actual_status == $status) {
            MPLog::generate('Order status is the same', 'warning');
            $this->getNotificationResponse('Order status is the same', 202);
        } elseif ($actual_status == $status_approved) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->getNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->getNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update chargedback, refunded and inmediation order
     *
     * @return void
     */
    public function ruleDevolution($cart, $order, $status, $actual_status)
    {
        if ($actual_status == $status) {
            MPLog::generate('Order status is the same', 'warning');
            $this->getNotificationResponse('Order status is the same', 202);
        } else {
            $this->updatePrestashopOrder($cart, $order);
        }
    }

    /**
     * Rule to update order with payment with possible fraud
     *
     * @return void
     */
    public function ruleFraud($cart, $order, $actual_status, $validate_actual)
    {
        MPLog::generate('The order '. $this->order_id .' have a possible payment fraud', 'error');

        $status_fraud = $this->getNotificationPaymentState('possible_fraud');
        $this->order_state = $status_fraud;

        if ($actual_status == $status_fraud) {
            MPLog::generate('Order status is the same', 'warning');
            $this->getNotificationResponse('Order status is the same', 202);
        } elseif ($validate_actual == true) {
            MPLog::generate('The order '. $this->order_id .' has been updated to possible fraud status', 'error');
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->getNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Update order on Prestashop database
     *
     * @param  mixed $cart
     * @return void
     */
    public function updatePrestashopOrder($cart, $order)
    {
        try {
            $this->generateLogs();

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
    }

    /**
     * Save payments info on mp_transaction table
     *
     * @param  mixed $cart
     * @param  mixed $data
     * @param  int   $order_id
     * @return void
     */
    public function saveCreateOrderData($cart)
    {
        $payments_id = $this->verifyValue('payments_id');

        $payments_type = $this->verifyValue('payments_type');

        $payments_method = $this->verifyValue('payments_method');

        $payments_status = $this->verifyValue('payments_status');

        $payments_amount = $this->verifyValue('payments_amount');

        $dataToCreate =  [
            "order_id" => $this->order_id,
            "notification_url" => $_SERVER['REQUEST_URI'],
            "merchant_order_id" => $this->transaction_id,
            "received_webhook" => true,
        ];

        if ($payments_id) {
            $dataToCreate['payment_id'] = $payments_id;
        }

        if ($payments_type) {
            $dataToCreate['payment_type'] = $payments_type;
        }

        if ($payments_method) {
            $dataToCreate['payment_method'] = $payments_method;
        }

        if ($payments_status) {
            $dataToCreate['payment_status'] = $payments_status;
        }

        if ($payments_amount) {
            $dataToCreate['payment_amount'] = $payments_amount;
        }

        $this->mp_transaction->where('cart_id', '=', $cart->id)->update($dataToCreate);
    }

    /**
     * Update payments info on mp_transaction table
     *
     * @param  mixed $cart
     * @param  mixed $data
     * @return void
     */
    public function saveUpdateOrderData($cart)
    {
        $payments_id = $this->payments_data['payments_id'];
        $payments_type = $this->payments_data['payments_type'];
        $payments_method = $this->payments_data['payments_method'];
        $payments_status = $this->payments_data['payments_status'];
        $payments_amount = $this->payments_data['payments_amount'];

        $this->mp_transaction->where('cart_id', '=', $cart->id)->update(
            [
                "payment_id" => pSQL(is_array($payments_id) ? implode(',', $payments_id) : $payments_id),
                "payment_type" => pSQL(is_array($payments_type) ? implode(',', $payments_type) : $payments_type),
                "payment_method" => pSQL(is_array($payments_method) ? implode(',', $payments_method) : $payments_method),
                "payment_status" => pSQL(is_array($payments_status) ? implode(',', $payments_status) : $payments_status),
                "payment_amount" => pSQL(is_array($payments_amount) ? implode(',', $payments_amount) : $payments_amount),
            ]
        );
    }

    /**
     * @param  $state
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
            'authorized' => 'MERCADOPAGO_STATUS_8',
            'possible_fraud' => 'MERCADOPAGO_STATUS_9',
        );

        return Configuration::get($payment_states[$state]);
    }

    /**
     * @param  integer $actual
     * @return bool
     */
    public function validateActualStatus($actual)
    {
        $result = $this->ps_order_state->where('id_order_state', '=', (int) $actual)->get();

        if ($result['module_name'] === 'mercadopago' || $this->getBackOrderStatus($actual)) {
            return true;
        }

        return false;
    }

    /**
     * @param  integer $actual
     * @return bool
     */
    public function getBackOrderStatus($actual)
    {
        $result = $this->ps_order_state_lang->columns(['id_order_state', 'name'])
            ->where('template', '=', 'outofstock')
            ->getAll();

        $count = count($result);

        foreach ($result as $row) {
            if ($row['id_order_state'] == $actual && $count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get responses to send for notification
     *
     * @param  string  $message
     * @param  integer $code
     * @return void
     */
    public static function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "message" => $message,
            "version" => MP_VERSION
        );

        echo json_encode($response);
        return http_response_code($code);
    }

    /**
     * Get order total
     *
     * @return float
     */
    public function getTotal($cart, $checkout)
    {
        $correctedTotal = $this->mpuseful->getCorrectedTotal($cart, $checkout);
        $localization = Configuration::get('MERCADOPAGO_SITE_ID');

        if ($localization == 'MCO' || $localization == 'MLC') {
            return Tools::ps_round($correctedTotal['amount'], 0);
        }

        return Tools::ps_round($correctedTotal['amount'], 2);
    }

    /**
     * Generate notification logs
     *
     * @param  string $method
     * @return void
     */
    public function generateLogs()
    {
        $logs = [
          "transaction_id" => $this->transaction_id,
          "cart_total" => $this->total,
          "order_id" => $this->order_id,
          "payment_status" => $this->status,
          "approved_order_state" => $this->approved,
          "pending_order_state" => $this->pending,
          "order_state" => $this->order_state,
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate('Order id ' . $this->order_id . ' notification logs: ' . $encodedLogs);
    }

    /**
     * Verify value
     *
     * @param  string $method
     * @return string
     */
    public function verifyValue($key)
    {
        if (!isset($this->payments_data[$key])) {
            return null;
        }

        if (is_array($this->payments_data[$key])) {
            return implode(',', $this->payments_data[$key]);
        }

        return $this->payments_data[$key];
    }
}
