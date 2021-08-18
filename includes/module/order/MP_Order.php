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
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

class MP_Order
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
    public $request;

    public function __construct($transaction_id, $status)
    {
        $this->request = Request::getinstance();
        $this->module = Module::getInstanceByName('mercadopago');
        $this->mercadopago = MPApi::getInstance();
        $this->mp_transaction = new MPTransaction();
        $this->ps_order_state = new PSOrderState();
        $this->ps_order_state_lang = new PSOrderStateLang();
        $this->transaction_id = $transaction_id;
        $this->status = $status;

        $this->amount = 0;
        $this->pending = 0;
        $this->approved = 0;
    }

    /**
     * Verify if received notification and save on BD
     *
     * @param mixed $cart Cart
     * 
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
     * Get total of cart 
     * 
     * @param mixed $cart Cart
     * 
     * @return float 
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

    /**
     * Verify if order exists then get order_id
     *
     * @param mixed $cart Cart
     * 
     * @return void
     */
    public function getOrderId($cart)
    {
        $orderId = Order::getOrderByCartId($cart->id);
        $this->order_id = $orderId;

        return $orderId;
    }

    /**
     * Get Payment Status from Notification
     * 
     * @param String $state OrderState
     * 
     * @return mixed
     */
    public function getNotificationPaymentState($state)
    {
        $payment_states = array(
            'in_process'     => 'MERCADOPAGO_STATUS_0',
            'approved'       => 'MERCADOPAGO_STATUS_1',
            'cancelled'      => 'MERCADOPAGO_STATUS_2',
            'rejected'       => 'MERCADOPAGO_STATUS_3',
            'refunded'       => 'MERCADOPAGO_STATUS_4',
            'charged_back'   => 'MERCADOPAGO_STATUS_5',
            'in_mediation'   => 'MERCADOPAGO_STATUS_6',
            'pending'        => 'MERCADOPAGO_STATUS_7',
            'authorized'     => 'MERCADOPAGO_STATUS_8',
            'possible_fraud' => 'MERCADOPAGO_STATUS_9',
        );

        return Configuration::get($payment_states[$state]);
    }

    /**
     * Get back Order Status
     * 
     * @param integer $actual OrderStatus
     * 
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
     * Validate Actual Status
     * 
     * @param integer $actual OrderStatus
     * 
     * @return bool
     */
    public function validateActualStatus($actual)
    {
        $result = $this->ps_order_state->where('id_order_state', '=', (int) $actual)->get();

        if ($result['module_name'] === 'mercadopago' 
            || $this->getBackOrderStatus($actual)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Validate status to update order
     *
     * @param mixed $cart Cart
     * 
     * @return void
     */
    public function updateOrder($cart)
    {
        $order = new Order($this->order_id);
        $actual_status = (int) $order->getCurrentState();
        $validate_actual = $this->validateActualStatus($actual_status);

        if ($this->order_id != 0 && $this->status != null) {
            switch ($this->status) {
            case 'approved':
                MPLog::generate('Entered the APPROVED rule');
                $this->ruleApproved($cart, $order, $validate_actual);
                break;

            case 'pending':
                MPLog::generate('Entered the PENDING rule');
                $this->ruleProcessing($cart, $order, 'pending', $validate_actual);
                break;

            case 'in_process':
                MPLog::generate('Entered the IN_PROCESS rule');
                $this->ruleProcessing($cart, $order, 'in_process', $validate_actual);
                break;

            case 'authorized':
                MPLog::generate('Entered the AUTHORIZED rule');
                $this->ruleProcessing($cart, $order, 'authorized', $validate_actual);
                break;

            case 'cancelled':
                MPLog::generate('Entered the CANCELLED rule');
                $this->ruleFailed($cart, $order, 'cancelled', $validate_actual);
                break;

            case 'rejected':
                MPLog::generate('Entered the REJECTED rule');
                $this->ruleFailed($cart, $order, 'rejected', $validate_actual);
                break;

            case 'refunded':
                MPLog::generate('Entered the REFUNDED rule');
                $this->ruleDevolution($cart, $order, 'refunded', $actual_status);
                break;

            case 'charged_back':
                MPLog::generate('Entered the CHARGED_BACK rule');
                $this->ruleDevolution($cart, $order, 'charged_back', $actual_status);
                break;

            case 'in_mediation':
                MPLog::generate('Entered the MEDIATION rule');
                $this->ruleDevolution($cart, $order, 'in_mediation', $actual_status);
                break;

            case 'possible_fraud':
                MPLog::generate('Entered the FRAUD rule');
                $this->ruleFraud($cart, $order, $actual_status);

            default:
                MPLog::generate('Order ' . $this->order_id . 'could not be updated. Status ' . $this->status . 'not found');
                $this->request->erroResponse();
                break;
            }
        } else {
            MPLog::generate('Order does not exist', 'error');
            $this->request->response('Order does not exist', 404);
        }
    }

    /**
     * Generate Logs
     * 
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
        ];

        $encodedLogs = Tools::jsonEncode($logs);
        MPLog::generate('Order id ' . $this->order_id . ' notification logs: ' . $encodedLogs);
    }

    /**
     * Verify merchant order payments
     *
     * @param mixed $payments
     * @return void
     */
    public function verifyPayments($payment)
    {
        $this->payments_data['payments_id'] = array();
        $this->payments_data['payments_type'] = array();
        $this->payments_data['payments_method'] = array();
        $this->payments_data['payments_status'] = array();
        $this->payments_data['payments_amount'] = array();

        $this->payments_data['payments_id'][] = $payment['id'];
        $this->payments_data['payments_type'][] = $payment['payment_type_id'];
        $this->payments_data['payments_method'][] = $payment['payment_method_id'];
        $this->payments_data['payments_amount'][] = $payment['total'];
        $this->payments_data['payments_status'][] = $this->status;

        if ($this->status == 'approved') {
            $this->approved += $payment['transaction_amount'];
        } elseif ($this->status == 'in_process' || $this->status == 'pending' || $this->status == 'authorized') {
            $this->pending += $payment['transaction_amount'];
        }
    }

    /**
     * Update payments info on mp_transaction table
     *
     * @param mixed $cart Cart
     * 
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
                "payment_id" => is_array($payments_id) ? implode(',', $payments_id) : $payments_id,
                "payment_type" => is_array($payments_type) ? implode(',', $payments_type) : $payments_type,
                "payment_method" => is_array($payments_method) ? implode(',', $payments_method) : $payments_method,
                "payment_status" => is_array($payments_status) ? implode(',', $payments_status) : $payments_status,
                "payment_amount" => is_array($payments_amount) ? implode(',', $payments_amount) : $payments_amount,
            ]
        );
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
     * Update order on Prestashop database
     *
     * @param mixed $cart  Cart
     * @param mixed $order Order
     * 
     * @return void
     */
    public function updatePrestashopOrder($cart, $order, $status)
    {
        try {
            $this->generateLogs();
            $actual_status = (int) $order->getCurrentState();
            $order->setCurrentState($status);
            $this->saveUpdateOrderData($cart);            

            MPLog::generate('Updated order ' . $this->order_id . ' to [' . $status . ']'. $this->getOrderState($status));

            $response         	    = array();
            $response['old_status'] = $this->getOrderState($actual_status);
            $response['new_status'] = $this->getOrderState($status);
            $response['timestamp']  = time();

            $this->request->response($response, 200);
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been updated on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );
            $this->request->response('The order has not been updated', 422);
        }
    }

    /**
     * @param  $state
     * @return mixed
     */
    public function getOrderState($state)
    {
        $payment_states = array();
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_0')] = 'in_process';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_1')] = 'approved';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_2')] = 'cancelled';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_3')] = 'rejected';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_4')] = 'refunded';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_5')] = 'charged_back';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_6')] = 'in_mediation';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_7')] = 'pending';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_8')] = 'authorized';
        $payment_states[Configuration::get('MERCADOPAGO_STATUS_9')] = 'possible_fraud';

        return $payment_states[$state];
    }

    /**
     * Rule to update approved order
     *
     * @return void
     */
    public function ruleApproved($cart, $order, $validate_actual)
    {

        if ($validate_actual == true) {
            $status = $this->getNotificationPaymentState('approved');
            return $this->updatePrestashopOrder($cart, $order, $status);
        }

        MPLog::generate('The order can be updated', 'warning');
        return $this->request->response('Internal Server Error', 500);
        
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleProcessing($cart, $order, $status, $validate_actual)
    {

        if ($validate_actual == true) {
            $prestaStatus = $this->getNotificationPaymentState($status);
            return $this->updatePrestashopOrder($cart, $order, $prestaStatus);
        }

        MPLog::generate('The order can be updated', 'warning');
        return $this->request->response('Internal Server Error', 500);
        
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleFailed($cart, $order, $status, $validate_actual)
    {
        if ($validate_actual == true) {
            $prestaStatus = $this->getNotificationPaymentState($status);
            return $this->updatePrestashopOrder($cart, $order, $prestaStatus);
        } 
        
        MPLog::generate('The order can be updated', 'warning');
        return $this->request->response('Internal Server Error', 500);
        
    }

    /**
     * Rule to update chargedback, refunded and inmediation order
     *
     * @return void
     */
    public function ruleDevolution($cart, $order, $status)
    {  
        $prestaStatus = $this->getNotificationPaymentState($status);
        return $this->updatePrestashopOrder($cart, $order, $prestaStatus);        
    }

    /**
     * Rule to update order with payment with possible fraud
     *
     * @return void
     */
    public function ruleFraud($cart, $order, $validate_actual)
    {
        MPLog::generate('The order '. $this->order_id .' have a possible payment fraud', 'error');

        $status_fraud = $this->getNotificationPaymentState('possible_fraud');

        if ($validate_actual == true) {
            MPLog::generate('The order '. $this->order_id .' has been updated to possible fraud status', 'error');
            return $this->updatePrestashopOrder($cart, $order, $status_fraud);
        }
        
        MPLog::generate('The order can be updated');
        return $this->request->response('Internal Server Error', 500);
        
    }

    /**
     * Save payments info on mp_transaction table
     *
     * @param mixed $cart
     * 
     * @return void
     */
    public function saveCreateOrderData($cart)
    {
        $payments_id = is_array($this->payments_data['payments_id'])
            ? implode(',', $this->payments_data['payments_id'])
            : $this->payments_data['payments_id'];

        $payments_type = is_array($this->payments_data['payments_type'])
            ? implode(',', $this->payments_data['payments_type'])
            : $this->payments_data['payments_type'];

        $payments_method = is_array($this->payments_data['payments_method'])
            ? implode(',', $this->payments_data['payments_method'])
            : $this->payments_data['payments_method'];

        $payments_status = is_array($this->payments_data['payments_status'])
            ? implode(',', $this->payments_data['payments_status'])
            : $this->payments_data['payments_status'];

        $payments_amount = is_array($this->payments_data['payments_amount'])
            ? implode(',', $this->payments_data['payments_amount'])
            : $this->payments_data['payments_amount'];

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
     * Create order on Prestashop database
     *
     * @param mixed   $cart                Cart
     * @param boolean $custom_create_order Custom Order created
     * 
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
                $this->request->response('The order has been created', 201);
            }
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been created on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );

            if ($custom_create_order != true) {
                $this->request->response('The order has not been created', 422);
            }
        }
    }

    /**
     * Create order for standard payments without notification
     *
     * @param mixed $cart Cart
     * 
     * @return void
     */
    public function createStandardOrder($cart)
    {
        $this->getOrderId($cart);
        $this->total = $this->getTotal($cart);
        $this->status = 'pending';
        $this->pending += $this->total;
        $this->validateOrderState();

        if ($this->order_id == 0 && $this->amount >= $this->total && $this->status != 'rejected') {
            $this->createOrder($cart, true);
        }
    }

    /**
     * Receive and treat the notification
     *
     * @param mixed $cart Cart
     * 
     * @return void
     */
    public function receiveNotification($cart, $payment)
    {
        $this->verifyWebhook($cart);
        $this->total = $this->getTotal($cart);
        $orderId = $this->getOrderId($cart);

        if ($orderId != 0) {

            $this->verifyPayments($payment);

            $baseOrder = new Order($orderId);
            $orders = Order::getByReference($baseOrder->reference);

            foreach ($orders as $order) {
                $this->order_id = $order->id;                
                $this->updateOrderTransaction($order);
                $this->updateOrder($cart);
            }
        } else {
            $this->createStandardOrder($cart);
        }
    }
}