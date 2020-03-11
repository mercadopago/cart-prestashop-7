{*
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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2020 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<h2 class="ticket-return-title">
    {l s='Thank you for your purchase!' mod='mercadopago'}
    <img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.png" class="img-fluid header-mp-logo" id="logo-confirmation" />
</h2>

<table class="std">
    <thead>
    <tr>
        <th>{l s='Product' mod='mercadopago'}</th>
        <th>{l s='Price' mod='mercadopago'}</th>
        <th>{l s='Qty' mod='mercadopago'}</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$order_products item=product}
        <tr>
            <td>{$product.product_name}</td>
            <td>
                {if $use_taxes}
                    {displayPrice price=$product.total_price_tax_incl}
                {else}
                    {displayPrice price=$product.total_price_tax_excl}
                {/if}
            </td>
            <td>{$product.product_quantity}</td>
        </tr>
    {/foreach}
    </tbody>
    <tfoot>
    <tr>
        <td style="text-align:right">
            {l s='Products Total' mod='mercadopago'}
        </td>
        <td colspan="2">
            {if $use_taxes}
                {displayPrice price=$order->total_products_wt}
            {else}
                {displayPrice price=$order->total_products}
            {/if}

        </td>
    </tr>
    <tr>
        <td style="text-align:right">
            {l s='Shipping' mod='mercadopago'}
        </td>
        <td colspan="2">
            {if $use_taxes}
                {displayPrice price=$order->total_shipping_tax_incl}
            {else}
                {displayPrice price=$order->total_shipping_tax_excl}
            {/if}

        </td>
    </tr>
    {if $order->total_discounts != '0.00'}
        <tr>
            <td style="text-align:right">
                {l s='Discounts' mod='mercadopago'}
            </td>
            <td colspan="2">-
                {if $use_taxes}
                    {displayPrice price=$order->total_discounts_tax_incl}
                {else}
                    {displayPrice price=$order->total_discounts_tax_excl}
                {/if}
            </td>
        </tr>
    {/if}
    {if $use_taxes}
        <tr>
            <td style="text-align:right">
                {l s='Taxes Paid' mod='mercadopago'}
            </td>
            <td colspan="2">
                {$taxamt = $order->total_paid_tax_incl - $order->total_paid_tax_excl}
                {displayPrice price=$taxamt}

            </td>
        </tr>
    {/if}
    <tr>
        <td style="text-align:right">
            {l s='TOTAL' mod='mercadopago'}
        </td>
        <td colspan="2">
            {if $use_taxes}
                {displayPrice price=$order->total_paid_tax_incl}
            {else}
                {displayPrice price=$order->total_paid_tax_excl}
            {/if}
        </td>
    </tr>
    </tfoot>
</table>



{if $ticket_url != null}
    <div class="row">
        <div class="col-md-12">
            <div class="mp-ticket-return">

                <h2 class="ticket-return-title">{l s='Thank you for your purchase! We are awaiting the payment.' mod='mercadopago'}</h2>

                <div class="row mp-ticket-frame">
                    <div class="col-md-12 mp-hg-100">
                        <iframe src="{$ticket_url|escape:'htmlall':'UTF-8'}" id="ticket-frame" name="ticket-frame">
                            <div class="lightbox" id="text">
                                <div class="box">
                                    <div class="content">
                                        <div class="processing">
                                            <span>{l s='Processing...' mod='mercadopago'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </iframe>
                    </div>

                    <div class="col-md-12">
                        <a href="{$ticket_url|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-primary">
                            {l s='Print ticket' mod='mercadopago'}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
{/if}