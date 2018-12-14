{**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @author    MercadoPago
*  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of MercadoPago
*}
<form id="module_form" class="defaultForm form-horizontal" action="{$currentIndex|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
<div class="panel">
	{if $show}
	<div class="form-group border-none">
		<div class="col-lg-2 logo-wrapper">
			<img src="{$thisPath|escape:'htmlall':'UTF-8'}views/img/mercadopago_125X125.jpg" class="payment-config-logo">
		</div>
		<label class="payment-label col-lg-3">
			Checkout Standard
		</label>
		<div class="col-lg-3">
{* 			<div class="col-lg-4 control-label switch-label">{$label.active|escape:'htmlall':'UTF-8'}</div> *}
			<div class="col-lg-6 switch prestashop-switch fixed-width-lg">
				<input type="radio" name="MERCADOPAGO_STARDAND_ACTIVE" id="MERCADOPAGO_STARDAND_ACTIVE_on" value="1"  {if ($mercadoPagoActive == 1)}checked="checked"{/if}>
				<label for="MERCADOPAGO_STARDAND_ACTIVE_on">{$button.yes|escape:'htmlall':'UTF-8'}</label>

				<input type="radio" name="MERCADOPAGO_STARDAND_ACTIVE" id="MERCADOPAGO_STARDAND_ACTIVE_off" value="0" {if empty($mercadoPagoActive)}checked="checked"{/if}>
				<label for="MERCADOPAGO_STARDAND_ACTIVE_off">{$button.no|escape:'htmlall':'UTF-8'}</label>
				<a class="slide-button btn"></a>
			</div>
		</div>
		<div class="col-lg-4">
			<label class="general-tooltip">
				{l s='When enabled, all single payment methods will be disabled.' d='Modules.MercadoPago.Admin'}
			</label>
		</div>
		<div style="clear: both"></div>
	</div>
	<div style="clear: both"></div>
	{else}
		<div class="alert alert-danger">
	  		<strong>{l s='Danger!' d='Modules.MercadoPago.Admin'}</strong> {l s='Please, fill your credentials to enable the module.' d='Modules.MercadoPago.Admin'}
		</div>
	{/if}
</div>
{if $show}
<div class="panel panel-default">
	<div class="panel-heading">{l s='Payment Methods' d='Modules.MercadoPago.Admin'}</div>
	<div class="alert alert-info">
	  {l s='Enable and disable your payment methods.' d='Modules.MercadoPago.Admin'}
	</div>
	{foreach from=$payments key=sort item=payment}
		<div class="form-group">
			<div class="col-lg-2 logo-wrapper">
				<img src="{$payment.brand|escape:'htmlall':'UTF-8'}" alt="{$payment.title|escape:'htmlall':'UTF-8'}">
			</div>
<!-- 				<label class="payment-label col-lg-3">
				{$payment.title|escape:'htmlall':'UTF-8'}
				{if !empty($payment.thumbnail)}
					<img src="{$thisPath|escape:'htmlall':'UTF-8'}views/img/questionmark.png" alt="{$payment.type|escape:'htmlall':'UTF-8'}" data-toggle="tooltip" title="{$payment.tooltips|escape:'htmlall':'UTF-8'}" class="payment-config-tooltip">
				{/if}
			</label> -->
			<div class="col-lg-3">
				<!--<div class="col-lg-4 control-label switch-label">
					{if ($payment.active == 1)}
						{$label.active|escape:'htmlall':'UTF-8'}
					{else}
						{$label.disable|escape:'htmlall':'UTF-8'}
					{/if}
				</div>-->
				<div class="col-lg-6 switch prestashop-switch fixed-width-lg">
					<input type="radio" name="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE" id="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE_on" value="1" {if ($payment.active == 1)}checked="checked"{/if}>
					<label for="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE_on">{$button.yes|escape:'htmlall':'UTF-8'}</label>

					<input type="radio" name="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE" id="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE_off" value="0" {if empty($payment.active)}checked="checked"{/if}>
					<label for="MERCADOPAGO_{$payment.id|escape:'htmlall':'UTF-8'}_ACTIVE_off">{$button.no|escape:'htmlall':'UTF-8'}</label>
					<a class="slide-button btn"></a>
				</div>
			</div>
			<div style="clear: both"></div>
		</div>
		<div style="clear: both"></div>
	{/foreach}
</div>

<div class="row">
	<div class="col-md-12 text-center"> 
		<button type="submit" value="1" name="btnSubmitPaymentConfig" class="btn btn-primary btn-lg">
			{l s='Save' mod='mercadopago'}
		</button>
	</div>
</div>

{/if}
</form>

<script type="text/javascript">
	{if $mercadoPagoActive}
		$("#MERCADOPAGO_STARDAND_ACTIVE_on").attr("checked", true);
	{/if}
</script>
