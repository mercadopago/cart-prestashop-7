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

/** Expand the audit and register this version; existing orders/payments stay untouched. */
function upgrade_module_4_19_2($module)
{
    try {
        $db = Db::getInstance();
        $minimum = [
            'payment_id' => 'text', 'payment_type' => 'text', 'payment_method' => 'text',
            'payment_status' => 'text', 'payment_amount' => 'text', 'payment_attempts' => 'mediumtext',
            'creation_recovery' => 'mediumtext', 'payment_initiation' => 'mediumtext',
        ];
        $rank = ['tinytext' => 0, 'text' => 1, 'mediumtext' => 2, 'longtext' => 3];
        $columns = $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'mp_transactions`', true, false);
        if (!is_array($columns)) {
            return false;
        }
        $types = array_column($columns, 'Type', 'Field');
        $nullable = array_column($columns, 'Null', 'Field');
        $changes = [];
        foreach ($minimum as $field => $required) {
            if (!isset($types[$field])) {
                if (!in_array($field, ['payment_attempts', 'creation_recovery', 'payment_initiation'], true)) {
                    return false;
                }
                $changes[] = 'ADD `' . $field . '` ' . strtoupper($required) . ' NULL';
                continue;
            }
            $type = strtolower($types[$field]);
            if (isset($rank[$type])) {
                $target = $rank[$type] >= $rank[$required] ? $type : $required;
            } elseif (preg_match('/^(?:var)?char\([1-9][0-9]*\)$/D', $type)) {
                $target = $required;
            } else {
                return false;
            }
            if ($target !== $type || ($nullable[$field] ?? '') !== 'YES') {
                $changes[] = 'MODIFY `' . $field . '` ' . strtoupper($target) . ' NULL';
            }
        }
        if ($changes && !$db->execute('ALTER TABLE `' . _DB_PREFIX_ . 'mp_transactions` ' . implode(', ', $changes))) {
            return false;
        }
        $columns = $db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'mp_transactions`', true, false);
        if (!is_array($columns)) {
            return false;
        }
        $types = array_column($columns, 'Type', 'Field');
        $nullable = array_column($columns, 'Null', 'Field');
        foreach ($minimum as $field => $required) {
            $type = strtolower($types[$field] ?? '');
            if (!isset($rank[$type]) || $rank[$type] < $rank[$required] || ($nullable[$field] ?? '') !== 'YES') {
                return false;
            }
        }

        // Use installation's database-scoped lock: version has no unique index.
        $lock = 'mp_install_' . sha1(_DB_NAME_ . '|' . _DB_PREFIX_ . '|mercadopago');
        if ((int) $db->getValue("SELECT GET_LOCK('" . pSQL($lock) . "', 10)", false) !== 1) {
            return false;
        }
        $released = false;
        try {
            $query = 'SELECT id_mp_module FROM `' . _DB_PREFIX_ . 'mp_module`'
                . " WHERE version='" . pSQL(MP_VERSION) . "' ORDER BY id_mp_module DESC LIMIT 1";
            $rows = $db->executeS($query, true, false);
            if (!is_array($rows)) {
                throw new RuntimeException('Module version registration could not be verified');
            }
            if (!$rows && !$db->insert('mp_module', [
                'version' => pSQL(MP_VERSION), 'created_at' => date('Y-m-d H:i:s'),
            ])) {
                throw new RuntimeException('Module version registration could not be verified');
            }
            $rows = $db->executeS($query, true, false);
            if (!is_array($rows) || count($rows) !== 1 || (int) $rows[0]['id_mp_module'] <= 0) {
                throw new RuntimeException('Module version registration could not be verified');
            }
        } finally {
            $released = (int) $db->getValue("SELECT RELEASE_LOCK('" . pSQL($lock) . "')", false) === 1;
        }

        return $released;
    } catch (Throwable $error) {
        MPLog::generate('Could not expand Mercado Pago payment audit fields', 'error');

        return false;
    }
}
