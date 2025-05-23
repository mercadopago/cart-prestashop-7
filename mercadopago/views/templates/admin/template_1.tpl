{*
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
*}

<div class="panel mp-panel-mercadopago">
	<div class="row header-mercadopago">
        <div class="mp-left-details">
            <h2 class="mp-title-checkout-header">{l s='Design the best payment experience for your customers' mod='mercadopago'}</h2>
        </div>
        <div class="mp-right-details">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/mpinfo_logo.png" class="img-fluid header-mp-logo" id="payment-logo" />
        </div>
	</div>

	<hr />

	<div class="mercadopago-content">
		<div class="row">
            <div class="col-md-12">
                <h4 class="mp-title-checkout-body">{l s='Follow these steps and maximize your conversion:' mod='mercadopago'}</h4>
            </div>
		</div>

        <div class="row mp-pt-15">
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $access_token != '' && $sandbox_access_token != ''}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">1</p>
                {/if}

                <p class="mp-text-checkout-body">
                    {l s='Get your' mod='mercadopago'}
                    {l s='credentials' mod='mercadopago'}
                    {l s='in your Mercado Pago account.' mod='mercadopago'}
                </p>
            </div>

            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $seller_homolog == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">2</p>
                {/if}

                <p class="mp-text-checkout-body">
                    {l s='Approve your account to securely charge your customers.' mod='mercadopago'}
                </p>
            </div>

            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $standard_test == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">3</p>
                {/if}

                <p class="mp-text-checkout-body">
                    {l s='Choose the' mod='mercadopago'}
                    {l s='payment methods' mod='mercadopago'}
                    {l s='available in your store.' mod='mercadopago'}
                </p>
            </div>

            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $count_test != 0}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">4</p>
                {/if}

                <p class="mp-text-checkout-body">
                    {l s='Activate the' mod='mercadopago'}
                    {l s='Sandbox' mod='mercadopago'}
                    {l s='test environment to test your store.' mod='mercadopago'}
                </p>
            </div>

            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $sandbox_status == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">5</p>
                {/if}
                <p class="mp-text-checkout-body">{l s='Deactivate it if you’re ready to receive payments.' mod='mercadopago'}</p>
            </div>
		</div>

		<div class="row mp-pt-30">
            <div class="col-md-6">
                <p class="text-branded lists-how-configure">
                    {l s='Credentials are the keys we provide you to integrate quickly and securely.' mod='mercadopago'}
                    {l s='You must have an approved account in Mercado Pago to collect on your website.' mod='mercadopago'}
                    {l s='You don`t need to know how to design or program to activate us in your store. ' mod='mercadopago'}
                </p>
            </div>
		</div>
	</div>
</div>

<!-- forms rendered via class from mercadopago.php -->
{html_entity_decode($country_form|escape:'html':'UTF-8')}
{html_entity_decode($credentials|escape:'html':'UTF-8')}

{if $access_token != '' && $public_key != ''  && $sandbox_access_token != '' && $sandbox_public_key != ''}

    {if $sandbox_status != true || $seller_homolog == true}
        <div style="display: none">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {else}
        <div style="display: block">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {/if}

    {html_entity_decode($store_form|escape:'html':'UTF-8')}

    <!-- Nav tabs checkouts -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#standard_checkout" role="tab" data-toggle="tab">{l s='Checkout Pro' mod='mercadopago'}</a></li>
        <li><a href="#custom_checkout" role="tab" data-toggle="tab" id="tab-custom">{l s='Custom Checkout' mod='mercadopago'}</a></li>
        <li><a href="#ticket_checkout" role="tab" data-toggle="tab">{l s='Ticket checkout' mod='mercadopago'}</a></li>
        {if $country_link == 'mlb' && $country_id == 'mlb'}
            <li><a href="#pix_checkout" role="tab" data-toggle="tab">{l s='Pix' mod='mercadopago'}</a></li>
        {/if}
        {if $country_link == 'mco' && $country_id == 'mco'}
            <li><a href="#pse_checkout" role="tab" data-toggle="tab">{l s='Pse checkout' mod='mercadopago'}</a></li>
        {/if}
    </ul>

    <!-- Tab panes checkouts -->
    <div class="tab-content">
        <div class="tab-pane active" id="standard_checkout">{html_entity_decode($standard_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="custom_checkout">{html_entity_decode($custom_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="ticket_checkout">{html_entity_decode($ticket_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="pix_checkout">{html_entity_decode($pix_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="pse_checkout">{html_entity_decode($pse_form|escape:'html':'UTF-8')}</div>
    </div>

    {if $sandbox_status != true}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Test yor store' mod='mercadopago'}
        </div>

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mp-title-checkout-body">{l s='Everything set up? Test your store' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row mp-pt-15">
                <div class="col-md-12">
                    <p class="mp-text-credenciais">{l s='Visit your store and simulate payments to check that everything is fine.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">{l s='I want to test my sales' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {else}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Comienza a vender' mod='mercadopago'}
        </div>

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mp-title-checkout-body">{l s='You have already gone to production!' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row mp-pt-15">
                <div class="col-md-12">
                    <p class="mp-text-credenciais">{l s='All ready for the takeoff of your sales. Now bring your' mod='mercadopago'}</p>
                    <p class="mp-text-credenciais">{l s='customers to offer them the best online shopping experience with Mercado Pago.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">{l s='Visit my store' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {/if}
{/if}

<hr class="hr-mp-modal">
<div class="row">
    <div class="col-md-8">
        {l s='Something`s wrong?' mod='mercadopago'}

        {if $country_link == 'mlb'}
          <a href="https://www.mercadopago.com.br/developers/pt/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {else}
          <a href="https://www.mercadopago.com.ar/developers/es/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {/if}
    </div>
</div>
<br>
