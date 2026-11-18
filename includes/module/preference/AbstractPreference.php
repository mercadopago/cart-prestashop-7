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
    /**
     * Decimal places used to build every monetary value sent to Mercado Pago.
     * Sites that transact in integer currency (CLP/COP) use zero decimals.
     */
    public const PRICE_DECIMALS = 2;
    public const PRICE_DECIMALS_INTEGER_CURRENCY = 0;

    /**
     * Scope keys used to address the non-product lines when distributing a cart rule.
     */
    public const SCOPE_KEY_SHIPPING = 'service_shipping';
    public const SCOPE_KEY_WRAPPING = 'service_wrapping';
    public const SCOPE_KEY_NO_ELIGIBLE_ITEM = '__no_eligible_item__';

    public $module;
    public $checkout;
    public $settings;
    public $mpuseful;
    public $cart_rule;
    public $mercadopago;
    public $ps_cart_rule;
    public $ps_cart_rule_rule;

    /** @var int|null Original checkout identity survives native replacement of Context cart. */
    private $paymentHoldCartId;

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

    /** One issuance policy for every checkout, before payload/discount side effects. */
    protected function initiatePayment($cart, string $kind, callable $build, ?callable $prepare = null)
    {
        require_once __DIR__ . '/../notification/CartPaymentInitiation.php';

        $this->paymentHoldCartId = (int) $cart->id;

        return (new CartPaymentInitiation($this->mercadopago))->run($cart, $kind, $build, function (int $preparedRuleId) use ($cart) {
            if ($preparedRuleId > 0) {
                $rule = new CartRule($preparedRuleId);
                if (Validate::isLoadedObject($rule)) {
                    if ($rule->code !== 'MPDISCOUNT' . (int) $cart->id) {
                        throw new RuntimeException('Interrupted checkout discount identity changed');
                    }
                    $deleted = $rule->delete();
                    if ($deleted !== true && $deleted !== 1) {
                        throw new RuntimeException('Could not remove an interrupted checkout discount');
                    }
                }
                $cart->clearCache();
                Cache::clean('Cart::getCartRules_*');
            }
        }, function () {
            return (int) $this->cart_rule;
        }, $prepare);
    }

    public function getPaymentHoldUrl(): string
    {
        $context = Context::getContext();

        return $context->link->getModuleLink('mercadopago', 'standardvalidation', [
            'cart_id' => $this->paymentHoldCartId ?? (int) $context->cart->id, 'payment_hold' => 1,
        ], true);
    }

    public function redirectPaymentHold()
    {
        Tools::redirect($this->getPaymentHoldUrl());
    }

    /** A post-issuance failure must preserve its discount even when logging fails. */
    public function holdIssuedCheckout($cart, ?Throwable $error = null, string $phase = 'response'): void
    {
        $this->paymentHoldCartId = $this->paymentHoldCartId ?? (int) $cart->id;
        $phase = in_array($phase, ['response', 'persistence', 'reconciliation', 'confirmation'], true) ? $phase : 'checkout';
        $causes = [
            'Could not complete checkout order reconciliation' => 'reconciliation_failed',
            'Synchronous checkout did not create a verified order' => 'missing_order',
            'Synchronous checkout order ownership could not be verified' => 'order_ownership',
        ];
        $cause = $error === null ? 'incomplete_issued_response' : ($causes[$error->getMessage()] ?? 'exception');
        $type = 'none';
        if ($error !== null) {
            $type = $error instanceof RuntimeException ? 'runtime_exception' : 'exception';
            if ($error instanceof PDOException || $error instanceof PrestaShopDatabaseException) {
                $type = 'storage_exception';
            } elseif ($error instanceof TypeError) {
                $type = 'type_error';
            } elseif ($error instanceof Error) {
                $type = 'runtime_error';
            }
        }
        try {
            MPLog::generate('Mercado Pago issued checkout failure for cart ' . $this->paymentHoldCartId
                . ' phase=' . $phase . ' cause=' . $cause . ' exception=' . $type, 'error');
        } catch (Throwable $loggingError) {
            // Diagnostic failure must not trigger discount cleanup or another payment.
        }
        $this->redirectPaymentHold();
    }

    public function respondPaymentHold(): void
    {
        header('Content-type: application/json');
        http_response_code(409);
        echo json_encode(['code' => 409, 'payment_confirmation_pending' => true,
            'confirmation_url' => $this->getPaymentHoldUrl()]);
        exit;
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
     * Every discount, except the Wallet Button checkout discount, must already be applied to the
     * cart as a PrestaShop cart rule when this runs: the item prices are derived from the cart,
     * never from a mirrored percentage. Callers are responsible for calling setCartRule() first.
     * Wallet Button deliberately calculates its checkout discount separately and reconciles it
     * after this method, before the CartRule is created during order creation (see the ordering
     * traps in .agents/traps.md).
     *
     * @param $cart
     * @param bool $custom
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCartItems($cart, $custom = false)
    {
        $items = [];
        $products = $cart->getProducts();

        // Products
        foreach ($products as $product) {
            /** @var array|false $image */
            $image = Image::getCover($product['id_product']);
            $image_id = is_array($image) ? (string) $image['id_image'] : '';
            $image_product = new Product($product['id_product'], false, Context::getContext()->language->id);

            $link = new Link();
            $link_image = $link->getImageLink($image_product->link_rewrite, $image_id, '');

            $item = [
                'id' => $product['id_product'],
                'title' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => $this->roundPrice($product['price_wt']),
                'picture_url' => 'https://' . $link_image,
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => strip_tags($product['description_short']),
                // Underscore-prefixed keys are internal to the discount distribution and are
                // stripped before the payload leaves this method.
                '_scope_key' => (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'],
                '_product_id' => (int) $product['id_product'],
                '_product_attribute_id' => (int) $product['id_product_attribute'],
                // CartRule::getContextualValue() chooses the cheapest-product target from the
                // original tax-excluded product price, not from a previously reduced payload line.
                '_comparison_price' => (float) $product['price'],
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
            $items[] = [
                'title' => 'Wrapping',
                'quantity' => 1,
                'unit_price' => $this->roundPrice($wrapping_cost),
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Wrapping service used by store',
                '_scope_key' => self::SCOPE_KEY_WRAPPING,
            ];
        }

        // Shipping cost
        $shipping_cost = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($shipping_cost > 0) {
            $items[] = [
                'title' => 'Shipping',
                'quantity' => 1,
                'unit_price' => $this->roundPrice($shipping_cost),
                'category_id' => $this->settings['MERCADOPAGO_STORE_CATEGORY'],
                'description' => 'Shipping service used by store',
                '_scope_key' => self::SCOPE_KEY_SHIPPING,
            ];
        }

        // Discounts are subtracted from the positive lines instead of being sent as a
        // negative "Discount" item: Mercado Pago rejects a create_preference payload that
        // carries a negative unit_price ("Invalid request parameters").
        $discounts = (float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        if ($discounts > 0) {
            $items = $this->applyPrestashopDiscounts($items, $cart, $discounts);
        }

        $cartTotal = $this->roundPrice($cart->getOrderTotal(true));
        $items = $this->reconcileItemsToTotal($items, $cartTotal);
        $this->logDiscountTotals($cart, $discounts, $cartTotal, $items);

        return $this->stripInternalItemKeys($items);
    }

    /**
     * Remove the internal, underscore-prefixed bookkeeping keys before the items are sent.
     *
     * @param array $items
     *
     * @return array
     */
    protected function stripInternalItemKeys($items)
    {
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
     * Distribute PrestaShop cart rules over the items they actually affect when that scope is
     * available. The final reconciliation remains authoritative because fixed reductions and
     * combined rules are not always attributable to individual product lines by PrestaShop.
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

        // A single cart rule can carry more than one action (free shipping *and* a reduction,
        // for instance) and PrestaShop then lists that same rule — with the same value_real —
        // under every matching action filter. Reading it once with FILTER_ACTION_ALL and
        // applying it in stages keeps a combined rule from being counted twice, which used to
        // skew how much of its value each scope absorbed.
        // Second argument false so PrestaShop does not auto-add rules while we only read them.
        $cartRules = $cart->getCartRules(CartRule::FILTER_ACTION_ALL, false);
        foreach ($cartRules as $cartRule) {
            if ($remaining <= 0) {
                break;
            }

            $ruleUnits = min($remaining, $this->priceToUnits((float) $cartRule['value_real']));
            if ($ruleUnits <= 0) {
                continue;
            }

            $remaining -= $this->applyCartRuleByStages($items, $cart, $cartRule, $ruleUnits);
        }

        // Whatever could not be attributed to a scope falls back to the products, then to any line.
        if ($remaining > 0) {
            $remaining -= $this->reduceItemsByUnits($items, $remaining, $this->getProductItemKeys($items));
        }

        if ($remaining > 0) {
            $this->reduceItemsByUnits($items, $remaining, []);
        }

        return $items;
    }

    /**
     * Apply one cart rule to the item lines, one action at a time: free shipping first, then the
     * gifted product, and finally the reduction scope with whatever value is left. Each stage
     * consumes part of the rule's value, so a rule combining several actions is applied exactly
     * once and each of its actions only reaches the lines it is entitled to.
     *
     * @param array $items passed by reference, rewritten with the reduced lines
     * @param Cart $cart
     * @param array $cartRule
     * @param int $ruleUnits
     *
     * @return int Applied amount in minor currency units
     */
    protected function applyCartRuleByStages(&$items, $cart, $cartRule, $ruleUnits)
    {
        $applied = 0;

        if (!empty($cartRule['free_shipping'])) {
            $stageUnits = $this->reduceItemsByUnits(
                $items,
                $ruleUnits,
                $this->getCartRuleItemKeys($cartRule, $cart, 'shipping', $items)
            );
            $applied += $stageUnits;
            $ruleUnits -= $stageUnits;
        }

        if ($ruleUnits > 0 && isset($cartRule['gift_product']) && (int) $cartRule['gift_product'] > 0) {
            $stageUnits = $this->reduceItemsByUnits(
                $items,
                min($ruleUnits, $this->getGiftValueUnits($cartRule, $cart)),
                $this->getCartRuleItemKeys($cartRule, $cart, 'gift', $items)
            );
            $applied += $stageUnits;
            $ruleUnits -= $stageUnits;
        }

        if ($ruleUnits > 0) {
            $applied += $this->reduceItemsByUnits(
                $items,
                $ruleUnits,
                $this->getCartRuleItemKeys($cartRule, $cart, 'reduction', $items)
            );
        }

        return $applied;
    }

    /**
     * PrestaShop grants one original, tax-included product unit per gift action, even when
     * that product has a larger quantity or an earlier rule already reduced its item price.
     *
     * @param array $cartRule
     * @param Cart $cart
     *
     * @return int
     */
    protected function getGiftValueUnits($cartRule, $cart)
    {
        foreach ($cart->getProducts() as $product) {
            if ((int) $product['id_product'] !== (int) $cartRule['gift_product']) {
                continue;
            }

            if ((int) $cartRule['gift_product_attribute'] === 0
                || (int) $product['id_product_attribute'] === (int) $cartRule['gift_product_attribute']
            ) {
                return max(0, $this->priceToUnits($product['price_wt']));
            }
        }

        return 0;
    }

    /**
     * Resolve which item lines one action ($type) of a cart rule is allowed to reduce.
     *
     * @param array $cartRule
     * @param Cart $cart
     * @param string $type
     * @param array $items
     *
     * @return array
     */
    protected function getCartRuleItemKeys($cartRule, $cart, $type, $items)
    {
        if ($type == 'shipping') {
            return [self::SCOPE_KEY_SHIPPING];
        }

        if ($type == 'gift') {
            return $this->getGiftItemKeys($cartRule, $items);
        }

        // A gift/free-shipping-only rule carries no reduction target of its own.
        $reductionProduct = isset($cartRule['reduction_product']) ? (int) $cartRule['reduction_product'] : 0;

        // > 0: the rule targets one specific product.
        if ($reductionProduct > 0) {
            $keys = [];
            foreach ($items as $item) {
                if (isset($item['_product_id']) && $item['_product_id'] == $reductionProduct
                    && $this->isDiscountableProductItem($cartRule, $item)
                ) {
                    $keys[] = $item['_scope_key'];
                }
            }

            return empty($keys) ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM] : array_values(array_unique($keys));
        }

        // -1: the rule targets the cheapest product of the cart.
        if ($reductionProduct == -1) {
            return $this->getCheapestItemKeys($cartRule, $items);
        }

        // -2: the rule targets the products matched by its own restriction rules.
        if ($reductionProduct == -2 && isset($cartRule['obj'])) {
            $selectedProducts = $cartRule['obj']->checkProductRestrictionsFromCart($cart, true, false);

            $keys = $this->getRestrictedProductItemKeys($selectedProducts, $items);

            return $this->filterDiscountableItemKeys($cartRule, $items, $keys);
        }

        return $this->getDiscountableProductKeys($cartRule, $items);
    }

    /**
     * Map PrestaShop restriction results to the module's item scope keys.
     *
     * PrestaShop returns product restrictions as <product-id>-<attribute-id>. For rules such as
     * category or manufacturer restrictions it deliberately returns <product-id>-0, meaning all
     * combinations of that product. The module item list carries the actual attribute id, so
     * forwarding the PrestaShop value directly would miss product variations and incorrectly
     * fall back to reducing unrelated lines.
     *
     * @param mixed $selectedProducts
     * @param array $items
     *
     * @return array
     */
    protected function getRestrictedProductItemKeys($selectedProducts, $items)
    {
        if (!is_array($selectedProducts) || empty($selectedProducts)) {
            return [self::SCOPE_KEY_NO_ELIGIBLE_ITEM];
        }

        $keys = [];
        foreach ($selectedProducts as $selectedProduct) {
            if (!is_string($selectedProduct)) {
                continue;
            }

            $parts = explode('-', $selectedProduct);
            if (count($parts) !== 2 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
                continue;
            }

            $productId = (int) $parts[0];
            $attributeId = (int) $parts[1];
            foreach ($items as $item) {
                if (!isset($item['_product_id']) || (int) $item['_product_id'] !== $productId) {
                    continue;
                }

                if ($attributeId === 0 || (int) $item['_product_attribute_id'] === $attributeId) {
                    $keys[] = $item['_scope_key'];
                }
            }
        }

        return empty($keys) ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM] : array_values(array_unique($keys));
    }

    /**
     * @param array $cartRule
     * @param array $items
     *
     * @return array
     */
    protected function getGiftItemKeys($cartRule, $items)
    {
        $keys = [];
        foreach ($items as $item) {
            if (!isset($item['_product_id']) || $item['_product_id'] != (int) $cartRule['gift_product']) {
                continue;
            }

            if ((int) $cartRule['gift_product_attribute'] === 0
                || $item['_product_attribute_id'] == (int) $cartRule['gift_product_attribute']
            ) {
                $keys[] = $item['_scope_key'];
            }
        }

        return empty($keys) ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM] : array_values(array_unique($keys));
    }

    /**
     * @param array $cartRule
     * @param array $items
     *
     * @return array
     */
    protected function getCheapestItemKeys($cartRule, $items)
    {
        $cheapestKey = null;
        $cheapestPrice = null;
        foreach ($items as $item) {
            if (!$this->isDiscountableProductItem($cartRule, $item)) {
                continue;
            }

            $comparisonPrice = isset($item['_comparison_price']) ? $item['_comparison_price'] : $item['unit_price'];
            if ($cheapestPrice === null || $comparisonPrice < $cheapestPrice) {
                $cheapestPrice = $comparisonPrice;
                $cheapestKey = $item['_scope_key'];
            }
        }

        return $cheapestKey === null ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM] : [$cheapestKey];
    }

    /**
     * @param array $cartRule
     * @param array $items
     *
     * @return array
     */
    protected function getDiscountableProductKeys($cartRule, $items)
    {
        $keys = [];
        foreach ($items as $item) {
            if ($this->isDiscountableProductItem($cartRule, $item)) {
                $keys[] = $item['_scope_key'];
            }
        }

        return empty($keys) ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM] : array_values(array_unique($keys));
    }

    /**
     * Keep a Cart Rule's product scope aligned with reduction_exclude_special.
     *
     * @param array $cartRule
     * @param array $items
     * @param array $keys
     *
     * @return array
     */
    protected function filterDiscountableItemKeys($cartRule, $items, $keys)
    {
        if (in_array(self::SCOPE_KEY_NO_ELIGIBLE_ITEM, $keys)) {
            return [self::SCOPE_KEY_NO_ELIGIBLE_ITEM];
        }

        $discountableKeys = [];
        foreach ($items as $item) {
            if (in_array($item['_scope_key'], $keys) && $this->isDiscountableProductItem($cartRule, $item)) {
                $discountableKeys[] = $item['_scope_key'];
            }
        }

        return empty($discountableKeys)
            ? [self::SCOPE_KEY_NO_ELIGIBLE_ITEM]
            : array_values(array_unique($discountableKeys));
    }

    /**
     * A product line is out of reach when the rule excludes items that already have a specific price.
     *
     * @param array $cartRule
     * @param array $item
     *
     * @return bool
     */
    protected function isDiscountableProductItem($cartRule, $item)
    {
        if (!isset($item['_product_id'])) {
            return false;
        }

        return empty($cartRule['reduction_exclude_special']) || empty($item['_reduction_applies']);
    }

    /**
     * Reduce items proportionally, splitting a quantity into at most two lines when a rounding
     * remainder exists. Every emitted unit keeps a value of at least one minor currency unit,
     * because Mercado Pago also rejects zero-priced items.
     *
     * @param array $items passed by reference, rewritten with the reduced lines
     * @param int $amountUnits
     * @param array $eligibleKeys empty means "any line"
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

            $capacity = max(0, ($this->priceToUnits($item['unit_price']) - 1) * (int) $item['quantity']);
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

        // Hand the floor() leftovers to the first lines that still have capacity.
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
     * @param array $item
     * @param int $reductionUnits
     *
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
     * Make the sum of the item lines exactly equal to the amount that will be charged.
     *
     * @param array $items
     * @param float $expectedTotal
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected function reconcileItemsToTotal($items, $expectedTotal)
    {
        $expectedUnits = $this->priceToUnits($expectedTotal);
        if ($expectedUnits <= 0) {
            MPLog::generate(sprintf(
                'Cannot create a Mercado Pago payment preference with a non-positive total - Expected total: %s',
                $expectedTotal
            ), 'error');
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
     * @param array $items
     * @param int $amountUnits
     *
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
     * @param array $item
     * @param int $unitPrice
     * @param int $baseIncrease
     * @param int $remainder
     *
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
     * @param array $items
     *
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
     * @param array $items
     *
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
     * Convert a price to minor currency units (integers), where all the discount math happens.
     *
     * @param float|int|string $price
     *
     * @return int
     */
    protected function priceToUnits($price)
    {
        return (int) round((float) $price * $this->getPriceFactor());
    }

    /**
     * Convert minor currency units back to the value that goes into the payload.
     *
     * The explicit round() is not cosmetic. A price with decimals has no exact binary
     * representation, so any arithmetic on it carries a residue. json_encode() in MPRestCli
     * serializes floats with php.ini's serialize_precision, and on hosts where that is not the
     * default -1 (17 significant digits instead of the shortest round-trip form) an accumulated
     * residue is emitted as "12.330000000000001" instead of "12.33". Rounding to the site's
     * decimals snaps the value back to the closest double of the intended decimal amount, which
     * keeps the serialized text within one ulp of it. It cannot make a fractional double exact,
     * so a 17-digit host may still print a "...93" tail; what it prevents is drift large enough
     * to be read as a different amount. Strings are not an option: the API expects a number here.
     *
     * @param int $units
     *
     * @return float|int
     */
    protected function unitsToPrice($units)
    {
        if ($this->getPriceDecimals() === self::PRICE_DECIMALS_INTEGER_CURRENCY) {
            return (int) $units;
        }

        return round($units / $this->getPriceFactor(), $this->getPriceDecimals());
    }

    /**
     * Decimal places accepted by the Mercado Pago site this store transacts with.
     *
     * @return int
     */
    protected function getPriceDecimals()
    {
        return $this->mpuseful->getRound() ? self::PRICE_DECIMALS_INTEGER_CURRENCY : self::PRICE_DECIMALS;
    }

    /**
     * @return int
     */
    protected function getPriceFactor()
    {
        return (int) pow(10, $this->getPriceDecimals());
    }

    /**
     * Round a price to the site's decimal places. Every monetary value that enters the payload
     * must go through here so no unrounded float reaches json_encode() (see unitsToPrice).
     *
     * @param float|int|string $price
     *
     * @return float|int
     */
    protected function roundPrice($price)
    {
        return $this->unitsToPrice($this->priceToUnits($price));
    }

    /**
     * @param Cart $cart
     * @param float $discounts
     * @param float|int $expectedTotal
     * @param array $items
     *
     * @return void
     */
    protected function logDiscountTotals($cart, $discounts, $expectedTotal, $items)
    {
        if ($discounts <= 0) {
            return;
        }

        $rules = [];
        foreach ($this->getPrestashopCartRules($cart) as $cartRule) {
            $rules[] = sprintf(
                '#%d: %s',
                $cartRule['id_cart_rule'],
                $cartRule['amount']
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
     * @param Cart $cart
     *
     * @return array
     */
    protected function getPrestashopCartRules($cart)
    {
        $result = [];
        foreach ($cart->getCartRules(CartRule::FILTER_ACTION_ALL, false) as $cartRule) {
            $result[] = [
                'id_cart_rule' => (int) $cartRule['id_cart_rule'],
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
     * @return string|null
     */
    public function getNotificationUrl($cart)
    {
        $customer = new Customer((int) $cart->id_customer);

        // The configured shop domain is the only trusted source for this outbound URL. Using
        // Tools::getHttpHost() here would let a forged Host header suppress notifications, while
        // Tools::getShopDomainSsl() would fall back to that same header when configuration is absent.
        $domain = $this->getValidatedShopAuthority(ShopUrl::getMainShopDomainSSL());
        $host = $domain === null ? null : parse_url('https://' . $domain, PHP_URL_HOST);

        if ($domain === null || !is_string($host) || strcasecmp($host, 'localhost') === 0) {
            return null;
        }

        $notification_url = 'https://' . Tools::htmlentitiesutf8($domain) . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=notification&' .
            'checkout=' . $this->checkout . '&customer=' . $customer->secure_key .
            '&notification=ipn&&source_news=ipn';

        return $notification_url;
    }

    /**
     * Get site url
     *
     * Scheme: resolved via `Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()`,
     * the same combined check PrestaShop core itself uses in `Tools::getShopProtocol()` (PPSP-1574,
     * verified against PrestaShop 8.2.7 and 9.0.3 source) — see git history on this method for the
     * two narrower attempts that preceded it.
     *
     * Domain: resolved directly from the current shop's configured `ShopUrl` instead of
     * `Tools::getShopDomainSsl()` or `$_SERVER['HTTP_HOST']` (PPSP-1892, CWE-601). The helper falls
     * back to `Tools::getHttpHost()` when no domain is configured, which would reintroduce the
     * client-controlled Host header into the `callback_url`. HTTPS uses `domain_ssl`; HTTP uses
     * `domain`, and a missing selected domain fails closed instead of consulting request headers.
     *
     * `getNotificationUrl()` follows the same trust boundary independently: it reads the configured
     * SSL domain directly so a request header cannot suppress or redirect payment notifications.
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getSiteUrl()
    {
        $useSsl = Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode();
        $scheme = $useSsl ? 'https://' : 'http://';
        $domain = $useSsl ? ShopUrl::getMainShopDomainSSL() : ShopUrl::getMainShopDomain();
        $domain = $this->getValidatedShopAuthority($domain);

        if ($domain === null) {
            throw new UnexpectedValueException('Unable to build callback URL: shop domain is not configured or valid.');
        }

        $url = Tools::htmlentitiesutf8($scheme . $domain . __PS_BASE_URI__);

        return $url;
    }

    /**
     * Get return url
     *
     * `back_urls` (PPSP-1892, CWE-601): Mercado Pago redirects the customer's browser here
     * after checkout, so this cannot fall back to `Tools::getShopDomainSsl()`/the request's
     * `Host` header the way it did before — see `getSiteUrl()` for the same trust boundary.
     *
     * @param mixed $cart
     * @param string $typeReturn
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getReturnUrl($cart, $typeReturn)
    {
        $return_url = $this->getConfiguredSslShopUrl() . __PS_BASE_URI__ .
            '?fc=module&module=mercadopago&controller=standardvalidation&' .
            'checkout=standard&cart_id=' . $cart->id . '&typeReturn=' . $typeReturn;

        return $return_url;
    }

    /**
     * Resolve the shop's configured HTTPS URL directly from `ShopUrl`, never from request
     * headers, failing closed when no domain is configured (PPSP-1892, CWE-601).
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    private function getConfiguredSslShopUrl()
    {
        $domain = $this->getValidatedShopAuthority(ShopUrl::getMainShopDomainSSL());

        if ($domain === null) {
            throw new UnexpectedValueException('Unable to resolve shop domain: SSL domain is not configured or valid.');
        }

        return 'https://' . Tools::htmlentitiesutf8($domain);
    }

    /**
     * Validate a configured shop domain as a hostname with an optional numeric port.
     *
     * @param mixed $domain
     *
     * @return string|null
     */
    private function getValidatedShopAuthority($domain)
    {
        $authority = trim((string) $domain);
        $parts = $authority === '' ? false : parse_url('https://' . $authority);

        if (!is_array($parts)
            || !isset($parts['host'])
            || filter_var($parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['path'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] === 0)
        ) {
            return null;
        }

        $expectedAuthority = $parts['host'];
        if (isset($parts['port'])) {
            $expectedAuthority .= ':' . $parts['port'];
        }

        return $authority === $expectedAuthority ? $authority : null;
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
            'seller_website' => $this->getConfiguredSslShopUrl(),
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

        // Discounts no longer show up as their own item line, so the breakdown is kept in the
        // metadata to keep chargeback/support investigations able to explain the charged amount.
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
