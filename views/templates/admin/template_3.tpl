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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Logging' mod='mercadopago'}
    </div>

    <div class="mercadopago-content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="mp-title-checkout-body">{l s='Here you can see Mercado Pago\'s log.' mod='mercadopago'}</h4>
            </div>
        </div>

        <div class="row mp-pt-15">
            <div class="col-md-12">
                <p class="mp-text-credenciais">
                    {l s='Only for support reasons. Does not share this with unauthorized people!' mod='mercadopago'}
                </p>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12">
                <a href="{$log|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">
                    {l s='See log' mod='mercadopago'}
                </a>
            </div>
        </div>
    </div>
</div>

<hr class="hr-mp-modal">
<div class="row">
    <div class="col-md-8">
        {l s='Something`s wrong?' mod='mercadopago'}

        {if $country_link == 'mlb'}
          <a href="https://www.mercadopago.com.br/developers/pt/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {else}
          <a href="https://www.mercadopago.com.br/developers/es/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {/if}
    </div>
</div>
<br>
