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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/notification/AbstractNotification.php';

class IpnNotification extends AbstractNotification
{
    public $merchant_order;
    public $merchantOrderId;
    public $isWalletButton;
    public $candidatePaymentIds = [];

    public function __construct($transaction_id, $merchant_order, array $candidatePaymentIds = [])
    {
        parent::__construct($transaction_id);
        $this->candidatePaymentIds = self::normalizeCandidatePaymentIds($candidatePaymentIds);

        $this->merchant_order = $merchant_order;
        $this->merchantOrderId = (string) $transaction_id;
        // Resolve checkout type from the authoritative preference under the cart lock.
        $this->checkout = 'pro';
        $this->isWalletButton = false;
        $this->mp_transaction_amount = isset($merchant_order['total_amount']) ? $merchant_order['total_amount'] : 0;
    }

    /**
     * Receive and treat the notification
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function receiveNotification($cart)
    {
        $this->processCartPayment($cart, true);
    }

    /** @return Order */
    public function createStandardOrder($cart)
    {
        $this->processCartPayment($cart, true);

        return $this->getSynchronousOrder($cart);
    }

    /** Untrusted discovery hints only. Reject malformed/unbounded lists before HTTP. */
    public static function normalizeCandidatePaymentIds($input): array
    {
        if (is_string($input)) {
            if (strlen($input) > 1049) {
                throw new InvalidArgumentException('Too many payment candidates');
            }
            $input = explode(',', $input);
        }
        if (!is_array($input) || count($input) > 50) {
            throw new InvalidArgumentException('Invalid payment candidate list');
        }
        $ids = [];
        foreach ($input as $id) {
            if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $id)) {
                throw new InvalidArgumentException('Invalid payment candidate identity');
            }
            $ids[] = (string) $id;
        }

        return array_values(array_unique($ids));
    }

    protected function loadPaymentSnapshot($cart): array
    {
        $knownIds = $this->knownPaymentIds($cart);
        $this->candidatePaymentIds = self::normalizeCandidatePaymentIds($this->candidatePaymentIds);
        // The cart named lock serializes returns/events. No SQL transaction or
        // row lock has started yet. Never sleep or retry native creation here.
        for ($attempt = 0; $attempt < 3; ++$attempt) {
            try {
                return $this->loadCompletePaymentSnapshot($cart, $knownIds);
            } catch (Throwable $error) {
                if ($attempt === 2) {
                    throw $error;
                }
            }
        }
        throw new RuntimeException('Payment confirmation is pending');
    }

    private function loadCompletePaymentSnapshot($cart, array $knownIds): array
    {
        $ids = array_merge($knownIds, $this->candidatePaymentIds);
        $id = $this->merchantOrderId;
        if (!$id || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $id)) {
            throw new RuntimeException('Invalid merchant order identity');
        }
        $merchantOrder = $this->mercadopago->getMerchantOrder($id, true);
        if (!is_array($merchantOrder) || !isset($merchantOrder['id'], $merchantOrder['external_reference'], $merchantOrder['preference_id'], $merchantOrder['payments'])
            || (string) $merchantOrder['id'] !== (string) $id
            || (string) $merchantOrder['external_reference'] !== (string) $cart->id
            || !isset($merchantOrder['collector']['id']) || !is_array($merchantOrder['payments'])
        ) {
            throw new RuntimeException('Merchant order ownership could not be verified');
        }
        $preference = $this->mercadopago->getPreference($merchantOrder['preference_id']);
        if (!is_object($preference) || !isset($preference->id, $preference->external_reference)
            || (string) $preference->id !== (string) $merchantOrder['preference_id']
            || (string) $preference->external_reference !== (string) $cart->id
            || (isset($preference->collector_id, $merchantOrder['collector']['id'])
                && (string) $preference->collector_id !== (string) $merchantOrder['collector']['id'])
        ) {
            throw new RuntimeException('Checkout preference ownership could not be verified');
        }
        $this->merchant_order = $merchantOrder;
        $this->checkout = isset($preference->metadata->checkout_type) && $preference->metadata->checkout_type === 'wallet_button' ? 'wallet_button' : 'pro';
        $this->isWalletButton = $this->checkout === 'wallet_button';
        $this->preference = $this->getCheckoutPreference();
        $remoteIds = [];
        foreach ($merchantOrder['payments'] as $payment) {
            if (!isset($payment['id'])) {
                throw new RuntimeException('Merchant order payment identity is missing');
            }
            if (!preg_match('/^[1-9][0-9]{0,19}$/D', (string) $payment['id'])) {
                throw new RuntimeException('Invalid merchant order payment identity');
            }
            $remoteIds[] = (string) $payment['id'];
            $ids[] = (string) $payment['id'];
        }
        $discoveredIds = $this->mercadopago->getMerchantOrderPaymentIds((string) $cart->id, (string) $id);
        $ids = array_merge($ids, $discoveredIds);
        $payments = [];
        foreach (array_unique($ids) as $paymentId) {
            $payment = $this->mercadopago->getPaymentStandard($paymentId, true);
            if (!is_array($payment) || !isset($payment['id'], $payment['external_reference'])
                || (string) $payment['id'] !== (string) $paymentId
                || (string) $payment['external_reference'] !== (string) $cart->id
            ) {
                throw new RuntimeException('Merchant order payment ownership could not be verified');
            }
            if (!isset($payment['collector_id'])
                || (string) $payment['collector_id'] !== (string) $merchantOrder['collector']['id']
            ) {
                throw new RuntimeException('Discovered payment collector could not be verified');
            }
            if (empty($payment['order']['id'])) {
                if (in_array((string) $paymentId, $knownIds, true)
                    && !in_array((string) $paymentId, $this->candidatePaymentIds, true)
                    && $this->archiveClosedHistoricalAttempt($cart, $payment)
                ) {
                    continue;
                }
                throw new RuntimeException('Merchant order payment identity is missing');
            }
            if (!preg_match('/^[1-9][0-9]{0,19}$/D', (string) $payment['order']['id'])) {
                throw new RuntimeException('Invalid payment merchant order identity');
            }
            if ((string) $payment['order']['id'] !== (string) $id) {
                // A new preference may reuse an unpaid cart. Never aggregate an old MO;
                // only a confirmed negative attempt without an order/receipt can be ignored.
                if (in_array((string) $paymentId, $knownIds, true)
                    && !in_array((string) $paymentId, $this->candidatePaymentIds, true)
                    && !in_array((string) $paymentId, $discoveredIds, true)
                    && !$this->findOrderId($cart) && !in_array((string) $paymentId, $remoteIds, true)
                    && !isset($this->recordedPaymentIds[(string) $paymentId])
                    && in_array($payment['status'] ?? null, ['rejected', 'cancelled'], true)
                ) {
                    require_once __DIR__ . '/CartPaymentInitiation.php';
                    $history = (new CartPaymentInitiation($this->mercadopago))->hasApprovalHistory($cart, (string) $paymentId);
                    if (PaymentClosureProof::isClosed($payment, $history, in_array($payment['currency_id'] ?? null, ['CLP', 'COP'], true) ? 0 : 2)) {
                        $this->archiveNegativeAttempt($payment);
                        continue;
                    }
                }
                if (in_array((string) $paymentId, $knownIds, true)
                    && !in_array((string) $paymentId, $this->candidatePaymentIds, true)
                    && !$this->findOrderId($cart) && ($payment['status'] ?? null) === 'refunded'
                ) {
                    if ($this->archiveClosedHistoricalAttempt($cart, $payment)) {
                        continue;
                    }
                }
                throw new RuntimeException('Payment belongs to another merchant order');
            }
            if (isset($payment['preference_id']) && (string) $payment['preference_id'] !== (string) $merchantOrder['preference_id']) {
                throw new RuntimeException('Current merchant order payment preference could not be verified');
            }
            $payments[] = $payment;
        }
        $precision = in_array(Configuration::get('MERCADOPAGO_SITE_ID'), ['MCO', 'MLC'], true) ? 0 : 2;

        $snapshot = PaymentReconciliationSnapshot::fromPayments($payments, $precision);
        if (!$snapshot['attempts']) {
            throw new RuntimeException('Payment discovery is empty; confirmation pending');
        }
        // Existing references must reach their native state/reversal/fraud guards.
        // Their persisted totals are already used by processCartPayment.
        if ($this->loadOrdersForCart($cart, false)) {
            return $snapshot;
        }
        $total = PaymentReconciliationSnapshot::units($this->getTotal($cart, $this->checkout), $precision);
        $coverage = PaymentReconciliationSnapshot::units($snapshot['approved_amount'], $precision)
            + PaymentReconciliationSnapshot::units($snapshot['pending_amount'], $precision);
        $terminal = 0;
        foreach ($snapshot['attempts'] as $payment) {
            if (in_array($payment['status'], ['rejected', 'cancelled'], true)) {
                $terminal += PaymentReconciliationSnapshot::units($payment['nominal_amount'], $precision);
            }
        }
        // A complete terminal pair is final, not an endlessly retried approval.
        // A positive/pending shortfall without that evidence must not be ACKed.
        $terminalComplete = $snapshot['pending_amount'] === PaymentReconciliationSnapshot::decimal(0, $precision)
            && $coverage + $terminal === $total;
        if (($coverage > 0 || in_array($snapshot['effective_status'], ['approved', 'pending', 'in_process', 'authorized'], true))
            && $coverage < $total && !$terminalComplete
        ) {
            throw new RuntimeException('Payment discovery is incomplete; confirmation pending');
        }

        return $snapshot;
    }

    /**
     * Get Checkout Preference
     *
     * @return mixed
     */
    public function getCheckoutPreference()
    {
        if ($this->isWalletButton) {
            return new WalletButtonPreference();
        }

        return new StandardPreference();
    }

    /**
     * Get Preference
     *
     * @return mixed
     */
    public function getCheckoutType()
    {
        $preference = $this->mercadopago->getPreference($this->merchant_order['preference_id']);

        $checkout = 'pro';
        $checkoutType = isset($preference->metadata->checkout_type) ? $preference->metadata->checkout_type : false;

        if ($checkoutType && $checkoutType === 'wallet_button') {
            $checkout = 'wallet_button';
        }

        return $checkout;
    }

    /**
     * Verify if order exists then get order_id
     *
     * @param mixed $cart
     *
     * @return int
     */
    public function getOrderId($cart)
    {
        $orderId = Order::getIdByCartId($cart->id);
        $this->order_id = $orderId;

        return $orderId;
    }

    /**
     * Verify merchant order payments
     *
     * @param mixed $payments
     *
     * @return void
     */
    public function verifyPayments($payments)
    {
        $verified = [];
        foreach ($payments as $payment) {
            $info = $this->mercadopago->getPaymentStandard($payment['id'], true);
            if (!is_array($info) || !isset($info['id']) || (string) $info['id'] !== (string) $payment['id']) {
                throw new RuntimeException('Could not verify merchant order payment identity');
            }
            $verified[] = $info;
        }
        $precision = in_array(Configuration::get('MERCADOPAGO_SITE_ID'), ['MCO', 'MLC'], true) ? 0 : 2;
        $this->applyPaymentSnapshot(PaymentReconciliationSnapshot::fromPayments($verified, $precision));
    }
}
