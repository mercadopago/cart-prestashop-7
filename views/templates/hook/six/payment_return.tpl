{*
* 2007-2024 PrestaShop
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
* @copyright 2007-2024 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<h2 class="ticket-return-title">
    {l s='Thank you for your purchase!' mod='mercadopago'}
    <img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.png" class="img-fluid header-mp-logo" id="logo-confirmation"/>
</h2>

{if $payment['metadata']['checkout_type'] == 'ticket'}
    <div class="mp-ticket-return">
        <div class="row mp-ticket-frame-six">
            <div class="col-md-12 mp-hg-500">
                <iframe src="{$payment['transaction_details']['external_resource_url']|escape:'htmlall':'UTF-8'}" id="ticket-frame" name="ticket-frame">
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
                <a href="{$payment['transaction_details']['external_resource_url']|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-primary">
                    {l s='Print ticket' mod='mercadopago'}
                </a>
            </div>
        </div>
    </div>
{/if}

{if $payment['metadata']['checkout_type'] == 'pix'}
    <div id="pix-order" class="mp-pix-container">
        <div class="mp-pix-confirmation">

            <div class="row">
                <div class="col-xs-12 col-md-12 col-12">
                    <h2>{l s='Almost there! Pay via Pix to complete your purchase' mod='mercadopago'}</h2>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-xs-6 col-md-6 col-6">
                    <div class="mp-pix-container mp-pix-box mp-pix-container-column">
                        <span class="mp-pix-box-title">{l s='Amount' mod='mercadopago'}</span>
                        <span class="mp-pix-box-subtitle mp-pt-5">{Tools::displayPrice($payment['transaction_details']['total_paid_amount']|escape:'htmlall':'UTF-8')}</span>
                    </div>
                </div>
                <div class="col-xs-6 col-md-6 col-6">
                    <div class="mp-pix-container mp-pix-box mp-pix-container-column">
                        <span class="mp-pix-box-title">{l s='Expiration' mod='mercadopago'}</span>
                        <span class="mp-pix-box-subtitle mp-pt-5">{$pix_expiration|escape:'htmlall':'UTF-8'}</span>
                    </div>
                </div>
            </div>

            <div class="mp-pix-container-desktop">
                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <span class="mp-pix-text-subtitle">{l s='Scan this QR code to pay' mod='mercadopago'}</span>
                        <div class="mp-pix-tooltip">
                            <img class="mp-badge-info" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/badge_info_blue.png"/>
                            <span class="mp-pix-tooltip-text">{l s='Before you confirming your purchase, you will see the amount to pay and the seller\'s information.' mod='mercadopago'}</span>
                        </div>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <ol class="mp-pix-text-subtitle-item">
                            <li>{l s='Access your bank or payment app' mod='mercadopago'}</li>
                            <li>{l s='Choose the option to pay via Pix with QR code' mod='mercadopago'}</li>
                            <li>{l s='Scan the following code:' mod='mercadopago'}</li>
                        </ol>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12 mp-pix-container">
                        <img class="mp-pix-qrcode" src="data:image/png;base64, {$payment['point_of_interaction']['transaction_data']['qr_code_base64']|escape:'html':'UTF-8'}" alt="Qr code"/>
                    </div>
                </div>

                <hr>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <h2>{l s='Or pay with Pix code "Copy and Paste"' mod='mercadopago'}</h2>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <p class="mp-pix-text-subtitle-item">{l s='Access your bank or payment app and choose the option to pay via Pix. Then, paste the following code:' mod='mercadopago'}</p>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-8 col-md-8 col-8">
                        <input id="mp-pix-input-code" type="text" class="form-control mp-form-control" value="{$payment['point_of_interaction']['transaction_data']['qr_code']|escape:'html':'UTF-8'}">
                    </div>
                    <div class="col-md-4 col-xs-4 col-4">
                        <button id="mp-pix-copy-code" class="btn btn-primary">{l s='Copy code' mod='mercadopago'}</button>
                    </div>
                </div>
            </div>

            <div class="mp-pix-container-mobile">
                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <h2>{l s='Use Pix Copy and Paste to pay' mod='mercadopago'}</h2>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <ol class="mp-pix-text-subtitle-item">
                            <li>{l s='Access your bank or payments app' mod='mercadopago'}</li>
                            <li>{l s='Choose to pay via Pix' mod='mercadopago'}</li>
                            <li>{l s='Paste the following code:' mod='mercadopago'}</li>
                        </ol>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <input id="mp-pix-input-code" type="text" class="form-control mp-form-control" value="{$payment['point_of_interaction']['transaction_data']['qr_code']|escape:'html':'UTF-8'}">
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <button id="mp-pix-copy-code" class="btn btn-primary">{l s='Copy code' mod='mercadopago'}</button>
                    </div>
                </div>

                <hr>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <h2>{l s='Or pay using QR Code' mod='mercadopago'}</h2>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12">
                        <p class="mp-pix-text-subtitle-item">{l s='Access your bank or payments app and choose to pay via Pix with QR Code. Then, scan the following code:' mod='mercadopago'}</p>
                    </div>
                </div>

                <div class="row mp-pt-25">
                    <div class="col-xs-12 col-md-12 col-12 mp-pix-container">
                        <img class="mp-pix-qrcode" src="data:image/png;base64, {$payment['point_of_interaction']['transaction_data']['qr_code_base64']|escape:'html':'UTF-8'}" alt="Qr code"/>
                    </div>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-xs-12 col-md-12 col-12">
                    <img class="mp-badge-info" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/badge_info_gray.png"/>
                    <span class="mp-pix-text-info">
                        {l s='Pix has a daily transfer limit. Please contact your bank for more information.' mod='mercadopago'}
                    </span>
                </div>
            </div>

        </div>
    </div>

    <script type="text/javascript">
        const text = document.getElementById('mp-pix-input-code');
        const copyButton = document.getElementById('mp-pix-copy-code');

        copyButton.addEventListener('click', () => {
            text.select();
            document.execCommand('copy');
        });
    </script>
{/if}

<div class="row mp-mx-0">
    <div class="col-md-12 mp-pb-20">
        <div class="row">
            <div class="col-md-6 col-sm-6 col-xs-6 mp-table"><b>{l s='Product' mod='mercadopago'}</b></div>
            <div class="col-md-3 col-sm-3 col-xs-3 mp-table mp-align-center"><b>{l s='Price' mod='mercadopago'}</b></div>
            <div class="col-md-3 col-sm-3 col-xs-3 mp-table mp-align-center"><b>{l s='Qty' mod='mercadopago'}</b></div>
        </div>

        {foreach from=$order_products item=product}
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 mp-table">{$product.product_name|escape:'htmlall':'UTF-8'}</div>
                <div class="col-md-3 col-sm-3 col-xs-3 mp-table mp-align-center">
                    {if $use_taxes}
                        {displayPrice price=$product.total_price_tax_incl}
                    {else}
                        {displayPrice price=$product.total_price_tax_excl}
                    {/if}
                </div>
                <div class="col-md-3 col-sm-3 col-xs-3 mp-table mp-align-center">{$product.product_quantity|escape:'htmlall':'UTF-8'}</div>
            </div>
        {/foreach}

        <div class="row">
            <div class="col-md-8 col-sm-8 col-xs-8 mp-table mp-align-right">
                {l s='Subtotal' mod='mercadopago'}
            </div>
            <div class="col-md-4 col-sm-4 col-xs-4 mp-table">
                {if $use_taxes}
                    {displayPrice price=$order->total_products_wt}
                {else}
                    {displayPrice price=$order->total_products}
                {/if}
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-sm-8 col-xs-8 mp-table mp-align-right">
                {l s='Discount' mod='mercadopago'}
            </div>
            <div class="col-md-4 col-sm-4 col-xs-4 mp-table">
                {if $use_taxes}
                    {displayPrice price=$order->total_discounts_tax_incl}
                {else}
                    {displayPrice price=$order->total_discounts_tax_excl}
                {/if}
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-sm-8 col-xs-8 mp-table mp-align-right">
                {l s='Shipping' mod='mercadopago'}
            </div>
            <div class="col-md-4 col-sm-4 col-xs-4 mp-table">
                {if $use_taxes}
                    {displayPrice price=$order->total_shipping_tax_incl}
                {else}
                    {displayPrice price=$order->total_shipping_tax_excl}
                {/if}
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-sm-8 col-xs-8 mp-table mp-align-right">
                <b>{l s='TOTAL' mod='mercadopago'}</b>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-4 mp-table">
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
</div>
