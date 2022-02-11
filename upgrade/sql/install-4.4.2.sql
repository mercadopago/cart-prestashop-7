
ALTER TABLE `PREFIX_mp_transactions`
CHANGE `id_mp_transaction` `id_mp_transaction` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `cart_id` `cart_id` INT(10) UNSIGNED NOT NULL,
CHANGE `order_id` `order_id` INT(10) UNSIGNED NULL DEFAULT NULL,
CHANGE `customer_id` `customer_id` INT(11) UNSIGNED NOT NULL,
CHANGE `merchant_order_id` `merchant_order_id` VARCHAR(100) NULL DEFAULT NULL;
