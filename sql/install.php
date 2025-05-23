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
      `id_mp_transaction` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `cart_id` INT(10) UNSIGNED NOT NULL,
      `order_id` INT(10) UNSIGNED NULL,
      `customer_id` INT(11) UNSIGNED NOT NULL,
      `total` DECIMAL(15,2) NULL,
      `payment_id` VARCHAR(100) NULL,
      `payment_method` VARCHAR(100) NULL,
      `payment_type` VARCHAR(100) NULL,
      `payment_status` VARCHAR(100) NULL,
      `payment_amount` VARCHAR(100) NULL,
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

//Create tables
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        MPLog::generate('Failed to execute query: ' . Db::getInstance()->getMsgError(), 'error');
        return false;
    }
}

//Insert necessary data on DB
$mp_module = new MPModule();
$count = $mp_module->where('version', '=', MP_VERSION)->count();

if ($count == 0) {
    $old_mp = $mp_module->orderBy('id_mp_module', 'desc')->get();
    if (isset($old_mp['id_mp_module'])) {
        $old_mp = $mp_module->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(["updated" => true]);
    }
    $mp_module->create(["version" => MP_VERSION]);
}

//Prestashop configuration table
Configuration::updateValue('MERCADOPAGO_AUTO_RETURN', true);
Configuration::updateValue('MERCADOPAGO_PROD_STATUS', false);
Configuration::updateValue('MERCADOPAGO_INSTALLMENTS', 24);
Configuration::updateValue('MERCADOPAGO_STANDARD', false);
Configuration::updateValue('MERCADOPAGO_HOMOLOGATION', false);
Configuration::updateValue('MERCADOPAGO_STANDARD_MODAL', true);
Configuration::updateValue('MERCADOPAGO_CUSTOM_WALLET_BUTTON', true);

//Remove Mercado Envios
Configuration::updateValue('MERCADOENVIOS_ACTIVATE', false);
Configuration::deleteByName('MERCADOPAGO_CARRIER');
Configuration::deleteByName('MERCADOPAGO_CARRIER_ID_1');
Configuration::deleteByName('MERCADOPAGO_CARRIER_ID_2');
