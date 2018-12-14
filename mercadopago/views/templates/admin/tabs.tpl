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
<link href="{$backOfficeCssUrl|escape:'htmlall':'UTF-8'}" rel="stylesheet" type="text/css">
<link href="{$marketingCssUrl|escape:'htmlall':'UTF-8'}" rel="stylesheet" type="text/css">

{if $message}
	{if $message.success}
		{assign var="alert" value="alert-success"}
	{else}
		{assign var="alert" value="alert-danger"}
	{/if}
	<div class="bootstrap">
		<div class="module_confirmation conf confirm alert {$alert|escape:'htmlall':'UTF-8'}">
			<button type="button" class="close" data-dismiss="alert">×</button>
			{$message.text|escape:'htmlall':'UTF-8'}
		</div>
	</div>
{/if}

<div class="mercadopago-tabs">
	{if $tabs}
		<nav>
		{foreach $tabs as $tab}
			<a class="tab-title {if isset($selectedTab) && $tab.id==$selectedTab}active{/if}" href="#" id="{$tab.id|escape:'htmlall':'UTF-8'}" data-target="#mercadopago-tabs-{$tab.id|escape:'htmlall':'UTF-8'}">{$tab.title|escape:'htmlall':'UTF-8'}</a>
		{/foreach}
		</nav>
		<div class="content">
		{foreach $tabs as $tab}
			<div class="tab-content" id="mercadopago-tabs-{$tab.id|escape:'htmlall':'UTF-8'}" style="display:{if isset($selectedTab) && $tab.id==$selectedTab}block{else}none{/if}">
                {html_entity_decode($tab.content|escape:'htmlall':'UTF-8')}
			</div>
		{/foreach}
		</div>
	{/if}
</div>
<script type='text/javascript' src="{$backOfficeJsUrl|escape:'htmlall':'UTF-8'}"></script>