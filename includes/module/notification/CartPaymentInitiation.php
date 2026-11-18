<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/PaymentInitiationBlocked.php';
require_once __DIR__ . '/../../PaymentIssuanceRejected.php';
require_once __DIR__ . '/CartPaymentContext.php';
require_once __DIR__ . '/CartPaymentLock.php';
require_once __DIR__ . '/PaymentClosureProof.php';
require_once __DIR__ . '/IpnNotification.php';
require_once __DIR__ . '/../model/MPTransaction.php';
if (!class_exists('MPLog')) {
    require_once __DIR__ . '/../../MPLog.php';
}

/** Durable issuance boundary. It neither creates native orders nor moves money. */
class CartPaymentInitiation
{
    private $api;
    private $transaction;
    private $sqlActive = false;

    public function __construct($api)
    {
        $this->api = $api;
        $this->transaction = new MPTransaction();
    }

    public function run($cart, string $kind, callable $build, ?callable $cleanup = null, ?callable $ruleId = null, ?callable $prepare = null)
    {
        $cartId = (int) $cart->id;
        $db = Db::getInstance();
        $lock = CartPaymentLock::name($cartId);
        $acquired = false;
        try {
            $acquired = $cartId > 0 && in_array($kind, ['preference', 'payment'], true)
                && (int) $db->getValue('SELECT GET_LOCK(\'' . pSQL($lock) . '\', 5)', false) === 1;
        } catch (Throwable $lockError) {
            // A failed lock query is also a held outcome, never permission to issue.
        }
        if (!$acquired) {
            $this->logHold($cartId, 'lock acquisition');
            throw new PaymentInitiationBlocked('Payment confirmation is pending');
        }
        $postStarted = false;
        $generation = null;
        $initialRuleId = 0;
        $preparedRuleId = 0;
        $response = null;
        $failure = null;
        $released = false;
        try {
            $this->assertStorage();
            $context = new CartPaymentContext();
            $before = $context->capture($cartId, false);
            $this->assertCartContext($cart, $before['cart']);
            OrderPaymentReconciler::assertNativeCreationSupported($cart);
            $registered = $this->transaction->findReconciliationForCart($cartId);
            $journal = $this->journal($registered, $cartId);
            $seller = $this->api->getAuthenticatedCollectorId();
            $currency = (new Currency((int) $cart->id_currency))->iso_code;
            $closedIds = $this->verifyPriorAttempts($cart, $registered, $journal, $seller, $currency, $before);
            $journal['closed_payment_ids'] = $closedIds;
            $generation = bin2hex(random_bytes(16));
            $entry = ['generation' => $generation, 'state' => 'reserved', 'kind' => $kind,
                'collector_id' => $seller, 'cart_id' => $cartId, 'customer_id' => (int) $cart->id_customer,
                'shop_id' => (int) $cart->id_shop, 'currency' => $currency,
                'preference_id' => null, 'payment_id' => null, 'cart_rule_id' => 0];
            $this->begin();
            if ($before['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
                throw new PaymentInitiationBlocked('Payment context changed during verification');
            }
            $this->retireClosedDiscounts($cart, $journal);
            if ($journal['current'] !== null) {
                $journal['history'][] = $journal['current'];
            }
            $journal['current'] = $entry;
            $this->ensureRegistration($cart);
            $this->persist($cartId, $journal);
            // Bind the builder's cached products to the context still protected by row locks.
            $prepared = $context->capture($cartId, true);
            $this->commit();
            // Even payload-building side effects start only after a durable reservation.
            if ($prepare !== null) {
                $initialRuleId = $ruleId !== null ? (int) $ruleId() : 0;
                $this->begin();
                if ($prepared['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
                    throw new PaymentInitiationBlocked('Payment context changed before checkout preparation');
                }
                $prepare();
                $preparedRuleId = $ruleId !== null ? (int) $ruleId() : 0;
                $journal['current']['cart_rule_id'] = $preparedRuleId;
                $this->persist($cartId, $journal);
                $prepared = $context->capture($cartId, true);
                $this->commit();
            }
            if ($prepared['fingerprint'] !== $context->capture($cartId, false)['fingerprint']) {
                throw new PaymentInitiationBlocked('Payment context changed before building the request');
            }
            $this->assertCartContext($cart, $prepared['cart']);
            $payload = $build();
            if (!is_array($payload) || (string) ($payload['external_reference'] ?? '') !== (string) $cartId) {
                throw new PaymentInitiationBlocked('Payment request ownership is invalid');
            }
            // Builder GETs run outside SQL. Revalidate their price/discount context before POST.
            $this->begin();
            if ($prepared['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
                throw new PaymentInitiationBlocked('Payment context changed while building the request');
            }
            OrderPaymentReconciler::assertNativeCreationSupported($cart);
            $this->commit();
            $postStarted = true;
            $response = $kind === 'preference' ? $this->api->createPreference($payload) : $this->api->createPayment($payload);
            $rejected = $response instanceof PaymentIssuanceRejected;
            if (!$rejected && (!is_array($response) || !isset($response['id']))) {
                throw new PaymentInitiationBlocked('Payment issuance outcome is unknown');
            }
            $resourceId = $rejected ? null : (string) $response['id'];
            if (!$rejected && (($kind === 'payment' && !preg_match('/^[1-9][0-9]{0,19}$/D', $resourceId))
                || ($kind === 'preference' && !preg_match('/^[a-zA-Z0-9-]{1,100}$/D', $resourceId)))
            ) {
                throw new PaymentInitiationBlocked('Payment issuance identity is unknown');
            }
            $this->begin();
            if ($prepared['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
                throw new PaymentInitiationBlocked('Payment context changed after issuance');
            }
            $journal['current']['state'] = $rejected ? 'not_created' : 'issued';
            $journal['current'][$kind === 'preference' ? 'preference_id' : 'payment_id'] = $resourceId;
            $this->persist($cartId, $journal);
            $this->commit();
            if ($rejected) {
                $response = $response->getMessage();
            }
        } catch (Throwable $error) {
            $rollbackConfirmed = $this->rollbackConfirmed($cartId);
            if (!$postStarted && $cleanup !== null && $preparedRuleId > 0 && $preparedRuleId !== $initialRuleId) {
                if ($rollbackConfirmed) {
                    try {
                        $cleanup($preparedRuleId);
                    } catch (Throwable $cleanupError) {
                        $this->logHold($cartId, 'discount cleanup failure', 'error');
                    }
                } else {
                    $this->logHold($cartId, 'discount cleanup deferred after rollback failure', 'error');
                }
            }
            if (!$postStarted && $generation !== null && $rollbackConfirmed) {
                try {
                    $this->closeUnpostedGeneration($cartId, $generation);
                } catch (Throwable $recoveryError) {
                    $this->rollbackConfirmed($cartId);
                    $this->logHold($cartId, 'pre-POST journal recovery failure', 'error');
                }
            }
            $this->logHold($cartId, 'verification or issuance');
            $failure = new PaymentInitiationBlocked('Payment confirmation is pending', 0, $error);
        } finally {
            try {
                $released = (int) $db->getValue('SELECT RELEASE_LOCK(\'' . pSQL($lock) . '\')', false) === 1;
            } catch (Throwable $releaseError) {
                $released = false;
            }
        }
        if (!$released) {
            $this->logHold($cartId, 'lock release unconfirmed');
            throw new PaymentInitiationBlocked('Payment initiation lock release is unconfirmed');
        }
        if ($failure !== null) {
            throw $failure;
        }

        return $response;
    }

    private function logHold(int $cartId, string $phase, string $severity = 'warning'): void
    {
        try {
            MPLog::generate('Mercado Pago payment initiation held for cart ' . $cartId . ' during ' . $phase, $severity);
        } catch (Throwable $loggingError) {
            // Diagnostic failure cannot change the held outcome.
        }
    }

    /** A negative in-memory POST boundary is useful only after its journal proof commits. */
    private function closeUnpostedGeneration(int $cartId, string $generation): void
    {
        $this->begin();
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'mp_transactions` WHERE cart_id=' . $cartId . ' FOR UPDATE', true, false);
        if (!is_array($rows) || count($rows) > 1) {
            throw new PaymentInitiationBlocked('Unposted reservation could not be revalidated');
        }
        $journal = $this->journal($rows ? $rows[0] : null, $cartId);
        $current = $journal['current'];
        if ($current !== null && $current['generation'] === $generation && $current['state'] === 'reserved'
            && $current['payment_id'] === null && $current['preference_id'] === null
        ) {
            $journal['current']['state'] = 'not_created';
            $this->persist($cartId, $journal);
        }
        $this->commit();
    }

    /** Unconfirmed rollback cannot authorize cleanup or a no-resource journal transition. */
    private function rollbackConfirmed(int $cartId): bool
    {
        if (!$this->sqlActive) {
            return true;
        }
        $confirmed = false;
        try {
            $confirmed = in_array(Db::getInstance()->execute('ROLLBACK'), [true, 1], true);
        } catch (Throwable $rollbackError) {
            // The phase is logged below without raw SQL/exception data.
        }
        $this->sqlActive = !$confirmed;
        if (!$confirmed) {
            $this->logHold($cartId, 'rollback failure', 'error');
        }

        return $confirmed;
    }

    /** Audit IDs identify resources to refetch; their saved statuses never release money. */
    private function verifyPriorAttempts($cart, ?array $registered, array &$journal, string $seller, string $currency, array &$before): array
    {
        $cartId = (int) $cart->id;
        if (Db::getInstance()->getValue('SELECT id_order FROM `' . _DB_PREFIX_ . 'orders` WHERE id_cart=' . $cartId, false)
            || ($registered !== null && (!empty($registered['creation_recovery'])
                || (int) $registered['customer_id'] !== (int) $cart->id_customer))
        ) {
            throw new PaymentInitiationBlocked('An existing payment requires confirmation');
        }
        $ids = array_merge($journal['verification_candidates'], $journal['closed_payment_ids'], array_keys($journal['observed']));
        if ($registered !== null) {
            $ids = array_merge($ids, empty($registered['payment_id']) ? [] : explode(',', $registered['payment_id']));
            foreach ($this->transaction->getStoredPaymentAttempts($registered) as $id => $attempt) {
                $ids[] = (string) $id;
            }
        }
        $generations = $journal['history'];
        if ($journal['current'] !== null) {
            $generations[] = $journal['current'];
        }
        $preferences = [];
        // These caches live only for this verification, never across issuances or callbacks.
        $preferenceResources = [];
        $merchantOrders = [];
        $hasIssuedGeneration = false;
        foreach ($generations as $entry) {
            if ($entry['collector_id'] !== $seller || $entry['currency'] !== $currency
                || $entry['customer_id'] !== (int) $cart->id_customer || $entry['shop_id'] !== (int) $cart->id_shop
            ) {
                throw new PaymentInitiationBlocked('An earlier payment request is unresolved');
            }
            if ($entry['state'] === 'not_created') {
                continue;
            }
            if ($entry['state'] !== 'issued') {
                throw new PaymentInitiationBlocked('An earlier payment request is unresolved');
            }
            $hasIssuedGeneration = true;
            if ($entry['payment_id'] !== null) {
                $ids[] = $entry['payment_id'];
            } elseif ($entry['preference_id'] !== null) {
                $prefId = $entry['preference_id'];
                if (!array_key_exists($prefId, $preferenceResources)) {
                    $preferenceResources[$prefId] = $this->api->getPreference($prefId);
                }
                $preference = $preferenceResources[$prefId];
                $this->assertPreference($preference, $entry['preference_id'], $cartId, $seller);
                $preferences[$entry['preference_id']] = false;
            } else {
                throw new PaymentInitiationBlocked('An earlier payment identity is unavailable');
            }
        }
        if ($registered !== null && $journal['current'] === null && !empty($registered['notification_url'])
            && empty($registered['payment_id']) && empty($registered['payment_attempts'])
        ) {
            throw new PaymentInitiationBlocked('Legacy checkout identity is unresolved');
        }
        $ids = array_merge($ids, $this->api->getCartPaymentIds((string) $cartId));
        $ids = IpnNotification::normalizeCandidatePaymentIds(array_values(array_unique($ids)));
        $this->rememberCandidates($cart, $journal, $ids, $before);
        if ($registered !== null && !$ids && (!empty($registered['notification_url']) || $hasIssuedGeneration)) {
            throw new PaymentInitiationBlocked('An earlier checkout may still be payable');
        }
        for ($cursor = 0; $cursor < count($ids); ++$cursor) {
            $id = $ids[$cursor];
            $payment = $this->api->getPaymentStandard($id, true);
            if (!is_array($payment) || (string) ($payment['id'] ?? '') !== $id
                || (string) ($payment['external_reference'] ?? '') !== (string) $cartId
                || (string) ($payment['collector_id'] ?? '') !== $seller
                || ($payment['currency_id'] ?? '') !== $currency
            ) {
                throw new PaymentInitiationBlocked('Payment identity could not be verified');
            }
            $prefId = null;
            $moId = null;
            if (!empty($payment['order']['id'])) {
                $moId = (string) $payment['order']['id'];
                if (!preg_match('/^[1-9][0-9]{0,19}$/D', $moId)) {
                    throw new PaymentInitiationBlocked('Merchant order identity is invalid');
                }
                if (!array_key_exists($moId, $merchantOrders)) {
                    $merchantOrders[$moId] = $this->api->getMerchantOrder($moId, true);
                }
                $mo = $merchantOrders[$moId];
                if (!is_array($mo) || (string) ($mo['id'] ?? '') !== $moId
                    || (string) ($mo['external_reference'] ?? '') !== (string) $cartId
                    || (string) ($mo['collector']['id'] ?? '') !== $seller || empty($mo['preference_id'])
                ) {
                    throw new PaymentInitiationBlocked('Merchant order ownership is unavailable');
                }
                if (!isset($mo['payments']) || !is_array($mo['payments'])) {
                    throw new PaymentInitiationBlocked('Merchant order discovery is incomplete');
                }
                $moIds = [];
                foreach ($mo['payments'] as $candidate) {
                    if (!is_array($candidate) || !isset($candidate['id'])) {
                        throw new PaymentInitiationBlocked('Merchant order payment identity is missing');
                    }
                    $moIds[] = $candidate['id'];
                }
                $ids = IpnNotification::normalizeCandidatePaymentIds(array_values(array_unique(array_merge($ids, $moIds))));
                $this->rememberCandidates($cart, $journal, $ids, $before);
                $prefId = (string) $mo['preference_id'];
                if (!array_key_exists($prefId, $preferenceResources)) {
                    $preferenceResources[$prefId] = $this->api->getPreference($prefId);
                }
                $this->assertPreference($preferenceResources[$prefId], $prefId, $cartId, $seller);
                if (isset($payment['preference_id']) && (string) $payment['preference_id'] !== $prefId) {
                    throw new PaymentInitiationBlocked('Payment preference identity differs');
                }
                if (array_key_exists($prefId, $preferences)) {
                    $preferences[$prefId] = true;
                }
            } elseif (!in_array($payment['metadata']['checkout_type'] ?? null, ['credit_card', 'pix', 'ticket', 'pse'], true)) {
                throw new PaymentInitiationBlocked('Payment integration scope is unknown');
            }
            $precision = in_array($currency, ['CLP', 'COP'], true) ? 0 : 2;
            PaymentReconciliationSnapshot::fromPayments([$payment], $precision);
            $existing = $journal['observed'][$id] ?? null;
            if ($existing !== null && ($existing['collector_id'] !== $seller || $existing['currency'] !== $currency
                || $existing['merchant_order_id'] !== $moId || $existing['preference_id'] !== $prefId)) {
                throw new PaymentInitiationBlocked('Observed payment scope changed');
            }
            $observation = ['id' => $id, 'collector_id' => $seller, 'currency' => $currency,
                'merchant_order_id' => $moId, 'preference_id' => $prefId,
                'approved_seen' => $this->hasApprovalHistory($cart, $id) || $payment['status'] === 'approved' || !empty($payment['date_approved'])];
            if (($journal['observed'][$id] ?? null) !== $observation) {
                $this->rememberPayment($cart, $journal, $id, $observation, $before);
            }
            $precision = in_array($currency, ['CLP', 'COP'], true) ? 0 : 2;
            if (!PaymentClosureProof::isClosed($payment, $observation['approved_seen'], $precision)) {
                throw new PaymentInitiationBlocked('An earlier payment has unresolved funds');
            }
        }
        if (in_array(false, $preferences, true)) {
            throw new PaymentInitiationBlocked('An earlier checkout has no terminal payment evidence');
        }

        return $ids;
    }

    /** An authenticated discovery ID is a durable hint, never financial proof. */
    private function rememberCandidates($cart, array &$journal, array $ids, array &$before): void
    {
        sort($ids, SORT_STRING);
        if ($ids === $journal['verification_candidates']) {
            return;
        }
        $context = new CartPaymentContext();
        $cartId = (int) $cart->id;
        $this->begin();
        if ($before['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
            throw new PaymentInitiationBlocked('Payment context changed during candidate discovery');
        }
        $this->ensureRegistration($cart);
        $journal['verification_candidates'] = $ids;
        $this->persist($cartId, $journal);
        $before = $context->capture($cartId, true);
        $this->commit();
    }

    /** Remember discovered identities before a later lookup can fail or omit them. */
    private function rememberPayment($cart, array &$journal, string $id, array $observation, array &$before): void
    {
        $context = new CartPaymentContext();
        $cartId = (int) $cart->id;
        $this->begin();
        if ($before['fingerprint'] !== $context->capture($cartId, true)['fingerprint']) {
            throw new PaymentInitiationBlocked('Payment context changed during discovery');
        }
        $this->ensureRegistration($cart);
        $journal['observed'][$id] = $observation;
        $this->persist($cartId, $journal);
        $before = $context->capture($cartId, true);
        $this->commit();
    }

    /** Journal identities delimit generations; only fresh API data can close one. */
    public function webhookPaymentScope($cart, array $payment): string
    {
        $id = (string) $payment['id'];
        $registered = $this->transaction->findReconciliationForCart((int) $cart->id);
        $journal = $this->journal($registered, (int) $cart->id);
        $current = $journal['current'];
        if ($current === null) {
            return 'legacy';
        }
        if ($current['customer_id'] !== (int) $cart->id_customer || $current['shop_id'] !== (int) $cart->id_shop
            || $current['currency'] !== (new Currency((int) $cart->id_currency))->iso_code
            || (string) ($payment['collector_id'] ?? '') !== $current['collector_id']
            || ($payment['currency_id'] ?? '') !== $current['currency']
            || $this->api->getAuthenticatedCollectorId() !== $current['collector_id']
        ) {
            throw new RuntimeException('Payment generation ownership could not be verified');
        }
        if (in_array($id, $journal['closed_payment_ids'], true)) {
            return 'retired';
        }
        if ($current['state'] === 'issued' && $current['kind'] === 'payment' && $current['payment_id'] === $id) {
            return 'current';
        }

        throw new RuntimeException('Payment event belongs to an unresolved or different issuance generation');
    }

    /** True = approval hint; false = valid no-history; uncertainty always throws. */
    public function hasApprovalHistory($cart, string $id): bool
    {
        $row = $this->transaction->getReconciliationForCart((int) $cart->id);
        $journal = $this->journal($row, (int) $cart->id);
        $attempts = $this->transaction->getStoredPaymentAttempts($row);
        $states = ['approved', 'refunded', 'charged_back', 'in_mediation', 'pending', 'in_process', 'authorized', 'rejected', 'cancelled'];
        $approvalStates = ['approved', 'refunded', 'charged_back', 'in_mediation'];
        $history = !empty($journal['observed'][$id]['approved_seen']);
        foreach ($attempts as $attemptId => $attempt) {
            if (!is_string($attempt['status']) || !in_array($attempt['status'], $states, true)
                || !is_int($attempt['approved_at']) || $attempt['approved_at'] < 0
            ) {
                throw new RuntimeException('Stored approval history is incomplete or invalid');
            }
            if ((string) $attemptId === $id
                && (in_array($attempt['status'], $approvalStates, true) || $attempt['approved_at'] > 0)
            ) {
                $history = true;
            }
        }
        $rawIds = $row['payment_id'] ?? null;
        $rawStatuses = $row['payment_status'] ?? null;
        $idsAbsent = $rawIds === null || $rawIds === '';
        $statusesAbsent = $rawStatuses === null || $rawStatuses === '';
        if ($idsAbsent && $statusesAbsent) {
            return $history;
        }
        if ($idsAbsent || $statusesAbsent || !is_string($rawIds) || !is_string($rawStatuses)) {
            throw new RuntimeException('Legacy approval history is incomplete');
        }
        $ids = explode(',', $rawIds);
        $statuses = explode(',', $rawStatuses);
        if (count($ids) !== count($statuses)) {
            throw new RuntimeException('Legacy approval history is not aligned');
        }
        $seen = [];
        foreach ($ids as $offset => $knownId) {
            if (!preg_match('/^[1-9][0-9]{0,19}$/D', $knownId) || isset($seen[$knownId])
                || !in_array($statuses[$offset], $states, true)
            ) {
                throw new RuntimeException('Legacy approval history identity or status is invalid');
            }
            $seen[$knownId] = true;
            if ($knownId === $id && in_array($statuses[$offset], $approvalStates, true)) {
                $history = true;
            }
        }

        return $history;
    }

    public function canArchiveClosedPayment($cart, array $payment): bool
    {
        $row = $this->transaction->getReconciliationForCart((int) $cart->id);
        $journal = $this->journal($row, (int) $cart->id);
        $id = (string) ($payment['id'] ?? '');
        if (!in_array($id, $journal['closed_payment_ids'], true)
            || (string) ($payment['external_reference'] ?? '') !== (string) $cart->id
            || !isset($payment['collector_id'])
        ) {
            return false;
        }
        $seller = $this->api->getAuthenticatedCollectorId();
        $currency = (new Currency((int) $cart->id_currency))->iso_code;
        if ((string) $payment['collector_id'] !== $seller || ($payment['currency_id'] ?? '') !== $currency) {
            return false;
        }
        $hasApprovalHistory = $this->hasApprovalHistory($cart, $id);
        if (empty($payment['order']['id'])) {
            return in_array($payment['metadata']['checkout_type'] ?? null, ['credit_card', 'pix', 'ticket', 'pse'], true)
                && PaymentClosureProof::isClosed($payment, $hasApprovalHistory, in_array($currency, ['CLP', 'COP'], true) ? 0 : 2);
        }
        $moId = (string) $payment['order']['id'];
        if (!preg_match('/^[1-9][0-9]{0,19}$/D', $moId)) {
            return false;
        }
        $mo = $this->api->getMerchantOrder($moId, true);
        if (!is_array($mo) || (string) ($mo['id'] ?? '') !== $moId
            || (string) ($mo['external_reference'] ?? '') !== (string) $cart->id
            || (string) ($mo['collector']['id'] ?? '') !== $seller || empty($mo['preference_id'])
            || (isset($payment['preference_id']) && (string) $payment['preference_id'] !== (string) $mo['preference_id'])
        ) {
            return false;
        }
        $prefId = (string) $mo['preference_id'];
        $this->assertPreference($this->api->getPreference($prefId), $prefId, (int) $cart->id, $seller);

        return PaymentClosureProof::isClosed($payment, $hasApprovalHistory, in_array($currency, ['CLP', 'COP'], true) ? 0 : 2);
    }

    /** Retired, fully closed generations cannot apply their old checkout discount twice. */
    private function retireClosedDiscounts($cart, array $journal): void
    {
        $cartId = (int) $cart->id;
        $entries = $journal['history'];
        if ($journal['current'] !== null) {
            $entries[] = $journal['current'];
        }
        foreach ($entries as $entry) {
            $id = (int) $entry['cart_rule_id'];
            if (!$id) {
                continue;
            }
            $rows = Db::getInstance()->executeS('SELECT id_cart FROM `' . _DB_PREFIX_ . 'cart_cart_rule` WHERE id_cart_rule=' . $id . ' FOR UPDATE', true, false);
            if (!is_array($rows)) {
                throw new PaymentInitiationBlocked('Previous checkout discount ownership is unavailable');
            }
            foreach ($rows as $row) {
                if ((int) $row['id_cart'] !== $cartId) {
                    throw new PaymentInitiationBlocked('Previous checkout discount belongs to another cart');
                }
            }
            $rule = new CartRule($id);
            if (!Validate::isLoadedObject($rule)) {
                if ($rows) {
                    throw new PaymentInitiationBlocked('Previous checkout discount definition is missing');
                }
                continue;
            }
            if ($rule->code !== 'MPDISCOUNT' . $cartId) {
                throw new PaymentInitiationBlocked('Previous checkout discount identity changed');
            }
            if ($rule->active) {
                $rule->active = false;
                if (!$rule->save()) {
                    throw new PaymentInitiationBlocked('Previous checkout discount could not be retired');
                }
            }
            if ($rows) {
                if (!$cart->removeCartRule($id)) {
                    throw new PaymentInitiationBlocked('Previous checkout discount association could not be retired');
                }
                $remaining = Db::getInstance()->executeS('SELECT id_cart FROM `' . _DB_PREFIX_ . 'cart_cart_rule` WHERE id_cart_rule=' . $id . ' AND id_cart=' . $cartId, true, false);
                if (!is_array($remaining) || $remaining) {
                    throw new PaymentInitiationBlocked('Previous checkout discount association remains unresolved');
                }
            }
        }
        Cache::clean('Cart::getCartRules_*');
    }

    private function assertPreference($preference, string $id, int $cartId, string $seller): void
    {
        if (!is_object($preference) || (string) ($preference->id ?? '') !== $id
            || (string) ($preference->external_reference ?? '') !== (string) $cartId
            || (string) ($preference->collector_id ?? '') !== $seller
        ) {
            throw new PaymentInitiationBlocked('Preference ownership is unavailable');
        }
    }

    /** Builders retain this Cart instance; never accept a newer DB address with stale object fields. */
    private function assertCartContext($cart, array $fresh): void
    {
        foreach (['id_customer', 'id_currency', 'id_shop', 'secure_key', 'id_address_delivery', 'id_address_invoice'] as $field) {
            if ((string) ($fresh[$field] ?? '') !== (string) $cart->{$field}) {
                throw new PaymentInitiationBlocked('Cart checkout context changed');
            }
        }
    }

    private function journal(?array $registered, int $cartId): array
    {
        if ($registered === null || empty($registered['payment_initiation'])) {
            return ['version' => 1, 'cart_id' => $cartId, 'current' => null, 'history' => [], 'closed_payment_ids' => [], 'observed' => [], 'verification_candidates' => []];
        }
        $journal = json_decode($registered['payment_initiation'], true);
        if (!is_array($journal) || ($journal['version'] ?? null) !== 1 || ($journal['cart_id'] ?? null) !== $cartId
            || !array_key_exists('current', $journal) || !isset($journal['history']) || !is_array($journal['history'])
            || !isset($journal['closed_payment_ids'], $journal['observed'], $journal['verification_candidates']) || !is_array($journal['verification_candidates']) || !is_array($journal['closed_payment_ids']) || !is_array($journal['observed']) || count($journal['observed']) > 50
            || count($journal['history']) > 100
        ) {
            throw new PaymentInitiationBlocked('Payment initiation history is invalid');
        }
        IpnNotification::normalizeCandidatePaymentIds($journal['closed_payment_ids']);
        IpnNotification::normalizeCandidatePaymentIds($journal['verification_candidates']);
        IpnNotification::normalizeCandidatePaymentIds(array_keys($journal['observed']));
        foreach ($journal['observed'] as $id => $observation) {
            $fields = ['id', 'collector_id', 'currency', 'merchant_order_id', 'preference_id', 'approved_seen'];
            if (!is_array($observation) || array_diff($fields, array_keys($observation)) || array_diff(array_keys($observation), $fields)
                || (string) $id !== $observation['id'] || !is_bool($observation['approved_seen'])
                || !is_string($observation['collector_id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', $observation['collector_id'])
                || !is_string($observation['currency']) || !preg_match('/^[A-Z]{3}$/D', $observation['currency'])
                || ($observation['merchant_order_id'] !== null && (!is_string($observation['merchant_order_id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', $observation['merchant_order_id'])))
                || ($observation['preference_id'] !== null && (!is_string($observation['preference_id']) || !preg_match('/^[a-zA-Z0-9-]{1,100}$/D', $observation['preference_id'])))
            ) {
                throw new PaymentInitiationBlocked('Observed payment identity is invalid');
            }
        }
        $entries = $journal['history'];
        if ($journal['current'] !== null) {
            $entries[] = $journal['current'];
        }
        foreach ($entries as $entry) {
            $fields = ['generation', 'state', 'kind', 'collector_id', 'cart_id', 'customer_id', 'shop_id', 'currency', 'preference_id', 'payment_id', 'cart_rule_id'];
            if (!is_array($entry) || array_diff($fields, array_keys($entry)) || array_diff(array_keys($entry), $fields)
                || !is_string($entry['generation']) || !preg_match('/^[a-f0-9]{32}$/D', $entry['generation'])
                || !in_array($entry['state'], ['reserved', 'issued', 'not_created'], true) || !in_array($entry['kind'], ['preference', 'payment'], true)
                || $entry['cart_id'] !== $cartId || !is_int($entry['customer_id']) || !is_int($entry['shop_id'])
                || !is_int($entry['cart_rule_id']) || $entry['cart_rule_id'] < 0
                || ($entry['state'] === 'not_created' && ($entry['payment_id'] !== null || $entry['preference_id'] !== null))
                || !is_string($entry['collector_id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', $entry['collector_id'])
                || !is_string($entry['currency']) || !preg_match('/^[A-Z]{3}$/D', $entry['currency'])
                || ($entry['payment_id'] !== null && (!is_string($entry['payment_id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', $entry['payment_id'])))
                || ($entry['preference_id'] !== null && (!is_string($entry['preference_id']) || !preg_match('/^[a-zA-Z0-9-]{1,100}$/D', $entry['preference_id'])))
            ) {
                throw new PaymentInitiationBlocked('Payment generation schema is invalid');
            }
        }

        return $journal;
    }

    private function ensureRegistration($cart): void
    {
        if ($this->transaction->findReconciliationForCart((int) $cart->id) !== null) {
            return;
        }
        $moduleId = Db::getInstance()->getValue('SELECT id_mp_module FROM `' . _DB_PREFIX_ . 'mp_module` WHERE version=\'' . pSQL(MP_VERSION) . '\' ORDER BY id_mp_module DESC', false);
        if (!$moduleId || !Db::getInstance()->insert('mp_transactions', [
            'cart_id' => (int) $cart->id, 'customer_id' => (int) $cart->id_customer,
            'mp_module_id' => (int) $moduleId, 'created_at' => date('Y-m-d H:i:s'),
        ])) {
            throw new PaymentInitiationBlocked('Payment registration could not be reserved');
        }
    }

    private function persist(int $cartId, array $journal): void
    {
        $encoded = json_encode($journal, JSON_UNESCAPED_SLASHES);
        if ($encoded === false || strlen($encoded) > 1000000 || count($journal['history']) > 100) {
            throw new PaymentInitiationBlocked('Payment initiation history capacity exceeded');
        }
        $this->transaction->persistReconciliation($cartId, ['payment_initiation' => $encoded]);
    }

    private function assertStorage(): void
    {
        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'mp_transactions` LIKE \'payment_initiation\'', true, false);
        $engine = Db::getInstance()->getValue('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=\'' . pSQL(_DB_NAME_) . '\' AND TABLE_NAME=\'' . pSQL(_DB_PREFIX_ . 'mp_transactions') . '\'', false);
        if (!is_array($columns) || count($columns) !== 1 || !in_array(strtolower($columns[0]['Type']), ['mediumtext', 'longtext'], true)
            || $columns[0]['Null'] !== 'YES' || strtoupper((string) $engine) !== 'INNODB'
        ) {
            throw new PaymentInitiationBlocked('Transactional initiation storage is unavailable');
        }
    }

    private function begin(): void
    {
        if (!Db::getInstance()->execute('START TRANSACTION')) {
            throw new PaymentInitiationBlocked('Payment reservation transaction failed');
        }
        $this->sqlActive = true;
    }

    private function commit(): void
    {
        if (!Db::getInstance()->execute('COMMIT')) {
            throw new PaymentInitiationBlocked('Payment reservation commit is unconfirmed');
        }
        $this->sqlActive = false;
    }
}
