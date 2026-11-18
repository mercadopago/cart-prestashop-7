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
 * If a copy of the license was not supplied, consult LICENSE.md
 * or the license URL below.
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Shared authoritative cart context, without API or financial writes. */
class CartPaymentContext
{
    /** Capture raw financial context before lookup and re-read it as current locked rows. */
    public function capture(int $cartId, bool $locking): array
    {
        $suffix = $locking ? ' FOR UPDATE' : '';
        $cartRows = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart` WHERE id_cart=' . $cartId . $suffix);
        if (count($cartRows) !== 1) {
            throw new RuntimeException('Cart could not be revalidated');
        }
        $cart = $cartRows[0];
        $customers = $this->lookupContextRows('SELECT id_customer,id_default_group,secure_key FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer=' . (int) $cart['id_customer'] . $suffix);
        $registrations = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'mp_transactions` WHERE cart_id=' . $cartId . $suffix);
        if (count($customers) !== 1 || count($registrations) > 1) {
            throw new RuntimeException('Payment ownership context is ambiguous');
        }
        $products = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_product` WHERE id_cart=' . $cartId . ' ORDER BY id_product,id_product_attribute,id_address_delivery,id_customization' . $suffix);
        $associations = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_cart_rule` WHERE id_cart=' . $cartId . ' ORDER BY id_cart_rule' . $suffix);
        $rules = $this->lookupContextRows('SELECT cr.* FROM `' . _DB_PREFIX_ . 'cart_rule` cr INNER JOIN `' . _DB_PREFIX_ . 'cart_cart_rule` ccr ON ccr.id_cart_rule=cr.id_cart_rule WHERE ccr.id_cart=' . $cartId . ' ORDER BY cr.id_cart_rule' . $suffix);
        $orders = $this->lookupContextRows('SELECT id_order,id_cart,reference,id_customer,id_currency,module,total_paid,total_paid_tax_incl,total_paid_tax_excl FROM `' . _DB_PREFIX_ . 'orders` WHERE id_cart=' . $cartId . ' ORDER BY id_order' . $suffix);
        $currencyIds = [(int) $cart['id_currency']];
        foreach ($rules as $rule) {
            $currencyIds[] = (int) $rule['minimum_amount_currency'];
            $currencyIds[] = (int) $rule['reduction_currency'];
        }
        $currencyIds = array_values(array_unique(array_filter($currencyIds)));
        $currency = $currencyIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'currency` WHERE id_currency IN (' . implode(',', $currencyIds) . ') ORDER BY id_currency' . $suffix) : [];
        $addressIds = array_values(array_unique(array_filter([(int) $cart['id_address_delivery'], (int) $cart['id_address_invoice']])));
        $addresses = $addressIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE id_address IN (' . implode(',', $addressIds) . ') ORDER BY id_address' . $suffix) : [];
        $productIds = array_values(array_unique(array_map('intval', array_column($products, 'id_product'))));
        $productShops = $productIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'product_shop` WHERE id_shop=' . (int) $cart['id_shop'] . ' AND id_product IN (' . implode(',', $productIds) . ') ORDER BY id_product' . $suffix) : [];
        $attributeIds = array_values(array_unique(array_filter(array_map('intval', array_column($products, 'id_product_attribute')))));
        $attributeShops = $attributeIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute_shop` WHERE id_shop=' . (int) $cart['id_shop'] . ' AND id_product_attribute IN (' . implode(',', $attributeIds) . ') ORDER BY id_product_attribute' . $suffix) : [];
        $references = [];
        foreach ($orders as $order) {
            $references[] = "'" . pSQL($order['reference']) . "'";
        }
        $payments = $references ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'order_payment` WHERE order_reference IN (' . implode(',', array_unique($references)) . ') ORDER BY id_order_payment' . $suffix) : [];
        $pricingDependencies = $this->capturePricingDependencies($cart, $customers[0], $products, $rules, $addresses, $locking);
        $currentTime = date('Y-m-d H:i:s');
        foreach ($rules as &$rule) {
            $rule['available_quantity'] = (int) $rule['quantity'] > 0;
            $rule['valid_dates'] = $rule['date_from'] <= $currentTime && $rule['date_to'] >= $currentTime;
        }
        unset($rule);
        $context = [
            $this->lookupFields([$cart], ['id_cart', 'id_customer', 'id_guest', 'id_currency', 'id_address_delivery', 'id_address_invoice', 'id_shop', 'id_shop_group', 'id_carrier', 'id_lang', 'secure_key', 'delivery_option', 'recyclable', 'gift', 'gift_message', 'allow_seperated_package']),
            $customers,
            $this->lookupFields($registrations, ['id_mp_transaction', 'cart_id', 'customer_id', 'order_id', 'mp_module_id', 'merchant_order_id', 'payment_id', 'payment_type', 'payment_method', 'payment_status', 'payment_amount', 'payment_attempts', 'creation_recovery', 'payment_initiation']),
            $this->lookupFields($products, ['id_cart', 'id_product', 'id_product_attribute', 'id_address_delivery', 'id_shop', 'id_customization', 'quantity']),
            $this->lookupFields($associations, ['id_cart', 'id_cart_rule']),
            $this->lookupFields($rules, ['id_cart_rule', 'id_customer', 'active', 'date_from', 'date_to', 'available_quantity', 'valid_dates', 'quantity_per_user', 'priority', 'partial_use', 'minimum_amount', 'minimum_amount_tax', 'minimum_amount_currency', 'minimum_amount_shipping', 'country_restriction', 'carrier_restriction', 'group_restriction', 'cart_rule_restriction', 'product_restriction', 'shop_restriction', 'free_shipping', 'reduction_percent', 'reduction_amount', 'reduction_tax', 'reduction_currency', 'reduction_product', 'reduction_exclude_special', 'gift_product', 'gift_product_attribute']),
            $orders,
            $this->lookupFields($currency, ['id_currency', 'iso_code', 'conversion_rate', 'precision', 'active', 'deleted']),
            $this->lookupFields($addresses, ['id_address', 'id_customer', 'id_country', 'id_state', 'postcode', 'vat_number', 'dni', 'deleted']),
            $this->lookupFields($productShops, ['id_product', 'id_shop', 'price', 'id_tax_rules_group', 'ecotax', 'unit_price_ratio', 'unit_price', 'on_sale', 'available_for_order', 'active', 'additional_shipping_cost']),
            $this->lookupFields($attributeShops, ['id_product_attribute', 'id_shop', 'price', 'ecotax', 'unit_price_impact', 'weight', 'minimal_quantity', 'available_date']),
            $this->lookupFields($payments, ['id_order_payment', 'order_reference', 'id_currency', 'amount', 'payment_method', 'conversion_rate', 'transaction_id', 'id_employee', 'card_number', 'card_holder']),
            $pricingDependencies,
        ];

        return ['cart' => $cart, 'fingerprint' => self::fingerprint($context)];
    }

    /** Hash only the selected scalar/array context; invalid encoding must fail closed. */
    public static function fingerprint(array $context): string
    {
        $encoded = json_encode($context, JSON_PRESERVE_ZERO_FRACTION);
        if ($encoded === false) {
            throw new RuntimeException('Payment context could not be encoded');
        }

        return hash('sha256', $encoded);
    }

    /** Native price and attached-rule inputs, bounded to this cart's products and owners. */
    private function capturePricingDependencies(array $cart, array $customer, array $products, array $rules, array $addresses, bool $locking): array
    {
        $suffix = $locking ? ' LOCK IN SHARE MODE' : '';
        $cartId = (int) $cart['id_cart'];
        $customerId = (int) $cart['id_customer'];
        $productIds = array_values(array_unique(array_map('intval', array_column($products, 'id_product'))));
        $attributeIds = array_values(array_unique(array_map('intval', array_column($products, 'id_product_attribute'))));
        $hasGroupRestriction = false;
        foreach ($rules as $rule) {
            $hasGroupRestriction = $hasGroupRestriction || !empty($rule['group_restriction']);
        }
        $customerGroups = $hasGroupRestriction ? $this->lookupContextRows('SELECT id_customer,id_group FROM `' . _DB_PREFIX_ . 'customer_group` WHERE id_customer=' . $customerId . ' ORDER BY id_group' . $suffix) : [];
        $groupIds = [0, (int) $customer['id_default_group'], (int) Group::getCurrent()->id];
        $countryIds = [0, (int) Configuration::get('PS_COUNTRY_DEFAULT')];
        if (isset(Context::getContext()->country->id)) {
            $countryIds[] = (int) Context::getContext()->country->id;
        }
        foreach ($addresses as $address) {
            $countryIds[] = (int) $address['id_country'];
        }
        $prices = [];
        $priorities = [];
        $priceConfiguration = [];
        if ($productIds) {
            $priceProducts = array_values(array_unique(array_merge([0], $productIds)));
            $priceAttributes = array_values(array_unique(array_merge([0], $attributeIds)));
            $prices = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'specific_price`' . ($locking ? ' FORCE INDEX (id_product_2)' : '') . ' WHERE id_product IN (' . implode(',', $priceProducts) . ')'
                . ' AND id_product_attribute IN (' . implode(',', $priceAttributes) . ')'
                . ' AND id_shop IN (0,' . (int) $cart['id_shop'] . ') AND id_currency IN (0,' . (int) $cart['id_currency'] . ')'
                . ' AND id_customer IN (0,' . $customerId . ') AND id_cart IN (0,' . $cartId . ')'
                . ' AND id_group IN (' . implode(',', array_unique($groupIds)) . ') AND id_country IN (' . implode(',', array_unique($countryIds)) . ')'
                . ' ORDER BY id_product,id_product_attribute,id_specific_price' . $suffix);
            $priorities = $this->lookupContextRows('SELECT id_product,priority FROM `' . _DB_PREFIX_ . 'specific_price_priority` WHERE id_product IN (' . implode(',', $productIds) . ') ORDER BY id_product' . $suffix);
            $priceConfiguration = $this->lookupContextRows('SELECT id_configuration,id_shop_group,id_shop,name,value FROM `' . _DB_PREFIX_ . 'configuration` WHERE name IN (\'PS_SPECIFIC_PRICE_PRIORITIES\',\'PS_SPECIFIC_PRICE_FEATURE_ACTIVE\',\'PS_QTY_DISCOUNT_ON_COMBINATION\')'
                . ' AND (id_shop IS NULL OR id_shop=' . (int) $cart['id_shop'] . ') AND (id_shop_group IS NULL OR id_shop_group=' . (int) $cart['id_shop_group'] . ')'
                . ' ORDER BY id_configuration');
            if ($locking) {
                $this->lockLookupPrimaryRows('configuration', 'id_configuration', $priceConfiguration);
            }
            $prices = $this->eligibleSpecificPrices($prices, $products, (bool) Configuration::get('PS_QTY_DISCOUNT_ON_COMBINATION'));
        }
        $restrictedIds = ['country' => [], 'carrier' => [], 'group' => [], 'shop' => [], 'combination' => []];
        $restrictedProductRuleIds = [];
        foreach ($rules as $rule) {
            foreach (['country', 'carrier', 'group', 'shop'] as $type) {
                if (!empty($rule[$type . '_restriction'])) {
                    $restrictedIds[$type][] = (int) $rule['id_cart_rule'];
                }
            }
            if (!empty($rule['cart_rule_restriction'])) {
                $restrictedIds['combination'][] = (int) $rule['id_cart_rule'];
            }
            if (!empty($rule['product_restriction'])) {
                $restrictedProductRuleIds[] = (int) $rule['id_cart_rule'];
            }
        }
        $restrictions = [];
        foreach (['country', 'carrier', 'group', 'shop'] as $type) {
            if ($restrictedIds[$type]) {
                $ids = implode(',', array_unique($restrictedIds[$type]));
                $restrictions[$type] = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_' . $type . '` WHERE id_cart_rule IN (' . $ids . ') ORDER BY id_cart_rule,id_' . $type . $suffix);
            }
        }
        if ($restrictedIds['combination']) {
            $ids = implode(',', array_unique($restrictedIds['combination']));
            $restrictions['combination'] = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_combination` WHERE id_cart_rule_1 IN (' . $ids . ') OR id_cart_rule_2 IN (' . $ids . ') ORDER BY id_cart_rule_1,id_cart_rule_2');
            if ($locking) {
                $this->lockLookupCombinationRows($restrictions['combination']);
            }
        }
        $groups = $restrictedProductRuleIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` WHERE id_cart_rule IN (' . implode(',', array_unique($restrictedProductRuleIds)) . ') ORDER BY id_product_rule_group') : [];
        if ($locking) {
            $this->lockLookupPrimaryRows('cart_rule_product_rule_group', 'id_product_rule_group', $groups);
        }
        $groupRuleIds = array_map('intval', array_column($groups, 'id_product_rule_group'));
        $productRules = $groupRuleIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule` WHERE id_product_rule_group IN (' . implode(',', $groupRuleIds) . ') ORDER BY id_product_rule') : [];
        if ($locking) {
            $this->lockLookupPrimaryRows('cart_rule_product_rule', 'id_product_rule', $productRules);
        }
        $productRuleIds = array_map('intval', array_column($productRules, 'id_product_rule'));
        $values = $productRuleIds ? $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` WHERE id_product_rule IN (' . implode(',', $productRuleIds) . ') ORDER BY id_product_rule,id_item' . $suffix) : [];
        $types = array_column($productRules, 'type');
        $categories = $productIds && in_array('categories', $types, true) ? $this->lookupContextRows('SELECT id_category,id_product FROM `' . _DB_PREFIX_ . 'category_product` WHERE id_product IN (' . implode(',', $productIds) . ') ORDER BY id_product,id_category' . $suffix) : [];
        $attributes = $attributeIds && in_array('attributes', $types, true) ? $this->lookupContextRows('SELECT id_attribute,id_product_attribute FROM `' . _DB_PREFIX_ . 'product_attribute_combination` WHERE id_product_attribute IN (' . implode(',', $attributeIds) . ') ORDER BY id_product_attribute,id_attribute' . $suffix) : [];
        $productOwners = $productIds && (in_array('manufacturers', $types, true) || in_array('suppliers', $types, true)) ? $this->lookupContextRows('SELECT id_product,id_manufacturer,id_supplier FROM `' . _DB_PREFIX_ . 'product` WHERE id_product IN (' . implode(',', $productIds) . ') ORDER BY id_product' . $suffix) : [];

        return [$customerGroups, $prices, $priorities, $priceConfiguration, $restrictions, $groups, $productRules, $values, $categories, $attributes, $productOwners];
    }

    /** Apply core's date and quantity eligibility only; core still calculates prices. */
    private function eligibleSpecificPrices(array $prices, array $products, bool $quantityPerCombination): array
    {
        $totals = [];
        foreach ($products as $product) {
            $id = (int) $product['id_product'];
            $totals[$id] = ($totals[$id] ?? 0) + (int) $product['quantity'];
        }
        $now = date('Y-m-d H:i:00');
        $eligible = [];
        foreach ($prices as $price) {
            if (($price['from'] !== '0000-00-00 00:00:00' && $price['from'] > $now)
                || ($price['to'] !== '0000-00-00 00:00:00' && $price['to'] < $now)
            ) {
                continue;
            }
            foreach ($products as $product) {
                $productId = (int) $product['id_product'];
                if (((int) $price['id_product'] !== 0 && (int) $price['id_product'] !== $productId)
                    || ((int) $price['id_product_attribute'] !== 0 && (int) $price['id_product_attribute'] !== (int) $product['id_product_attribute'])
                ) {
                    continue;
                }
                $quantity = $quantityPerCombination ? (int) $product['quantity'] : $totals[$productId];
                if (max(1, $quantity) >= (int) $price['from_quantity']) {
                    $eligible[] = $price;
                    break;
                }
            }
        }

        return $eligible;
    }

    /** Lock identified rows by primary key without locking unindexed coupon tables. */
    private function lockLookupPrimaryRows(string $table, string $key, array $rows): void
    {
        if (!$rows) {
            return;
        }
        $ids = array_map('intval', array_column($rows, $key));
        $locked = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . bqSQL($table) . '` WHERE `' . bqSQL($key) . '` IN (' . implode(',', $ids) . ') ORDER BY `' . bqSQL($key) . '` LOCK IN SHARE MODE');
        $fields = array_keys($rows[0]);
        if ($this->lookupFields($locked, $fields) !== $this->lookupFields($rows, $fields)) {
            throw new RuntimeException('Payment pricing context changed during revalidation');
        }
    }

    private function lockLookupCombinationRows(array $rows): void
    {
        if (!$rows) {
            return;
        }
        $pairs = [];
        foreach ($rows as $row) {
            $pairs[] = '(' . (int) $row['id_cart_rule_1'] . ',' . (int) $row['id_cart_rule_2'] . ')';
        }
        $locked = $this->lookupContextRows('SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_combination` WHERE (id_cart_rule_1,id_cart_rule_2) IN (' . implode(',', $pairs) . ') ORDER BY id_cart_rule_1,id_cart_rule_2 LOCK IN SHARE MODE');
        if ($locked !== $rows) {
            throw new RuntimeException('Payment coupon combination changed during revalidation');
        }
    }

    private function lookupFields(array $rows, array $fields): array
    {
        $selected = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $item[$field] = $row[$field];
                }
            }
            $selected[] = $item;
        }

        return $selected;
    }

    private function lookupContextRows(string $query): array
    {
        $rows = Db::getInstance()->executeS($query, true, false);
        if (!is_array($rows)) {
            throw new RuntimeException('Payment context could not be read');
        }

        return $rows;
    }
}
