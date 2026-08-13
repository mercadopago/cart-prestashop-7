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
