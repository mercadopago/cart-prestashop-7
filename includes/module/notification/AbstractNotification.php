<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
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
    public $approved;
    public $pending;
    public $order_id;
    public $mercadopago;
    public $order_state;
    public $payments_data;
    public $transaction_id;
    public $mp_transaction;
    public $ps_order_state;
    public $ps_order_state_lang;
    public $order_state_lang;
    public $customer_secure_key;
    public $mpuseful;
    public $checkout;
    public $mp_transaction_amount;
    public $responseMessage;
    public $responseCode;

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
     * @param mixed $cart
     *
     * @return void
     */
    public function verifyWebhook($cart)
    {
        $this->mp_transaction->where('cart_id', '=', $cart->id)->update(
            [
                'received_webhook' => true,
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
     *
     * @return void
     */
    public function updateOrderTransaction($order)
    {
        $order_payments = $order->getOrderPaymentCollection();

        if (!empty($this->payments_data['payments_id']) && is_array($this->payments_data['payments_id']) && count($this->payments_data['payments_id']) > 1) {
            $db = Db::getInstance();
            if ($db->execute('START TRANSACTION') !== true) {
                MPLog::generate('Error on update order transaction: could not start database transaction', 'error');
                throw new Exception('Could not start database transaction');
            }

            try {
                foreach ($order_payments as $payment) {
                    if ($payment->delete() !== true) {
                        throw new Exception('Could not delete existing OrderPayment ' . $payment->transaction_id);
                    }
                }

                foreach ($this->payments_data['payments_id'] as $index => $payment_id) {
                    $new_payment = new OrderPayment();
                    $new_payment->order_reference = $order->reference;
                    $new_payment->id_currency = $order->id_currency;
                    $new_payment->amount = $this->payments_data['payments_amount'][$index];
                    $new_payment->payment_method = 'Mercado Pago';
                    $new_payment->transaction_id = $payment_id;
                    if ($new_payment->add() !== true) {
                        throw new Exception('Could not persist OrderPayment ' . $payment_id);
                    }
                }

                if ($db->execute('COMMIT') !== true) {
                    throw new Exception('Could not commit OrderPayment reconciliation');
                }
            } catch (Exception $e) {
                if ($db->execute('ROLLBACK') !== true) {
                    MPLog::generate('Error on update order transaction: database rollback failed', 'error');
                }
                MPLog::generate('Error on update order transaction: ' . $e->getMessage(), 'error');
                throw $e;
            }
        } else {
            try {
                $order_payments[0]->amount = $this->approved;

                if (!empty($this->transaction_id)) {
                    $order_payments[0]->transaction_id = $this->transaction_id;
                }

                if ($order_payments[0]->update() !== true) {
                    throw new Exception('Could not update existing OrderPayment');
                }
            } catch (Exception $e) {
                MPLog::generate('Error on update order transaction: ' . $e->getMessage(), 'error');
                throw $e;
            }
        }
    }

    /**
     * Create order on Prestashop database
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function createOrder($cart)
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
                'Mercado Pago',
                null,
                [],
                (int) $cart->id_currency,
                false
            );

            $this->order_id = Order::getIdByCartId($cart->id);
            $order = new Order($this->order_id);

            $payments = $order->getOrderPaymentCollection();
            if ($payments->count() > 0) {
                /** @var OrderPayment $payment */
                $payment = $payments[0];
                $payment->transaction_id = $this->transaction_id;
                $payment->update();
            }

            $this->saveCreateOrderData($cart);

            MPLog::generate('Order created successfully on cart id ' . $cart->id);

            $this->setNotificationResponse('The order has been created', 201);
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been created on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );

            $this->setNotificationResponse('The order has not been created', 422);
        }
    }

    /**
     * Validate status to update order
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function updateOrder($cart)
    {
        $this->updateOrders($cart, [(object) ['id' => $this->order_id]]);
    }

    /**
     * Update every order in a split reference while holding one per-cart lock.
     *
     * @param mixed $cart
     * @param iterable $orders
     *
     * @return void
     */
    public function updateOrders($cart, $orders)
    {
        $installation = hash('sha256', _DB_NAME_ . ':' . _DB_PREFIX_);
        $lockName = 'mp:notification:' . substr($installation, 0, 24) . ':' . (int) $cart->id;
        $acquired = (int) Db::getInstance()->getValue(
            'SELECT GET_LOCK(\'' . pSQL($lockName) . '\', 5)',
            false
        );

        if ($acquired !== 1) {
            MPLog::generate('Could not acquire notification processing lock for cart id ' . $cart->id, 'error');
            $this->setNotificationResponse('Could not acquire notification processing lock', 409);

            return;
        }

        try {
            foreach ($orders as $order) {
                $this->order_id = $order->id;
                $this->updateOrderLocked($cart);
            }
        } finally {
            Db::getInstance()->getValue('SELECT RELEASE_LOCK(\'' . pSQL($lockName) . '\')', false);
        }
    }

    /**
     * Body of updateOrder()/updateOrders(), executed while the per-cart notification lock is
     * held. Concurrent notifications for the same cart — e.g. two idempotent
     * "same state" callbacks — must not interleave their read of the current
     * order state with their OrderPayment mutations, or one can delete
     * payment rows the other just recreated.
     *
     * @param mixed $cart
     *
     * @return void
     */
    private function updateOrderLocked($cart)
    {
        $order = new Order($this->order_id);
        $actual_status = (int) $order->getCurrentState();
        $validate_actual = $this->validateActualStatus($actual_status, $order);

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
                    $this->ruleDevolution($cart, $order, $status_refunded, $actual_status, $validate_actual);
                    break;

                case $status_charged:
                    MPLog::generate('Entered the CHARGED_BACK rule');
                    $this->ruleDevolution($cart, $order, $status_charged, $actual_status, $validate_actual);
                    break;

                case $status_mediation:
                    MPLog::generate('Entered the MEDIATION rule');
                    $this->ruleDevolution($cart, $order, $status_mediation, $actual_status, $validate_actual);
                    break;

                default:
                    MPLog::generate('Order state not recognized by Mercado Pago', 'warning');
                    $this->setNotificationResponse('Order state not recognized by Mercado Pago', 200);

                    break;
            }
        } else {
            MPLog::generate('Order does not exist', 'error');
            $this->setNotificationResponse('Order does not exist', 404);
        }
    }

    /**
     * Rule to update approved order
     *
     * @return void
     */
    public function ruleApproved($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($this->isApprovedEquivalent($actual_status)) {
            if (!$this->reconcileSameStatePayment($cart, $order, $validate_actual)) {
                $this->setNotificationResponse('Could not reconcile payment for the current order status', 422);

                return;
            }
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->total > $this->approved) {
            $this->ruleFraud($cart, $order, $actual_status, $validate_actual);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleProcessing($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($actual_status == $status) {
            if (!$this->reconcileSameStatePayment($cart, $order, $validate_actual)) {
                $this->setNotificationResponse('Could not reconcile payment for the current order status', 422);

                return;
            }
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->isApprovedEquivalent($actual_status)) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->setNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update pending, in_process and authorized order
     *
     * @return void
     */
    public function ruleFailed($cart, $order, $status, $actual_status, $validate_actual)
    {
        if ($this->isSameOutcome($actual_status, $status)) {
            if (!$this->reconcileSameStatePayment($cart, $order, $validate_actual)) {
                $this->setNotificationResponse('Could not reconcile payment for the current order status', 422);

                return;
            }
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($this->isApprovedEquivalent($actual_status)) {
            MPLog::generate('It is only possible to mediate, chargeback or refund an approved payment', 'warning');
            $this->setNotificationResponse('It is not possible to update this approved payment', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update chargedback, refunded and inmediation order
     *
     * @return void
     */
    public function ruleDevolution($cart, $order, $status, $actual_status, $validate_actual = null)
    {
        if ($validate_actual === null) {
            $validate_actual = $this->validateActualStatus($actual_status, $order);
        }

        // Financial reversals remain valid after fulfillment, but only for an owned order.
        if (in_array((int) $actual_status, array_map('intval', array_filter([
            Configuration::get('PS_OS_SHIPPING'),
            Configuration::get('PS_OS_DELIVERED'),
            Configuration::get('PS_OS_REFUND'),
        ])), true) && $this->orderBelongsToMercadoPago($order)) {
            $validate_actual = true;
        }

        if ($actual_status == $status) {
            if (!$this->reconcileSameStatePayment($cart, $order, $validate_actual)) {
                $this->setNotificationResponse('Could not reconcile payment for the current order status', 422);

                return;
            }
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($validate_actual == true) {
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Rule to update order with payment with possible fraud
     *
     * @return void
     */
    public function ruleFraud($cart, $order, $actual_status, $validate_actual)
    {
        MPLog::generate('The order ' . $this->order_id . ' have a possible payment fraud', 'error');

        $status_fraud = $this->getNotificationPaymentState('possible_fraud');
        $this->order_state = $status_fraud;

        if ($actual_status == $status_fraud) {
            if (!$this->reconcileSameStatePayment($cart, $order, $validate_actual)) {
                $this->setNotificationResponse('Could not reconcile payment for the current order status', 422);

                return;
            }
            MPLog::generate('Order status is the same', 'warning');
            $this->setNotificationResponse('Order status is the same', 200);
        } elseif ($validate_actual == true) {
            MPLog::generate('The order ' . $this->order_id . ' has been updated to possible fraud status', 'error');
            $this->updatePrestashopOrder($cart, $order);
        } else {
            MPLog::generate('The order has been updated to a status that does not belong to Mercado Pago', 'warning');
            $this->setNotificationResponse('The order has been updated to a status that does not belong to MP', 200);
        }
    }

    /**
     * Reconcile payment records for an idempotent notification without creating
     * another order-history transition. Ownership/state validation must succeed
     * before any OrderPayment or transaction data is mutated.
     *
     * updateOrderTransaction() now rethrows on failure (e.g. a rolled-back multi-payment
     * reconciliation) instead of swallowing the error, so callers must check the return
     * value and respond accordingly instead of unconditionally reporting success — mp_transaction
     * must not be updated via saveUpdateOrderData() when the OrderPayment reconciliation failed.
     *
     * @return bool true if reconciled (or skipped because $validate_actual was false), false on failure
     */
    private function reconcileSameStatePayment($cart, $order, $validate_actual): bool
    {
        if ($validate_actual != true) {
            return true;
        }

        try {
            $this->updateOrderTransaction($order);
            $this->saveUpdateOrderData($cart);

            return true;
        } catch (Exception $e) {
            MPLog::generate(
                'Error reconciling same-state payment for cart ' . $cart->id . ': ' . $e->getMessage(),
                'error'
            );

            return false;
        }
    }

    /**
     * Update order on Prestashop database
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function updatePrestashopOrder($cart, $order)
    {
        try {
            $this->generateLogs();

            $targetState = $this->getTargetOrderState($this->order_state);
            $this->updateOrderTransaction($order);
            $order->setCurrentState($targetState);
            $this->saveUpdateOrderData($cart);

            MPLog::generate('Updated order ' . $this->order_id . ' for the status of ' . $targetState);
            $this->setNotificationResponse('The order has been updated', 201);
        } catch (Exception $e) {
            MPLog::generate(
                'The order has not been updated on cart id ' . $cart->id . ' - ' . $e->getMessage(),
                'error'
            );
            $this->setNotificationResponse('The order has not been updated', 422);
        }
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
        $payments_id = $this->verifyValue('payments_id');

        $payments_type = $this->verifyValue('payments_type');

        $payments_method = $this->verifyValue('payments_method');

        $payments_status = $this->verifyValue('payments_status');

        $payments_amount = $this->verifyValue('payments_amount');

        $dataToCreate = [
            'order_id' => $this->order_id,
            'notification_url' => $_SERVER['REQUEST_URI'],
            'merchant_order_id' => $this->transaction_id,
            'received_webhook' => true,
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
     * @param mixed $cart
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

        $updated = $this->mp_transaction->where('cart_id', '=', $cart->id)->update(
            [
                'payment_id' => pSQL(is_array($payments_id) ? implode(',', $payments_id) : $payments_id),
                'payment_type' => pSQL(is_array($payments_type) ? implode(',', $payments_type) : $payments_type),
                'payment_method' => pSQL(is_array($payments_method) ? implode(',', $payments_method) : $payments_method),
                'payment_status' => pSQL(is_array($payments_status) ? implode(',', $payments_status) : $payments_status),
                'payment_amount' => pSQL(is_array($payments_amount) ? implode(',', $payments_amount) : $payments_amount),
            ]
        );

        if ($updated === false) {
            throw new RuntimeException('Could not persist Mercado Pago transaction data');
        }
    }

    /**
     * @param $state
     *
     * @return mixed
     */
    public function getNotificationPaymentState($state)
    {
        $payment_states = [
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
        ];

        return Configuration::get($payment_states[$state]);
    }

    /**
     * @param int $actual
     *
     * @return bool
     */
    public function validateActualStatus($actual, $order = null)
    {
        $result = $this->ps_order_state->where('id_order_state', '=', (int) $actual)->get();

        if ($result['module_name'] === 'mercadopago'
            || $this->getBackOrderStatus($actual)
            || (in_array((int) $actual, $this->getNativeCompatibleStates(), true)
                && $this->orderBelongsToMercadoPago($order))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Native PrestaShop order states accepted as a valid starting point for a Mercado Pago
     * notification to transition an order from, even though they don't belong to this module
     * (PPSP-1575). Before this, any order moved into a native state — manually by a seller, or
     * by another module — permanently stopped receiving MP synchronization: validateActualStatus()
     * rejected every state except this module's own 10, silently dropping the notification with a
     * non-retryable 200 (see traps.md).
     *
     * Deliberately limited to payment-adjacent states, not shipping/delivery/refund
     * (PS_OS_SHIPPING/PS_OS_DELIVERED/PS_OS_REFUND) — a late or duplicate webhook must not be
     * able to walk an order back to a payment state after it has already moved past payment
     * resolution into fulfillment.
     *
     * Being in one of these states is necessary but not sufficient — orderBelongsToMercadoPago()
     * must also confirm this order, or a split-order sibling with the same PrestaShop reference,
     * was created by this module before a native-compatible state is trusted.
     *
     * @return int[]
     */
    public function getNativeCompatibleStates()
    {
        return array_map('intval', array_filter([
            Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_WS_PAYMENT'),
            Configuration::get('PS_OS_ERROR'),
            Configuration::get('PS_OS_CANCELED'),
        ]));
    }

    /**
     * Whether this order belongs to Mercado Pago. The exact `mp_transactions.order_id`, written
     * by saveCreateOrderData(), is preferred. For PrestaShop split orders, a registered sibling
     * with the same reference also proves ownership because the table stores only one order ID
     * per cart (PPSP-1575).
     *
     * A native-compatible state (getNativeCompatibleStates()) alone can't distinguish "this
     * module created the order and a seller later moved it to a native state" (safe — Hyago's
     * repro) from "a DIFFERENT payment module created an unrelated order directly in that same
     * native state for the same cart" (unsafe — confirmed by reproduction: a stale/duplicate MP
     * notification could otherwise flip an order paid through another method to "Transaction
     * Declined"). The exact ID or the registered order's shared reference proves ownership;
     * cart identity alone does not.
     *
     * @return bool
     */
    public function orderBelongsToMercadoPago($order = null)
    {
        if (empty($this->order_id)) {
            return false;
        }

        if ($this->hasMercadoPagoTransactionForOrder($this->order_id)) {
            return true;
        }

        if ($order === null) {
            $order = new Order((int) $this->order_id);
        }

        if (empty($order->reference)) {
            return false;
        }

        $orders = Order::getByReference($order->reference);
        foreach ($orders as $relatedOrder) {
            if ($this->hasMercadoPagoTransactionForOrder($relatedOrder->id)) {
                MPLog::generate(
                    'Order ' . (int) $this->order_id . ' belongs to Mercado Pago through split order '
                    . (int) $relatedOrder->id
                );

                return true;
            }
        }

        return false;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    private function hasMercadoPagoTransactionForOrder($orderId)
    {
        $result = $this->mp_transaction->where('order_id', '=', (int) $orderId)->get();

        return !empty($result)
            && isset($result['order_id'])
            && (int) $result['order_id'] === (int) $orderId;
    }

    /**
     * Maps this module's own "rejected"/"cancelled" MP states to the equivalent native
     * PrestaShop state for later notification-driven transitions (PPSP-1575). The creation
     * entrypoints deliberately do not create orders for negative payment outcomes, so this
     * mapping is only used by updatePrestashopOrder().
     *
     * Every other outcome (approved, pending, in_process, authorized, refunded, charged_back,
     * in_mediation, possible_fraud) is unaffected and keeps this module's own state/label.
     *
     * @param mixed $moduleState one of this module's own getNotificationPaymentState() values
     *
     * @return int
     */
    public function getTargetOrderState($moduleState)
    {
        if ($moduleState == $this->getNotificationPaymentState('rejected')) {
            return (int) Configuration::get('PS_OS_ERROR');
        }

        if ($moduleState == $this->getNotificationPaymentState('cancelled')) {
            return (int) Configuration::get('PS_OS_CANCELED');
        }

        return (int) $moduleState;
    }

    /**
     * Whether $actual (the order's real current state) already represents the same outcome as
     * $moduleState (one of this module's own MP states), accounting for getTargetOrderState()'s
     * substitution — so a declined/cancelled order sitting on the native ID still short-circuits
     * as "order status is the same" (200, no-op) instead of re-running updatePrestashopOrder()
     * on every duplicate/retried notification (PPSP-1575).
     *
     * @param int $actual
     * @param mixed $moduleState
     *
     * @return bool
     */
    public function isSameOutcome($actual, $moduleState)
    {
        return (int) $actual === (int) $moduleState || (int) $actual === $this->getTargetOrderState($moduleState);
    }

    /**
     * Whether $actual represents an "approved" order for the "can't downgrade an approved
     * payment" guard in ruleApproved()/ruleProcessing()/ruleFailed() — true both for this
     * module's own approved state and for the native PS_OS_PAYMENT/PS_OS_WS_PAYMENT states that
     * validateActualStatus() now also treats as ours (PPSP-1575).
     *
     * Confirmed by reproduction: without this, an MP-owned order sitting in a native "paid"
     * state (getNativeCompatibleStates()) — e.g. moved there manually by a seller — could be
     * silently downgraded back to pending/in_process/rejected by a later notification, because
     * the guard only recognized this module's own approved ID. That's the same class of bug
     * this ticket closed in the opposite direction (a native state blocking sync entirely).
     *
     * @param int $actual
     *
     * @return bool
     */
    public function isApprovedEquivalent($actual)
    {
        $actual = (int) $actual;

        if ($actual === (int) $this->getNotificationPaymentState('approved')) {
            return true;
        }

        return in_array($actual, [
            (int) Configuration::get('PS_OS_PAYMENT'),
            (int) Configuration::get('PS_OS_WS_PAYMENT'),
        ], true);
    }

    /**
     * @param int $actual
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
     * Records the outcome of processing this notification, without sending anything yet.
     *
     * receiveNotification() can update more than one order under the same reference (a cart
     * split across packages/warehouses/carriers loops here — see IpnNotification/WebhookNotification
     * ::receiveNotification()), and each iteration may reach a different branch of updateOrder().
     * Recording instead of sending immediately lets every order in that loop finish processing;
     * the caller (the notification front controller) decides when all processing for this request
     * is done and sends exactly one response via getNotificationResponse() (PPSP-1573).
     *
     * Within that loop, a retryable failure (code >= 400) always wins over a later success
     * (code < 400): once one order in the loop fails, a different order finishing successfully
     * afterwards must not mask it with a 2xx — Mercado Pago would stop retrying while the failed
     * order stays silently out of sync. Among calls on the same side of that line (two successes,
     * or two failures), the last call still wins.
     *
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    public function setNotificationResponse($message, $code)
    {
        if ($this->responseCode !== null && $this->responseCode >= 400 && $code < 400) {
            return;
        }

        $this->responseMessage = $message;
        $this->responseCode = $code;
    }

    /**
     * Sends the response for this notification request and terminates immediately. Must be
     * called exactly once, by the front controller, after all processing (including any
     * multi-order loop) has finished — never from within the per-order processing methods
     * themselves; use setNotificationResponse() there instead.
     *
     * Sets the status code before echoing the body, then terminates the request immediately.
     * Both matter and are independently verified (PPSP-1573):
     *
     * - `http_response_code()` must run before any output is produced. With `output_buffering`
     *   Off, the first `echo` flushes headers immediately using whatever status is set at that
     *   instant; calling `http_response_code()` after the `echo` silently loses the intended code
     *   and the client always sees a default 200, regardless of $code.
     * - `exit` must run after the response is sent. Without it, PrestaShop's front controller
     *   lifecycle continues past initContent() into display(), which tries to render a Smarty
     *   template that was never set for this controller and fatals. Whether that turns into a
     *   visible HTTP 500 depends on the host's output buffering (masked when it's already flushed
     *   the response), but the fatal fires either way and can start surfacing after any hosting/PHP
     *   change with no code change on either side — `exit` removes the dependency entirely.
     *
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    public static function getNotificationResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = self::buildNotificationResponse($message, $code);

        http_response_code($response['status']);
        echo $response['body'];

        exit;
    }

    /**
     * Builds the HTTP status/body pair for a notification response.
     *
     * Keeping the payload construction separate from transport makes the response
     * contract verifiable without executing the terminal exit used in production.
     *
     * @param string $message
     * @param int $code
     *
     * @return array<string, int|string>
     */
    public static function buildNotificationResponse($message, $code)
    {
        return [
            'status' => $code,
            'body' => json_encode([
                'code' => $code,
                'message' => $message,
                'version' => MP_VERSION,
            ]),
        ];
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
     * @return void
     */
    public function generateLogs()
    {
        $logs = [
            'transaction_id' => $this->transaction_id,
            'cart_total' => $this->total,
            'order_id' => $this->order_id,
            'payment_status' => $this->status,
            'approved_order_state' => $this->approved,
            'pending_order_state' => $this->pending,
            'order_state' => $this->order_state,
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate('Order id ' . $this->order_id . ' notification logs: ' . $encodedLogs);
    }

    /**
     * Verify value
     *
     * @param string $key
     *
     * @return string|null
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
