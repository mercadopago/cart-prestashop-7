{*
* 2007-2021 PrestaShop
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
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

// ----- pix configuration form ------ //
//pix form header
document.querySelector("#module_form_7 .panel").style.borderTopLeftRadius = 0;
var form_pix_header_prepend = document.createElement("div");
var form_pix_header = document.querySelector("#module_form_7 .panel .panel-heading");
form_pix_header.style.height = "auto";

form_pix_header_prepend.innerHTML = "<ul class='mp-checkout-list'>\
    <li><span>{l s='Ofereça um meio de pagamento instântaneo, disponível 24h por dia.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Receba o dinheiro das suas vendas em até 10 segundos.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Tenha à disposição taxas menores que as do boleto e dos cartões.' mod='mercadopago'}</span></li>\
</ul>";
form_pix_header.insertBefore(form_pix_header_prepend, form_pix_header.firstChild);

{if $pix_enabled == true} 

    var form_pix_prepend = document.createElement("div");
    var form_pix = document.querySelector("#module_form_7 .panel .form-wrapper");
    var form_pix_group = document.querySelectorAll("#module_form_7 .panel .form-wrapper .form-group");

    form_pix_prepend.innerHTML = "<div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>{l s='Ative ou desative o Pix na sua loja e defina o prazo para pagamento da compra após envio do código.' mod='mercadopago'}</h4>\
        </div>\
    </div>";
    form_pix.insertBefore(form_pix_prepend, form_pix.firstChild);

    //advanced configuration
    var form_pix_append = "<div class='row mp-pb-25'>\
        <div class='col-md-12 mp-pt-15'>\
            <p class='mp-text-credenciais'>\
                <b>{l s='Importante:' mod='mercadopago'}</b> {l s='Você pode gerenciar a(s) chave(s) Pix que cadastrou na sua conta sempre pelo app do Mercado Pago.' mod='mercadopago'}\
            </p>\
        </div>\
    </div>\
    <div class='panel-heading mp-panel-advanced-config'>\
        <i class='icon-cogs'></i> {l s='Advanced Configuration' mod='mercadopago'}\
        <span class='mp-btn-collapsible' id='header_plus_pix' style='display:block'>+</span>\
        <span class='mp-btn-collapsible' id='header_less_pix' style='display:none'>-</span>\
    </div>\
    <div class='row text-pix-advanced'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body mp-pix-input-collapsible'>{l s='Ofereça descontos em pagamentos com Pix. A porcentagem definida será descontada do valor total da compra.' mod='mercadopago'}</h4>\
        </div>\
    </div>";

    for (i=0; i < form_pix_group.length; i++) {
        form_pix_group[i].querySelector("p").style.width = "400px";
        if(i == 1){
            form_pix_group[i].insertAdjacentHTML('afterend', form_pix_append);
        }
        if(i > 1) {
            form_pix_group[i].classList.add("mp-pix-input-collapsible");
        }
    }

    var style_collapsible_pix = false;
    var header_plus_pix = document.querySelector("#header_plus_pix");
    var header_less_pix = document.querySelector("#header_less_pix");
    var form_pix_collapsible = document.querySelector("#module_form_7 .panel .mp-panel-advanced-config");
    var form_pix_collapsible_body = document.querySelectorAll(".mp-pix-input-collapsible");
    var form_pix_collapsible_footer = document.querySelector("#module_form_7 .panel .panel-footer");

    form_pix_collapsible_footer.style.marginTop = "-2px";

    form_pix_collapsible.onclick = function(){
        if(style_collapsible_pix == false){
            style_collapsible_pix = true;
            header_less_pix.style.display = "block";
            header_plus_pix.style.display = "none";
            form_pix_collapsible_footer.style.marginTop = "15px";
            document.querySelector(".text-pix-advanced").style.paddingTop = "20px";
            document.querySelector(".text-pix-advanced").style.paddingBottom = "25px";

            for(i=0; i<form_pix_collapsible_body.length; i++){
                form_pix_collapsible_body[i].style.display = "block";
            }
        } else {
            style_collapsible_pix = false;
            header_less_pix.style.display = "none";
            header_plus_pix.style.display = "block";
            form_pix_collapsible_footer.style.marginTop = "-2px";
            document.querySelector(".text-pix-advanced").style.paddingTop = "0px";
            document.querySelector(".text-pix-advanced").style.paddingBottom = "0px";

            for(i=0; i<form_pix_collapsible_body.length; i++){
                form_pix_collapsible_body[i].style.display = "none";
            }
        }
    }

    var saveButton = document.querySelector('#module_form_submit_btn_7');
    saveButton.style.display = "block";

{else}

    var form_pix_prepend = document.createElement("div");
    var form_pix = document.querySelector("#module_form_7 .panel .form-wrapper");
    var form_pix_group = document.querySelectorAll("#module_form_7 .panel .form-wrapper .form-group");

    form_pix_prepend.innerHTML = "<div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>{l s='Para receber pagamentos via Pix, você precisa ter uma ou mais chaves cadastradas no Mercado Pago.' mod='mercadopago'}</h4>\
            <h4 class='mp-title-checkout-body'>{l s='Siga os passos abaixo:' mod='mercadopago'}</h4>\
        </div>\
    </div>\
    <div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <ol>\
                <li><span class='mp-text-credenciais'>{l s='Baixe o app do Mercado Pago no seu celular;' mod='mercadopago'}</span></li>\
                <li><span class='mp-text-credenciais'>{l s='No menu lateral esquerdo, acesse Seu Perfil e vá em ' mod='mercadopago'}<b>{l s='Suas chaves Pix;' mod='mercadopago'}</b></span></li>\
                <li><span class='mp-text-credenciais'>{l s='Insira os dados da(s) chave(s) Pix que você deseja cadastrar e finalize o processo;' mod='mercadopago'}</span></li>\
                <li><span class='mp-text-credenciais'>{l s='Volte aqui no admin da sua loja PrestaShop, ' mod='mercadopago'}<b>{l s='na aba Pix' mod='mercadopago'}</b>{l s=', para seguir com a configuração do meio de pagamento.' mod='mercadopago'}</span></li>\
            </ol>\
        </div>\
    </div>\
    <div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <p class='mp-text-credenciais'>\
                <b>{l s='Importante' mod='mercadopago'}</b>: {l s='Você pode gerenciar a(s) chave(s) Pix que cadastrou na sua conta sempre pelo app do Mercado Pago.' mod='mercadopago'}\
            </p>\
        </div>\
    </div>\
    <hr>\
    <div class='row mp-pb-25 mp-pt-25'>\
        <div class='col-md-12'>\
            <ul>\
                <li>\
                    <span class='mp-text-credenciais'>\
                        {l s='Por enquanto, o Banco Central do Brasil funciona de segunda a sexta-feira, das 9h às 18h.' mod='mercadopago'}<br>\
                        {l s='Cadastros feitos fora desse horário serão confirmados no próximo dia útil.' mod='mercadopago'}\
                        <a href='https://www.mercadopago.com.br/pix/' target='_blank'>{l s='Ler mais sobre Pix.' mod='mercadopago'}</a>\
                    </span>\
                </li>\
            </ul>\
        </div>\
    </div>\
    <div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <ul>\
                <li>\
                    <span class='mp-text-credenciais'>\
                        {l s='Caso já tenha cadastrado chaves no Mercado Pago e não esteja conseguindo ativar o Pix no checkout,' mod='mercadopago'}<br>\
                        <a href='https://www.mercadopago.com.br/developers/pt/support/center/contact' target='_blank'>{l s='clique aqui.' mod='mercadopago'}</a>\
                    </span>\
                </li>\
            </ul>\
        </div>\
    </div>";
    form_pix.insertBefore(form_pix_prepend, form_pix.firstChild);

    var saveButton = document.querySelector('#module_form_submit_btn_7');
    saveButton.style.display = "none";
{/if}