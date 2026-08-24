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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/checkouts/PseCheckout.php';

abstract class AbstractPreference
{
    public $module;
    public $checkout;
    public $settings;
    public $mpuseful;
    public $cart_rule;
    public $mercadopago;
    public $ps_cart_rule;
    public $ps_cart_rule_rule;

    /**
     * AbstractPreference constructor.
     */
    public function __construct()
    {
        $this->module = Module::getInstanceByName('mercadopago');
        $this->settings = $this->getMercadoPagoSettings();
        $this->mpuseful = MPUseful::getInstance();
        $this->mercadopago = MPApi::getInstance();
        $this->ps_cart_rule = new PSCartRule();
        $this->ps_cart_rule_rule = new PSCartRuleRule();
    }

    /**
     * Verify if module is available
     *
     * @return void
     */
    public function verifyModuleParameters()
    {
        $cart = $this->module->context->cart;
        $authorized = false;

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'mercadopago') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            exit($this->module->l('This payment method is not available.'));
        }
    }

    /**
     * @param $cart
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCommonPreference($cart)
    {
        $preference = [
            'external_reference' => $cart->id,
            'notification_url' => $this->getNotificationUrl($cart),
            'statement_descriptor' => $this->getStatementDescriptor(),
        ];

        if (!$this->mercadopago->isTestUser()) {
            $preference['sponsor_id'] = $this->getSponsorId();
        }

        return $preference;
    }

    /**
     * Get all cart items
     *
     * @param $cart
     * @param bool $custom
     * @param int|float|null $percent
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCartItems($cart, $custom = false, $percent = null)
    {
        $items = [];
        $products = $cart->getProducts();

        // Verify country for round
        $round = $this->mpuseful->getRound();

        // Products
        foreach ($products as $product) {
            /** @var array|false $image */
            $image = Image::getCover($product['id_product']);
            $image_id = is_array($image) ? (string) $image['id_image'] : '';
            $image_product = new Product($product['id_product'], false, Context::getContext()->language->id);

            $link = new Link();
            $link_image = $link->getImageLink($image_product->link_rewrite, $image_id, '');

            $product_price = $product['price_wt'];
            if ($percent != null) {
                $product_price = (float) $product_price - ($product_price * ($percent / 100));
            }

            $item = [
                'id' => $product['id_product'],
                'title' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => $round ? Tools::ps_round($product_price) : $product_price,
                'picture_url' => 'https://' . $link_image,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => strip_tags($product['description_short']),
                '_scope_key' => (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'],
                '_product_id' => (int) $product['id_product'],
                '_product_attribute_id' => (int) $product['id_product_attribute'],
                '_reduction_applies' => !empty($product['reduction_applies']),
            ];

            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $items[] = $item;
        }

        // Wrapping cost
        $wrapping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
        if ($wrapping_cost > 0) {
            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $item = [
                'title' => 'Wrapping',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round($wrapping_cost) : $wrapping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Wrapping service used by store',
                '_scope_key' => 'service_wrapping',
            ];

            $items[] = $item;
        }

        // Discounts are distributed among positive items because Mercado Pago does not accept negative prices.
        $discounts = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        // Shipping cost
        $shipping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            if ($custom != true) {
                $item['currency_id'] = $this->module->context->currency->iso_code;
            }

            $item = [
                'title' => 'Shipping',
                'quantity' => 1,
                'unit_price' => $round ? Tools::ps_round($shipping_cost) : $shipping_cost,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Shipping service used by store',
                '_scope_key' => 'service_shipping',
            ];

            $items[] = $item;
        }

        if ($discounts > 0) {
            $items = $this->applyPrestashopDiscounts($items, $cart, $discounts);
        }

        $cartTotal = (float) $cart->getOrderTotal(true);
        if ($percent !== null) {
            $checkoutDiscount = (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) * ((float) $percent / 100);
            $cartTotal -= $checkoutDiscount;
        }

        $cartTotal = $this->roundPrice($cartTotal);
        $items = $this->reconcileItemsToTotal($items, $cartTotal);
        $this->logDiscountTotals($cart, $discounts, $cartTotal, $items);

        foreach ($items as &$item) {
            foreach (array_keys($item) as $key) {
                if (strpos($key, '_') === 0) {
                    unset($item[$key]);
                }
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Distribute PrestaShop cart rules over the items they actually affect when that scope is available.
     * The final reconciliation remains authoritative because fixed reductions and combined rules are not
     * always attributable to individual product lines by PrestaShop.
     *
     * @param array $items
     * @param Cart $cart
     * @param float $discountTotal
     *
     * @return array
     */
    protected function applyPrestashopDiscounts($items, $cart, $discountTotal)
    {
        $remaining = $this->priceToUnits($discountTotal);
        $filters = [
            CartRule::FILTER_ACTION_GIFT => 'gift',
            CartRule::FILTER_ACTION_SHIPPING => 'shipping',
            CartRule::FILTER_ACTION_REDUCTION => 'reduction',
        ];

        foreach ($filters as $filter => $type) {
            $cartRules = $cart->getCartRules($filter, false);
            foreach ($cartRules as $cartRule) {
                if ($remaining <= 0) {
                    break 2;
                }

                $ruleUnits = min($remaining, $this->priceToUnits((float) $cartRule['value_real']));
                if ($ruleUnits <= 0) {
                    continue;
                }

                $eligibleKeys = $this->getCartRuleItemKeys($cartRule, $cart, $type, $items);
                $applied = $this->reduceItemsByUnits($items, $ruleUnits, $eligibleKeys);
                $remaining -= $applied;
            }
        }

        if ($remaining > 0) {
            $remaining -= $this->reduceItemsByUnits($items, $remaining, $this->getProductItemKeys($items));
        }

        if ($remaining > 0) {
            $this->reduceItemsByUnits($items, $remaining, []);
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getCartRuleItemKeys($cartRule, $cart, $type, $items)
    {
        if ($type == 'shipping') {
            return ['service_shipping'];
        }

        if ($type == 'gift') {
            $keys = [];
            foreach ($items as $item) {
                if (!isset($item['_product_id']) || $item['_product_id'] != (int) $cartRule['gift_product']) {
                    continue;
                }
                if ((int) $cartRule['gift_product_attribute'] === 0
                    || $item['_product_attribute_id'] == (int) $cartRule['gift_product_attribute']) {
                    $keys[] = $item['_scope_key'];
                }
            }

            return empty($keys) ? ['__no_eligible_item__'] : array_values(array_unique($keys));
        }

        $reductionProduct = (int) $cartRule['reduction_product'];
        if ($reductionProduct > 0) {
            $keys = [];
            foreach ($items as $item) {
                if (isset($item['_product_id']) && $item['_product_id'] == $reductionProduct) {
                    $keys[] = $item['_scope_key'];
                }
            }

            return array_values(array_unique($keys));
        }

        if ($reductionProduct == -1) {
            $cheapestKey = null;
            $cheapestPrice = null;
            foreach ($items as $item) {
                if (!isset($item['_product_id']) || (!empty($cartRule['reduction_exclude_special']) && $item['_reduction_applies'])) {
                    continue;
                }
                if ($cheapestPrice === null || $item['unit_price'] < $cheapestPrice) {
                    $cheapestPrice = $item['unit_price'];
                    $cheapestKey = $item['_scope_key'];
                }
            }

            return $cheapestKey === null ? ['__no_eligible_item__'] : [$cheapestKey];
        }

        if ($reductionProduct == -2 && isset($cartRule['obj'])) {
            $selectedProducts = $cartRule['obj']->checkProductRestrictionsFromCart($cart, true, false);

            return is_array($selectedProducts) && !empty($selectedProducts)
                ? array_values(array_unique($selectedProducts))
                : ['__no_eligible_item__'];
        }

        $keys = [];
        foreach ($items as $item) {
            if (!isset($item['_product_id']) || (!empty($cartRule['reduction_exclude_special']) && $item['_reduction_applies'])) {
                continue;
            }
            $keys[] = $item['_scope_key'];
        }

        return array_values(array_unique($keys));
    }

    /**
     * Reduce items proportionally, splitting a quantity into at most two lines when a rounding remainder exists.
     * Every emitted unit keeps a value of at least one minor currency unit.
     *
     * @return int Applied amount in minor currency units
     */
    protected function reduceItemsByUnits(&$items, $amountUnits, $eligibleKeys)
    {
        $eligibleIndexes = [];
        $totalCapacity = 0;
        foreach ($items as $index => $item) {
            $scopeKey = isset($item['_scope_key']) ? $item['_scope_key'] : 'item_' . $index;
            if (!empty($eligibleKeys) && !in_array($scopeKey, $eligibleKeys)) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            $capacity = max(0, ($this->priceToUnits($item['unit_price']) - 1) * $quantity);
            if ($capacity > 0) {
                $eligibleIndexes[$index] = $capacity;
                $totalCapacity += $capacity;
            }
        }

        $amountUnits = min((int) $amountUnits, $totalCapacity);
        if ($amountUnits <= 0) {
            return 0;
        }

        $allocations = [];
        $allocated = 0;
        foreach ($eligibleIndexes as $index => $capacity) {
            $allocation = min($capacity, (int) floor($amountUnits * $capacity / $totalCapacity));
            $allocations[$index] = $allocation;
            $allocated += $allocation;
        }

        $remainder = $amountUnits - $allocated;
        foreach ($eligibleIndexes as $index => $capacity) {
            if ($remainder <= 0) {
                break;
            }
            $extra = min($remainder, $capacity - $allocations[$index]);
            $allocations[$index] += $extra;
            $remainder -= $extra;
        }

        $result = [];
        foreach ($items as $index => $item) {
            if (empty($allocations[$index])) {
                $result[] = $item;
                continue;
            }
            foreach ($this->splitItemWithReduction($item, $allocations[$index]) as $splitItem) {
                $result[] = $splitItem;
            }
        }
        $items = $result;

        return $amountUnits;
    }

    /**
     * @return array
     */
    protected function splitItemWithReduction($item, $reductionUnits)
    {
        $quantity = (int) $item['quantity'];
        $unitPrice = $this->priceToUnits($item['unit_price']);
        $baseReduction = (int) floor($reductionUnits / $quantity);
        $remainder = $reductionUnits % $quantity;
        $result = [];

        if ($quantity - $remainder > 0) {
            $baseItem = $item;
            $baseItem['quantity'] = $quantity - $remainder;
            $baseItem['unit_price'] = $this->unitsToPrice($unitPrice - $baseReduction);
            $result[] = $baseItem;
        }

        if ($remainder > 0) {
            $remainderItem = $item;
            $remainderItem['quantity'] = $remainder;
            $remainderItem['unit_price'] = $this->unitsToPrice($unitPrice - $baseReduction - 1);
            $result[] = $remainderItem;
        }

        return $result;
    }

    /**
     * Make the sum of item lines exactly equal to the payment amount.
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected function reconcileItemsToTotal($items, $expectedTotal)
    {
        $expectedUnits = $this->priceToUnits($expectedTotal);
        if ($expectedUnits <= 0) {
            throw new PrestaShopException('Mercado Pago cannot create a payment preference with a non-positive total.');
        }

        $currentUnits = $this->getItemsTotalUnits($items);
        if ($currentUnits > $expectedUnits) {
            $this->reduceItemsByUnits($items, $currentUnits - $expectedUnits, []);
        } elseif ($currentUnits < $expectedUnits) {
            $items = $this->increaseItemsByUnits($items, $expectedUnits - $currentUnits);
        }

        if ($this->getItemsTotalUnits($items) != $expectedUnits) {
            MPLog::generate('Unable to represent the cart total with positive Mercado Pago item prices', 'error');
            throw new PrestaShopException('Mercado Pago items could not be reconciled with the cart total.');
        }

        foreach ($items as $item) {
            if ($this->priceToUnits($item['unit_price']) <= 0) {
                throw new PrestaShopException('Mercado Pago item prices must be positive.');
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function increaseItemsByUnits($items, $amountUnits)
    {
        if (empty($items)) {
            return $items;
        }

        $index = count($items) - 1;
        $item = $items[$index];
        $quantity = (int) $item['quantity'];
        $unitPrice = $this->priceToUnits($item['unit_price']);
        $baseIncrease = (int) floor($amountUnits / $quantity);
        $remainder = $amountUnits % $quantity;
        array_splice($items, $index, 1, $this->splitIncreasedItem($item, $unitPrice, $baseIncrease, $remainder));

        return $items;
    }

    /**
     * @return array
     */
    protected function splitIncreasedItem($item, $unitPrice, $baseIncrease, $remainder)
    {
        $quantity = (int) $item['quantity'];
        $result = [];
        if ($quantity - $remainder > 0) {
            $baseItem = $item;
            $baseItem['quantity'] = $quantity - $remainder;
            $baseItem['unit_price'] = $this->unitsToPrice($unitPrice + $baseIncrease);
            $result[] = $baseItem;
        }
        if ($remainder > 0) {
            $remainderItem = $item;
            $remainderItem['quantity'] = $remainder;
            $remainderItem['unit_price'] = $this->unitsToPrice($unitPrice + $baseIncrease + 1);
            $result[] = $remainderItem;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getProductItemKeys($items)
    {
        $keys = [];
        foreach ($items as $item) {
            if (isset($item['_product_id'])) {
                $keys[] = $item['_scope_key'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return int
     */
    protected function getItemsTotalUnits($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $this->priceToUnits($item['unit_price']) * (int) $item['quantity'];
        }

        return $total;
    }

    /**
     * @return int
     */
    protected function priceToUnits($price)
    {
        return (int) round((float) $price * $this->getPriceFactor());
    }

    /**
     * @return float|int
     */
    protected function unitsToPrice($units)
    {
        return $this->getPriceFactor() == 1 ? (int) $units : $units / $this->getPriceFactor();
    }

    /**
     * @return int
     */
    protected function getPriceFactor()
    {
        return $this->mpuseful->getRound() ? 1 : 100;
    }

    /**
     * @return float|int
     */
    protected function roundPrice($price)
    {
        return $this->unitsToPrice($this->priceToUnits($price));
    }

    protected function logDiscountTotals($cart, $discounts, $expectedTotal, $items)
    {
        if ($discounts <= 0) {
            return;
        }

        $rules = [];
        foreach ($this->getPrestashopCartRules($cart) as $cartRule) {
            $rules[] = sprintf(
                '#%d %s%s: %s',
                (int) $cartRule['id_cart_rule'],
                empty($cartRule['code']) ? '' : $cartRule['code'] . ' - ',
                $cartRule['name'],
                $this->roundPrice($cartRule['amount'])
            );
        }

        MPLog::generate(sprintf(
            'Cart %d discount totals - Original subtotal: %s; Prestashop discount: %s; Expected final total: %s; '
            . 'MercadoPago items total: %s; Applied cart rules: %s',
            (int) $cart->id,
            $this->roundPrice($cart->getOrderTotal(true) + $discounts),
            $this->roundPrice($discounts),
            $expectedTotal,
            $this->unitsToPrice($this->getItemsTotalUnits($items)),
            empty($rules) ? 'none' : implode(' | ', $rules)
        ));
    }

    /**
     * @return array
     */
    protected function getPrestashopCartRules($cart)
    {
        $result = [];
        foreach ($cart->getCartRules(CartRule::FILTER_ACTION_ALL, false) as $cartRule) {
            $result[] = [
                'id_cart_rule' => (int) $cartRule['id_cart_rule'],
                'name' => $cartRule['name'],
                'code' => $cartRule['code'],
                'amount' => $this->roundPrice($cartRule['value_real']),
                'free_shipping' => (bool) $cartRule['free_shipping'],
            ];
        }

        return $result;
    }

    public function getStatementDescriptor()
    {
        if ($this->settings['MERCADOPAGO_INVOICE_NAME'] == null) {
            return '';
        }

        return $this->settings['MERCADOPAGO_INVOICE_NAME'];
    }

    /**
     * Get notification url
     *
     * @param $cart
     *
     * @return string|void
     */
    public function getNotificationUrl($cart)
    {
        $customer = new Customer((int) $cart->id_customer);

        if (!strrpos($this->getSiteUrl(), 'localhost')) {
            $notification_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
                '?fc=module&module=mercadopago&controller=notification&' .
                'checkout=' . $this->checkout . '&customer=' . $customer->secure_key .
                '&notification=ipn&&source_news=ipn';

            return $notification_url;
        }
    }

    /**
     * Get site url
     *
     * @return string
     */
    public function getSiteUrl()
    {
        $url = Tools::htmlentitiesutf8('https://' . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__);

        return $url;
    }

    /**
     * Get return url
     *
     * @param mixed $cart
     * @param string $typeReturn
     *
     * @return string
     */
    public function getReturnUrl($cart, $typeReturn)
    {
        $return_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=standardvalidation&' .
            'checkout=standard&cart_id=' . $cart->id . '&typeReturn=' . $typeReturn;

        return $return_url;
    }

    /**
     * Get sponsor_id for preference
     *
     * @return mixed
     */
    public function getSponsorId()
    {
        $sponsor_id = $this->mpuseful->getCountryConfigs($this->settings['MERCADOPAGO_SITE_ID']);

        return $sponsor_id;
    }

    /**
     * Get customer email
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getCustomerEmail()
    {
        $customer_fields = Context::getContext()->customer->getFields();
        $customer_email = $customer_fields['email'];

        return $customer_email;
    }

    /**
     * Get customer data for custom checkout
     *
     * @return array|null
     *
     * @throws PrestaShopException
     */
    public function getCustomCustomerData($cart)
    {
        $customer = Context::getContext()->customer;
        if (!(empty($customer->firstname) && empty($customer->lastname))) {
            $customer_fields = $customer->getFields();
            $address_invoice = new Address((int) $cart->id_address_invoice);

            $customer_data = [
                'first_name' => $customer_fields['firstname'],
                'last_name' => $customer_fields['lastname'],
                'phone' => [
                    'area_code' => '-',
                    'number' => $address_invoice->phone,
                ],
                'address' => [
                    'zip_code' => $address_invoice->postcode,
                    'street_name' => $this->buildStreetName($address_invoice),
                    'street_number' => '-',
                ],
            ];

            return $customer_data;
        }

        return null;
    }

    /**
     * Get shippment address
     *
     * @return array
     */
    public function getShipmentAddress($cart)
    {
        $address_shipment = new Address((int) $cart->id_address_delivery);

        $shipment = [
            'receiver_address' => [
                'zip_code' => $address_shipment->postcode,
                'street_name' => $this->buildStreetName($address_shipment),
                'street_number' => '-',
                'apartment' => '-',
                'floor' => '-',
                'city_name' => $address_shipment->city,
            ],
        ];

        return $shipment;
    }

    /**
     * Get items description
     *
     * @return array|string
     */
    public function getPreferenceDescription($cart)
    {
        $items = [];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $items[] = $product['name'] . ' x ' . $product['quantity'];
        }

        $items = implode(', ', $items);

        return $items;
    }

    /**
     * Create the array for medatada informations
     *
     * @return array
     */
    public function getInternalMetadata($cart)
    {
        $address_invoice = new Address((int) $cart->id_address_invoice);
        $customer_fields = Context::getContext()->customer->getFields();
        $is_logged = Context::getContext()->customer->isLogged();

        $internal_metadata = [
            'details' => '',
            'platform' => MPRestCli::PLATFORM_ID,
            'platform_version' => _PS_VERSION_,
            'module_version' => MP_VERSION,
            'sponsor_id' => $this->getSponsorId(),
            'collector' => $this->settings['MERCADOPAGO_SELLER_ID'],
            'test_mode' => $this->validateSandboxMode(),
            'site' => $this->settings['MERCADOPAGO_SITE_ID'],
            'basic_settings' => $this->getStandardCheckoutSettings(),
            'custom_settings' => $this->getCustomCheckoutSettings(),
            'ticket_settings' => $this->getTicketCheckoutSettings(),
            'pix_settings' => $this->getPixCheckoutSettings(),
            'seller_website' => Tools::getShopDomainSsl(true, true),
            'billing_address' => [
                'zip_code' => $address_invoice->postcode,
                'street_name' => $address_invoice->address1 . ' - ' . $address_invoice->address2,
                'street_number' => '-',
                'city_name' => $address_invoice->city,
                'country_name' => $address_invoice->country,
            ],
            'user' => [
                'registered_user' => $is_logged ? 'yes' : 'no',
                'user_email' => $is_logged ? $customer_fields['email'] : ' ',
                'user_registration_date' => $is_logged ? $customer_fields['date_add'] : ' ',
            ],
        ];

        $discountTotal = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discountTotal > 0) {
            $internal_metadata['prestashop_discount'] = [
                'prestashop_cart_id' => (int) $cart->id,
                'subtotal_before_discounts' => $this->roundPrice($cart->getOrderTotal(true) + $discountTotal),
                'products_subtotal_before_discounts' => $this->roundPrice(
                    $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS)
                ),
                'shipping_before_discounts' => $this->roundPrice(
                    $cart->getOrderTotal(true, Cart::ONLY_SHIPPING)
                ),
                'discount_total' => $this->roundPrice($discountTotal),
                'discounts' => $this->getPrestashopCartRules($cart),
                'total_after_discounts' => $this->roundPrice($cart->getOrderTotal(true)),
            ];
        }

        return $internal_metadata;
    }

    /**
     * Save payments primary info on mp_transaction table
     *
     * @param mixed $cart
     * @param mixed $notification_url
     *
     * @return void
     */
    public function saveCreatePreferenceData($cart, $notification_url)
    {
        $mp_module = $this->getOrUpdateMpModule();
        $mp_transaction = new MPTransaction();
        $count = $mp_transaction->where('cart_id', '=', $cart->id)->count();

        if ($count == 0) {
            $mp_transaction->create(
                [
                    'total' => $cart->getOrderTotal(),
                    'cart_id' => $cart->id,
                    'customer_id' => $cart->id_customer,
                    'mp_module_id' => $mp_module['id_mp_module'],
                    'notification_url' => $notification_url,
                    'is_payment_test' => $this->validateSandboxMode(),
                ]
            );
        } else {
            $mp_transaction->where('cart_id', '=', $cart->id)->update(
                [
                    'total' => $cart->getOrderTotal(),
                    'customer_id' => $cart->id_customer,
                    'notification_url' => $notification_url,
                    'is_payment_test' => $this->validateSandboxMode(),
                ]
            );
        }
    }

    /**
     * Get mp module id to save mp transactions
     *
     * @return MPModule
     */
    public function getOrUpdateMpModule()
    {
        $count = (new MPModule())->where('version', '=', MP_VERSION)->count();
        if ($count) {
            return (new MPModule())->where('version', '=', MP_VERSION)->get();
        }

        $old_mp = (new MPModule())->orderBy('id_mp_module', 'desc')->get();
        $old_mp = (new MPModule())->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(['updated' => true]);

        (new MPModule())->create(['version' => MP_VERSION]);

        return (new MPModule())->where('version', '=', MP_VERSION)->get();
    }

    /**
     * Get validate sandbox mode
     *
     * @return bool
     */
    public function validateSandboxMode()
    {
        if ($this->settings['MERCADOPAGO_PROD_STATUS'] == true) {
            return false;
        }

        return true;
    }

    /**
     * Create and set ticket discount on CartRule()
     *
     * @param mixed $cart
     * @param $discount
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setCartRule($cart, $discount)
    {
        $mp_code = 'MPDISCOUNT' . $cart->id;
        $store_name = Configuration::get('PS_LANG_DEFAULT');
        $discount_name = $this->module->l('Mercado Pago discount applied to cart ' . $cart->id);

        $cart_rule = new CartRule();
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y') + 10));
        $cart_rule->name[$store_name] = $discount_name;
        $cart_rule->quantity = 1;
        $cart_rule->code = $mp_code;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->reduction_percent = $discount;
        $cart_rule->reduction_amount = 0;
        $cart_rule->active = true;
        $cart_rule->save();

        $cart->addCartRule($cart_rule->id);

        $this->cart_rule = $cart_rule->id;
    }

    /**
     * Disable cart rule when buyer completes purchase
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function disableCartRule()
    {
        $cart_rule = new CartRule($this->cart_rule);
        $cart_rule->active = false;
        $cart_rule->save();
    }

    /**
     * Delete cart rule if an error occurs
     *
     * @return bool
     */
    public function deleteCartRule()
    {
        $result_cart_rule = $this->ps_cart_rule->where('id_cart_rule', '=', $this->cart_rule)->destroy();
        $result_cart_rule_rule = $this->ps_cart_rule_rule->where('id_cart_rule', '=', $this->cart_rule)->destroy();

        if ($result_cart_rule == false || $result_cart_rule_rule == false) {
            $this->disableCartRule();
            MPLog::generate('Failed to delete cart_rule from database', 'error');

            return false;
        }

        return true;
    }

    /**
     * Redirect if any errors occurs
     *
     * @return void
     */
    public function redirectError()
    {
        Tools::redirect('index.php?controller=order&step=3&typeReturn=failure');
    }

    /**
     * Get plugin settings on database
     *
     * @return mixed
     */
    public function getMercadoPagoSettings()
    {
        // localization
        $this->settings['MERCADOPAGO_SITE_ID'] = Configuration::get('MERCADOPAGO_SITE_ID');
        $this->settings['MERCADOPAGO_SELLER_ID'] = Configuration::get('MERCADOPAGO_SELLER_ID');
        $this->settings['MERCADOPAGO_COUNTRY_LINK'] = Configuration::get('MERCADOPAGO_COUNTRY_LINK');

        // credentials
        $this->settings['MERCADOPAGO_PROD_STATUS'] = Configuration::get('MERCADOPAGO_PROD_STATUS');
        $this->settings['MERCADOPAGO_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        $this->settings['MERCADOPAGO_SANDBOX_PUBLIC_KEY'] = Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
        $this->settings['MERCADOPAGO_SANDBOX_ACCESS_TOKEN'] = Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');

        // store info
        $this->settings['MERCADOPAGO_INVOICE_NAME'] = Configuration::get('MERCADOPAGO_INVOICE_NAME');
        $this->settings['MERCADOPAGO_INTEGRATOR_ID'] = Configuration::get('MERCADOPAGO_INTEGRATOR_ID');
        $this->settings['MERCADOPAGO_STORE_CATEGORY'] = Configuration::get('MERCADOPAGO_STORE_CATEGORY');

        // standard checkout
        $this->settings['MERCADOPAGO_AUTO_RETURN'] = Configuration::get('MERCADOPAGO_AUTO_RETURN');
        $this->settings['MERCADOPAGO_INSTALLMENTS'] = Configuration::get('MERCADOPAGO_INSTALLMENTS');
        $this->settings['MERCADOPAGO_STANDARD_MODAL'] = Configuration::get('MERCADOPAGO_STANDARD_MODAL');
        $this->settings['MERCADOPAGO_STANDARD_CHECKOUT'] = Configuration::get('MERCADOPAGO_STANDARD_CHECKOUT');
        $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'] = Configuration::get('MERCADOPAGO_EXPIRATION_DATE_TO');
        $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] = Configuration::get('MERCADOPAGO_STANDARD_BINARY_MODE');

        // custom checkout
        $this->settings['MERCADOPAGO_CUSTOM_CHECKOUT'] = Configuration::get('MERCADOPAGO_CUSTOM_CHECKOUT');
        $this->settings['MERCADOPAGO_CUSTOM_WALLET_BUTTON'] = Configuration::get('MERCADOPAGO_CUSTOM_WALLET_BUTTON');
        $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'] = Configuration::get('MERCADOPAGO_CUSTOM_DISCOUNT');
        $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] = Configuration::get('MERCADOPAGO_CUSTOM_BINARY_MODE');

        // ticket checkout
        $this->settings['MERCADOPAGO_TICKET_CHECKOUT'] = Configuration::get('MERCADOPAGO_TICKET_CHECKOUT');
        $this->settings['MERCADOPAGO_TICKET_DISCOUNT'] = Configuration::get('MERCADOPAGO_TICKET_DISCOUNT');
        $this->settings['MERCADOPAGO_TICKET_EXPIRATION'] = Configuration::get('MERCADOPAGO_TICKET_EXPIRATION');

        // pix checkout
        $this->settings['MERCADOPAGO_PIX_CHECKOUT'] = Configuration::get('MERCADOPAGO_PIX_CHECKOUT');
        $this->settings['MERCADOPAGO_PIX_DISCOUNT'] = Configuration::get('MERCADOPAGO_PIX_DISCOUNT');
        $this->settings['MERCADOPAGO_PIX_EXPIRATION'] = Configuration::get('MERCADOPAGO_PIX_EXPIRATION');

        // pse checkout
        $this->settings[PseCheckout::PSE_CHECKOUT_NAME] = Configuration::get(PseCheckout::PSE_CHECKOUT_NAME);
        $this->settings[PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME] = Configuration::get(PseCheckout::PSE_CHECKOUT_DISCOUNT_NAME);

        return $this->settings;
    }

    /**
     * Get standard checkout settings for metadata
     *
     * @return array
     */
    public function getStandardCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_STANDARD_CHECKOUT'] == '' ? false : true;
        $settings['modal'] = $this->settings['MERCADOPAGO_STANDARD_MODAL'] == '' ? false : true;
        $settings['auto_return'] = $this->settings['MERCADOPAGO_AUTO_RETURN'] == '' ? false : true;
        $settings['binary_mode'] = $this->settings['MERCADOPAGO_STANDARD_BINARY_MODE'] == '' ? false : true;
        $settings['installments'] = $this->settings['MERCADOPAGO_INSTALLMENTS'];
        $settings['expiration_date_to'] = $this->settings['MERCADOPAGO_EXPIRATION_DATE_TO'];

        return $settings;
    }

    /**
     * Get custom checkout settings for metadata
     *
     * @return array
     */
    public function getCustomCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_CUSTOM_CHECKOUT'] == '' ? false : true;
        $settings['wallet_button'] = $this->settings['MERCADOPAGO_CUSTOM_WALLET_BUTTON'] == '' ? false : true;
        $settings['discount'] = (float) $this->settings['MERCADOPAGO_CUSTOM_DISCOUNT'];
        $settings['binary_mode'] = $this->settings['MERCADOPAGO_CUSTOM_BINARY_MODE'] == '' ? false : true;

        return $settings;
    }

    /**
     * Get ticket checkout settings for metadata
     *
     * @return array
     */
    public function getTicketCheckoutSettings()
    {
        $settings = [];

        $settings['active'] = $this->settings['MERCADOPAGO_TICKET_CHECKOUT'] == '' ? false : true;
        $settings['discount'] = (float) $this->settings['MERCADOPAGO_TICKET_DISCOUNT'];
        $settings['expiration_date_to'] = $this->settings['MERCADOPAGO_TICKET_EXPIRATION'];

        return $settings;
    }

    /**
     * Get pix checkout settings for metadata
     *
     * @return array
     */
    public function getPixCheckoutSettings()
    {
        $settings = [
            'active' => !($this->settings['MERCADOPAGO_PIX_CHECKOUT'] == ''),
            'discount' => (float) $this->settings['MERCADOPAGO_PIX_DISCOUNT'],
            'expiration_date_to' => $this->settings['MERCADOPAGO_PIX_EXPIRATION'],
        ];

        return $settings;
    }

    /**
     * Generate preference logs
     *
     * @param array $preference
     * @param mixed $checkout
     *
     * @return void
     */
    public function generateLogs($preference, $checkout)
    {
        $logs = [
            'cart_id' => $preference['external_reference'],
            'cart_total' => $preference['transaction_amount'],
            'payment_method' => $preference['payment_method_id'],
            'cart_items' => $preference['additional_info']['items'],
            'metadata' => array_diff_key($preference['metadata'], array_flip(['collector'])),
        ];

        $encodedLogs = json_encode($logs);
        MPLog::generate($checkout . ' preference logs: ' . $encodedLogs);
    }

    /**
     * build street name
     *
     * @param object $address_data
     *
     * @return string
     */
    public function buildStreetName($address_data)
    {
        $address = $address_data->address1 . ' - ' .
        $address_data->address2 . ' - ' .
        $address_data->city . ' - ' .
        $address_data->country;

        return $address;
    }
}
