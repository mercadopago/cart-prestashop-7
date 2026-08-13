{**
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
 *}
{if $checkout_type == 'pix'}
    <div class="form-group">
        <div class="col-xs-12 col-md-12 col-12 mp-px-0 mp-m-col">
            <div class="mp-pt-5">
                <label class="mp-pix-text-label">
                    <strong> {l s='Pay %s via Pix to guarantee your purchase.' sprintf=[{$total_paid_amount|escape:'htmlall':'UTF-8'}] mod='mercadopago'} </strong>
                    <a class="mp-link-checkout-custom" href="#pix-order">
                        <strong> {l s='Check Pix code.' mod='mercadopago'} </strong>
                    </a>
                </label>
            </div>
        </div>
    </div>
{/if}
