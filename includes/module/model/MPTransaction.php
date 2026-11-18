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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/MPAbstractDB.php';

class MPTransaction extends MPAbstractDB
{
    public function __construct()
    {
        parent::__construct();
        $this->table = _DB_PREFIX_ . 'mp_transactions';
    }

    /** Read from the master without the inherited query cache. */
    public function getReconciliationForCart(int $cartId): array
    {
        $row = $this->findReconciliationForCart($cartId);
        if ($row === null) {
            throw new RuntimeException('Mercado Pago cart registration is missing');
        }

        return $row;
    }

    public function findReconciliationForCart(int $cartId): ?array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mp_transactions` WHERE `cart_id` = ' . $cartId,
            true,
            false
        );
        if (!is_array($rows) || count($rows) > 1) {
            throw new RuntimeException('Mercado Pago cart registration is ambiguous');
        }

        return $rows ? $rows[0] : null;
    }

    /** Persist aligned attempt audit fields, never an inferred receipt. */
    public function persistReconciliation(int $cartId, array $data): void
    {
        $before = $this->getReconciliationForCart($cartId);
        $allowed = ['order_id', 'merchant_order_id', 'payment_id', 'payment_type', 'payment_method', 'payment_status', 'payment_amount', 'payment_attempts', 'creation_recovery', 'payment_initiation', 'received_webhook'];
        $escaped = [];
        foreach ($data as $field => $value) {
            $capacity = in_array($field, ['payment_attempts', 'creation_recovery', 'payment_initiation'], true) ? 16777215 : 65535;
            if (!in_array($field, $allowed, true) || strlen((string) $value) > $capacity) {
                throw new InvalidArgumentException('Invalid Mercado Pago payment audit field');
            }
            $escaped[$field] = pSQL((string) $value);
        }
        $escaped['updated_at'] = date('Y-m-d H:i:s');
        if (!Db::getInstance()->update('mp_transactions', $escaped, '`id_mp_transaction` = ' . (int) $before['id_mp_transaction'], 0, false, true)) {
            throw new RuntimeException('Could not persist Mercado Pago payment audit');
        }
        $after = $this->getReconciliationForCart($cartId);
        foreach ($data as $field => $value) {
            if ((string) $after[$field] !== (string) $value) {
                throw new RuntimeException('Mercado Pago payment audit read-back failed');
            }
        }
    }

    /** Complete audit across preferences; it never authorizes a financial receipt. */
    public function getStoredPaymentAttempts(array $registration): array
    {
        if (empty($registration['payment_attempts'])) {
            return [];
        }
        $attempts = json_decode($registration['payment_attempts'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($attempts)) {
            throw new RuntimeException('Stored payment audit is invalid');
        }
        $fields = ['id', 'status', 'type', 'method', 'currency', 'nominal_amount', 'payer_paid_amount', 'coupon_amount', 'updated_at', 'approved_at', 'merchant_order_id'];
        foreach ($attempts as $id => $attempt) {
            if (!is_array($attempt) || array_diff($fields, array_keys($attempt))
                || array_diff(array_keys($attempt), $fields)
                || (string) $id !== (string) $attempt['id']
                || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $id)
            ) {
                throw new RuntimeException('Stored payment audit schema is invalid');
            }
        }

        return $attempts;
    }
}
