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

class WebhookNotification extends AbstractNotification
{
    public $payment;
    protected $notifiedPaymentId;
    private $allowHistoricalAck = false;

    public function __construct($transaction_id, $payment)
    {
        parent::__construct($transaction_id);

        $this->payment = $payment;
        $this->notifiedPaymentId = (string) $transaction_id;
        $this->checkout = $payment['metadata']['checkout_type'];
        $this->mp_transaction_amount = $payment['transaction_amount'];
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
        $this->allowHistoricalAck = true;
        $this->processCartPayment($cart, false);
    }

    /** @return Order */
    public function createCustomOrder($cart)
    {
        $this->allowHistoricalAck = false;
        $this->processCartPayment($cart, true);

        return $this->getSynchronousOrder($cart);
    }

    protected function loadPaymentSnapshot($cart): array
    {
        require_once __DIR__ . '/CartPaymentInitiation.php';
        $policy = new CartPaymentInitiation($this->mercadopago);
        $ids = $this->knownPaymentIds($cart, true);
        $ids[] = $this->notifiedPaymentId;
        $payments = [];
        foreach (array_unique($ids) as $id) {
            $payment = $this->mercadopago->getPaymentStandard($id, true);
            if (!is_array($payment) || !isset($payment['id'], $payment['external_reference'])
                || (string) $payment['id'] !== (string) $id
                || (string) $payment['external_reference'] !== (string) $cart->id
            ) {
                throw new RuntimeException('Custom payment ownership could not be verified');
            }
            $scope = $policy->webhookPaymentScope($cart, $payment);
            if ($scope === 'retired') {
                if (!$policy->canArchiveClosedPayment($cart, $payment)) {
                    throw new RuntimeException('A retired payment generation has unresolved funds');
                }
                $precision = in_array($payment['currency_id'], ['CLP', 'COP'], true) ? 0 : 2;
                $retired = PaymentReconciliationSnapshot::fromPayments([$payment], $precision);
                if ((string) $id === $this->notifiedPaymentId) {
                    if (!$this->allowHistoricalAck) {
                        throw new RuntimeException('A retired payment cannot confirm current checkout creation');
                    }
                    $this->historicalPaymentEvent = true;

                    return $retired;
                }
                $this->auditOnlyAttempts = array_replace($this->auditOnlyAttempts, $retired['attempts']);
                continue;
            }
            if (!in_array($payment['metadata']['checkout_type'] ?? null, ['credit_card', 'pix', 'ticket', 'pse'], true)) {
                if ((string) $id !== (string) $this->notifiedPaymentId
                    && !isset($this->recordedPaymentIds[(string) $id])
                    && in_array($payment['status'] ?? null, ['rejected', 'cancelled'], true)
                ) {
                    $this->archiveNegativeAttempt($payment);
                    continue;
                }
                throw new RuntimeException('Payment belongs to another checkout integration');
            }
            $payments[] = $payment;
            if ((string) $id === (string) $this->notifiedPaymentId) {
                $this->payment = $payment;
                $this->checkout = $payment['metadata']['checkout_type'];
            }
        }
        $precision = in_array(Configuration::get('MERCADOPAGO_SITE_ID'), ['MCO', 'MLC'], true) ? 0 : 2;

        return PaymentReconciliationSnapshot::fromPayments($payments, $precision);
    }

    /**
     * Verify custom payments
     *
     * @return void
     */
    public function verifyCustomPayment()
    {
        $precision = in_array(Configuration::get('MERCADOPAGO_SITE_ID'), ['MCO', 'MLC'], true) ? 0 : 2;
        $this->applyPaymentSnapshot(PaymentReconciliationSnapshot::fromPayments([$this->payment], $precision));
    }
}
