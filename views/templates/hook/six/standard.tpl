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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<a href="{$redirect|escape:'html':'UTF-8'}" class="redirect-checkout-six">
    <div class="row frame-checkout-six">
        <div class="title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}" />
            <p>{l s='Quiero pagar con Mercado Pago sin costo adicional' mod='mercadopago'}</p>
        </div>

        <p class="subtitle-checkout-six">{l s='Usa el medio de pago que prefieras' mod='mercadopago'}</p>

        {if count($credit) != 0}
        <div class="col-xs-4 col-md-4">
            <div class="frame-tarjetas">
                <p class="subtitle-checkout">
                    {l s='Tarjetas de crédito' mod='mercadopago'} 
                    <span class="badge-checkout">
                        {l s='Hasta' mod='mercadopago'} {$installments|escape:'html':'UTF-8'} {l s='cuotas' mod='mercadopago'}
                    </span>
                </p>

                {foreach $credit as $tarjeta}
                <img src="{$tarjeta['image']|escape:'html':'UTF-8'}"class="img-fluid img-tarjetas" />
                {/foreach}
            </div>
        </div>
        {/if}

        {if count($debit) != 0}
        <div class="col-xs-4 col-md-4">
            <div class="frame-tarjetas">
                <p class="subtitle-checkout">{l s='Tarjetas de débito' mod='mercadopago'}</p>

                {foreach $debit as $tarjeta}
                <img src="{$tarjeta['image']|escape:'html':'UTF-8'}"class="img-fluid img-tarjetas" />
                {/foreach}
            </div>
        </div>
        {/if}

        {if count($ticket) != 0}
        <div class="col-xs-4 col-md-4">
            <div class="frame-tarjetas">
                <p class="subtitle-checkout">{l s='Pagos en efectivo' mod='mercadopago'}</p>

                {foreach $ticket as $tarjeta}
                <img src="{$tarjeta['image']|escape:'html':'UTF-8'}"class="img-fluid img-tarjetas" />
                {/foreach}
            </div>
        </div>
        {/if}
    </div>

    {if $modal == true && $preference != ""}
        <script src="{$modal_link|escape:'html':'UTF-8'}" data-public-key="{$public_key|escape:'html':'UTF-8'}" data-preference-id="{$preference|escape:'html':'UTF-8'}"></script>
    {/if}
</a>

{if $modal == true && $preference != ""}
<script>
    var mercadopago_button = document.querySelector('.mercadopago-button');
    var mercadopago_redirect = document.querySelector('.redirect-checkout-six');
    
    mercadopago_button.style.display = 'none';
    mercadopago_redirect.setAttribute('href', '#');

    mercadopago_redirect.onclick = function () {
        mercadopago_button.click();
        return false;
    }
</script>
{/if}