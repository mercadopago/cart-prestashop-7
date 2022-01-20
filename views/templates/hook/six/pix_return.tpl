{*
* 2007-2022 PrestaShop
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
* @copyright 2007-2022 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<div id="pix-order" class="mp-pix-container">
    <div class="mp-pix-confirmation">
        
        <div class="row">
            <div class="col-xs-12 col-md-12 col-12">
                <h2>Quase lá! Pague via Pix para concluir sua compra</h2>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-6 col-md-6 col-6">
                <div class="mp-pix-container mp-pix-box mp-pix-container-column">
                    <span class="mp-pix-box-title">{l s='Valor' mod='mercadopago'}</span>
                    <span class="mp-pix-box-subtitle mp-pt-5">R${$total_paid_amount|escape:'htmlall':'UTF-8'}</span>
                </div>
            </div>
            <div class="col-xs-6 col-md-6 col-6">
                <div class="mp-pix-container mp-pix-box mp-pix-container-column">
                    <span class="mp-pix-box-title">{l s='Vencimento' mod='mercadopago'}</span>
                    <span class="mp-pix-box-subtitle mp-pt-5">{$expiration|escape:'htmlall':'UTF-8'}</span>
                </div>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12">
                <span class="mp-pix-text-subtitle">{l s='Escaneie este código QR para pagar' mod='mercadopago'}</span>
                <div class="mp-pix-tooltip">
                    <img class="mp-badge-info" src="{$badge_info_blue|escape:'html':'UTF-8'}"/>
                    <span class="mp-pix-tooltip-text">{l s='Antes de confirmar a compra, você verá o valor a pagar e as informações do vendedor.' mod='mercadopago'}</span>
                </div>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12">
                <ol class="mp-pix-text-subtitle">
                    <li>{l s='Acesse o seu banco ou app de pagamentos' mod='mercadopago'}</li>
                    <li>{l s='Escolha pagar via Pix com código QR' mod='mercadopago'}</li>
                    <li>{l s='Escaneie o seguinte código:' mod='mercadopago'}</li>
                </ol>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12 mp-pix-container">
                <img class="mp-pix-qrcode" src="data:image/png;base64, {$qr_code_base64|escape:'html':'UTF-8'}" alt="Qr code"/>
            </div>
        </div>

        <hr>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12">
                <h2>{l s='Ou pague com o código Pix Copia e Cola' mod='mercadopago'}</h2>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12">
                <p class="mp-pix-text-subtitle">{l s='Acesse o seu banco ou app de pagamentos e escolha pagar via Pix. Depois, cole o seguinte código:' mod='mercadopago'}</p>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-8 col-md-8 col-8">
                <input id="mp-pix-input-code" type="text" class="form-control mp-form-control" value="{$qr_code|escape:'html':'UTF-8'}">
            </div>
            <div class="col-md-4 col-xs-4 col-4">
                <button id="mp-pix-copy-code" class="btn btn-primary">{l s='Copiar código' mod='mercadopago'}</button>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12 col-md-12 col-12">
                <img class="mp-badge-info" src="{$badge_info_gray|escape:'html':'UTF-8'}"/>
                <span class="mp-pix-text-info">
                    {l s='O Pix possui limite diário de transferência. Consulte o seu banco para mais informações.' mod='mercadopago'}
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