<?php
/**
 * 2007-2019 PrestaShop
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
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

$sql = array();

//module table
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

//transactions table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_transactions` (
      `id_mp_transaction` INT(11) NOT NULL AUTO_INCREMENT,
      `cart_id` INT NOT NULL,
      `order_id` INT NULL,
      `customer_id` INT NOT NULL,
      `total` DECIMAL(15,2) NULL,
      `payment_id` VARCHAR(100) NULL,
      `payment_method` VARCHAR(100) NULL,
      `payment_type` VARCHAR(100) NULL,
      `payment_status` VARCHAR(100) NULL,
      `payment_amount` VARCHAR(100) NULL,
      `merchant_order_id` INT NULL,
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

//Create tables
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        MPLog::generate('Failed to create tables (mp_module and mp_transactions) in database', 'error');
        return false;
    }
}

//Insert necessary data on DB
$mp_module = new MPModule();
$count = $mp_module->where('version', '=', MP_VERSION)->count();

if ($count == 0) {
    $old_mp = $mp_module->orderBy('id_mp_module', 'desc')->get();
    $old_mp = $mp_module->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(["updated" => true]);
    $mp_module->create(["version" => MP_VERSION]);
}
