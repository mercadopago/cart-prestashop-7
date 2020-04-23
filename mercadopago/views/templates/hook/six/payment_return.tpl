{*
* 2007-2020 PrestaShop
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
<div class="row mp_thanks">
  <div class="col-xs-12">
    <h2 class="ticket-return-title">
      {l s='Thank you for your purchase!' mod='mercadopago'}
      {*<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.png" class="img-fluid header-mp-logo" id="logo-confirmation"/>*}
    </h2>
  </div>
</div>

<div class="row mp_ticket">
  <div class="col-xs-12">
    {if $ticket_url != null}
      <div class="mp-ticket-return">
        <div class="row mp-ticket-frame-six">
          <div class="col-md-12 mp-hg-500">
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
    {/if}
  </div>
</div>

<div class="mp_container">
    <div class="row mp_header">
        <div class="col-xs-7 tg-f79y"><b>{l s='Product' mod='mercadopago'}</b></div>
        <div class="col-xs-3 tg-f79y"><b>{l s='Price' mod='mercadopago'}</b></div>
        <div class="col-xs-2 tg-f79y"><b>{l s='Qty' mod='mercadopago'}</b></div>
    </div>
    {foreach from=$order_products item=product}
      <div class="row mp_detail">
          <div class="col-xs-7">{$product.product_name}</div>
          <div class="col-xs-3">
            {if $use_taxes}
              {displayPrice price=$product.total_price_tax_incl}
            {else}
              {displayPrice price=$product.total_price_tax_excl}
            {/if}
          </div>
          <div class="col-xs-2">{$product.product_quantity}</div>
      </div>
    {/foreach}
    <div class="row mp_subtotal">
        <div class="col-xs-7 col-md-8">{l s='Subtotal' mod='mercadopago'}</div>
        <div class="col-xs-5 col-md-4">
          {if $use_taxes}
            {displayPrice price=$order->total_products_wt}
          {else}
            {displayPrice price=$order->total_products}
          {/if}
        </div>
    </div>
    <div class="row mp_discount">
        <div class="col-xs-7 col-md-8">{l s='Discount' mod='mercadopago'}</div>
        <div class="col-xs-5 col-md-4">
          {if $use_taxes}
            {displayPrice price=$order->total_discounts_tax_incl}
          {else}
            {displayPrice price=$order->total_discounts_tax_excl}
          {/if}
        </div>
    </div>
    <div class="row mp_shipping">
        <div class="col-xs-7 col-md-8">{l s='Shipping' mod='mercadopago'}</div>
        <div class="col-xs-5 col-md-4">
          {if $use_taxes}
            {displayPrice price=$order->total_shipping_tax_incl}
          {else}
            {displayPrice price=$order->total_shipping_tax_excl}
          {/if}
        </div>
    </div>
    <div class="row mp_total">
        <div class="col-xs-7 col-md-8"><b>{l s='TOTAL' mod='mercadopago'}</b></div>
        <div class="col-xs-5 col-md-4">
          <b>
            {if $use_taxes}
              {displayPrice price=$order->total_paid_tax_incl}
            {else}
              {displayPrice price=$order->total_paid_tax_excl}
            {/if}
          </b>
        </div>
    </div>
</div>
