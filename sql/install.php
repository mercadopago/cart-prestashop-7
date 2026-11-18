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

if (!isset($record_installation_mp_module_id)
    || !is_callable($record_installation_mp_module_id)
    || !isset($record_installation_previous_mp_module)
    || !is_callable($record_installation_previous_mp_module)
) {
    return false;
}

$sql = [];

// module table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_module` (
    `id_mp_module` INT(11) NOT NULL AUTO_INCREMENT,
    `version` VARCHAR(20) NOT NULL,
    `updated` TINYINT(1) NULL,
    `evaluation` VARCHAR(20) NULL,
    `comments` TINYTEXT NULL,
    `recommend` TINYINT(1) NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id_mp_module`))
    ENGINE = ' . _MYSQL_ENGINE_ . 'DEFAULT CHARSET=utf8';

// transactions table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_transactions` (
      `id_mp_transaction` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `cart_id` INT(10) UNSIGNED NOT NULL,
      `order_id` INT(10) UNSIGNED NULL,
      `customer_id` INT(11) UNSIGNED NOT NULL,
      `total` DECIMAL(15,2) NULL,
      `payment_id` TEXT NULL,
      `payment_method` TEXT NULL,
      `payment_type` TEXT NULL,
      `payment_status` TEXT NULL,
      `payment_amount` TEXT NULL,
      `payment_attempts` MEDIUMTEXT NULL,
      `creation_recovery` MEDIUMTEXT NULL,
      `payment_initiation` MEDIUMTEXT NULL,
      `merchant_order_id` VARCHAR(100) NULL,
      `notification_url` TEXT NULL,
      `is_payment_test` TINYINT(1) NULL,
      `received_webhook` TINYINT(1) NULL,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NULL,
      `mp_module_id` INT NOT NULL,
      PRIMARY KEY (`id_mp_transaction`),
      CONSTRAINT `mp_module_id`
      FOREIGN KEY (`mp_module_id`)
      REFERENCES `' . _DB_PREFIX_ . 'mp_module` (`id_mp_module`)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION)
    ENGINE = ' . _MYSQL_ENGINE_ . 'DEFAULT CHARSET=utf8';

// Create tables
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

// Insert version metadata and propagate every database failure to install().
$current_modules = Db::getInstance()->executeS(
    'SELECT `id_mp_module` FROM `' . _DB_PREFIX_ . 'mp_module`'
    . " WHERE `version` = '" . pSQL(MP_VERSION) . "'"
    . ' ORDER BY `id_mp_module` DESC LIMIT 1',
    true,
    false
);

if (!is_array($current_modules)) {
    return false;
}

if (count($current_modules) === 0) {
    if (!Db::getInstance()->insert(
        'mp_module',
        [
            'version' => pSQL(MP_VERSION),
            'created_at' => date('Y-m-d H:i:s'),
        ]
    )) {
        return false;
    }

    $id_mp_module = (int) Db::getInstance()->Insert_ID();
    if ($id_mp_module <= 0) {
        return false;
    }

    $record_installation_mp_module_id($id_mp_module);
} elseif (!isset($current_modules[0]['id_mp_module'])
    || (int) $current_modules[0]['id_mp_module'] <= 0
) {
    return false;
} else {
    $id_mp_module = (int) $current_modules[0]['id_mp_module'];
}

$previous_modules = Db::getInstance()->executeS(
    'SELECT `id_mp_module`, `updated`, `updated_at` FROM `' . _DB_PREFIX_ . 'mp_module`'
    . ' WHERE `id_mp_module` < ' . $id_mp_module
    . ' ORDER BY `id_mp_module` DESC LIMIT 1',
    true,
    false
);

if (!is_array($previous_modules)) {
    return false;
}

if (isset($previous_modules[0]['id_mp_module'])) {
    $previous_id = (int) $previous_modules[0]['id_mp_module'];
    if ($previous_id <= 0) {
        return false;
    }

    if (!isset($previous_modules[0]['updated']) || (int) $previous_modules[0]['updated'] !== 1) {
        $record_installation_previous_mp_module($previous_modules[0]);

        if (!Db::getInstance()->update(
            'mp_module',
            [
                'updated' => true,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            '`id_mp_module` = ' . $previous_id
        )) {
            return false;
        }
    }
}

return true;
