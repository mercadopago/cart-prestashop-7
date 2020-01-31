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

<form action="{$redirect}" method="post" class="custom-checkout-six">
    <div class="row frame-checkout-six">
        <!-- Logo & Title -->
        <div class="title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}"/>
            <p>{l s='Quiero pagar con Checkout Custom' mod='mercadopago'}</p>
        </div>

        <!-- Cards Type -->
        <div class="col-xs-12 col-md-12 col-12 pt-25">
            <a class="link-checkout-custom"
               id="button-show-payments">{l s='Con qué tarjetas puedo pagar' mod='mercadopago'} ⌵ </a>
        </div>
        <div class="col-xs-12 col-md-12 col-12">
            <div class="frame-payments" id="frame-payments">
                {if count($credit) != 0}
                    <p class="subtitle-payments">{l s='Tarjetas de crédito' mod='mercadopago'}</p>
                    {foreach $credit as $tarjeta}
                        <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid img-tarjetas"/>
                    {/foreach}
                {/if}

                {if count($debit) != 0}
                    <p class="subtitle-payments pt-10">{l s='Tarjetas de débito' mod='mercadopago'}</p>
                    {foreach $debit as $tarjeta}
                        <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid img-tarjetas"/>
                    {/foreach}
                {/if}
            </div>
        </div>


        <!-- MP FORM -->
        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <div id="mp-subtitle-div-title" class="col-md-12 col-12 frame-title">
                <p class="subtitle-checkout-six">{l s='Ingresa los datos de tu tarjeta' mod='mercadopago'}</p>
            </div>


            <!-- Input Card number -->
            <div class="row">
                <div class="col-md-12 col-12 pb-10 mp-m-col">
                    <label for="id-card-number" class="pb-5">{l s='Número de Tarjeta' mod='mercadopago'} <em
                                class="mp-required">*</em></label>
                    <input id="id-card-number" data-checkout="cardNumber" type="text"
                           class="form-control mp-form-control" onkeyup="maskInput(this, mcc);" maxlength="24"
                           autocomplete="off"/>
                    <small id="mp-error-205" class="mp-erro-form">{l s='Invalid card number' mod='mercadopago'}</small>
                    <small id="mp-error-E301"
                           class="mp-erro-form mp-error-E301">{l s='Invalid card number' mod='mercadopago'}</small>
                </div>
            </div>


            <!-- Input Name and Surname -->
            <div class="row">
                <div class="col-md-12 col-12 pt-10 mp-m-col">
                    <label for="id-card-holder-name"
                           class="pb-5">{l s='Nombre y apellido del titular de la tarjeta' mod='mercadopago'} <em
                                class="mp-required">*</em></label>
                    <input id="id-card-holder-name" data-checkout="cardholderName" type="text"
                           class="form-control mp-form-control" autocomplete="off"/>
                    <small id="mp-error-221"
                           class="mp-erro-form">{l s='Invalid card holder name' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="row">
                <!-- Input expiration date -->
                <div class="col-md-6 col-6 pt-10 mp-m-col">
                    <label for="id-card-expiration" class="pb-5">{l s='Fecha de vencimiento' mod='mercadopago'} <em
                                class="mp-required">*</em></label>
                    <input id="id-card-expiration" data-checkout="cardExpiration" type="text"
                           class="form-control mp-form-control" autocomplete="off" placeholder="MM/AAAA"
                           onkeyup="maskInput(this, mdate);" maxlength="7"/>
                    <small id="mp-error-208"
                           class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-209"
                           class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-325"
                           class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-326"
                           class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>

                </div>

                <!-- Input Security Code -->
                <div class="col-md-6 col-6 pt-10 mp-m-col">
                    <label for="id-security-code" class="pb-5">{l s='Código de seguridad' mod='mercadopago'} <em
                                class="mp-required">*</em></label>
                    <input id="id-security-code" data-checkout="securityCode" type="text"
                           class="form-control mp-form-control" autocomplete="off"
                           placeholder="{l s='CVV' mod='mercadopago'}" onkeyup="maskInput(this, minteger);"
                           maxlength="4"/>
                    <small class="mp-small pt-5">{l s='Últimos 3 números del dorso' mod='mercadopago'}</small>
                    <small id="mp-error-224"
                           class="mp-erro-form pt-0">{l s='Invalid card holder name' mod='mercadopago'}</small>
                    <small id="mp-error-E302"
                           class="mp-erro-form pt-0">{l s='Invalid card holder name' mod='mercadopago'}</small>
                </div>
            </div>

            <!-- Title installments -->
            <div class="col-md-12 col-12 frame-title">
                <p class="subtitle-checkout-six pt-15">{l s='Cuántas cuotas querés pagar' mod='mercadopago'}</p>
            </div>

            <div class="row">
                <!-- Select issuer -->
                <div id="container-issuers" class="issuers-options col-md-4 col-4 pt-20 mp-m-col">
                    <label for="id-issuers-options"
                           class="issuers-options pb-5">{l s='Banco emisor' mod='mercadopago'}</label>
                    <select id="id-issuers-options"
                            class="issuers-options form-control mp-form-control mp-select pointer noUniform"
                            data-checkout="issuer" name="mercadopago_custom[issuer]" type="text"></select>
                    <small id="id-issuer-status" class="mp-erro-form"></small>
                </div>

                <!-- Select installments -->
                <div id="container-installments" class="col-md-12 col-8 col-md-4 mp-m-col pl-15">
                    <label for="id-installments"
                           class="pb-5">{l s='Seleccione el número de cotas' mod='mercadopago'}</label>
                    <select class="form-control mp-form-control  pointer not_uniform noUniform" id="id-installments"
                            data-no-uniform="true" data-checkout="installments" name="mercadopago_custom[installments]"
                            type="text"></select>
                    <small id="id-installments-status" class="mp-erro-form"></small>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mp-text-cft"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mp-text-tea"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="mp-doc-div-title" class="col-md-12 col-12 frame-title">
                <p class="subtitle-checkout-six">{l s='Ingresá tu número de documento' mod='mercadopago'}</p>
            </div>

            <div id="mp-doc-div" class="row">
                <div id="mp-doc-type-div" class="col-md-4 col-4 pb-20 mp-m-col">
                    <label for="id-docType" class="pb-5">{l s='Tipo' mod='mercadopago'}</label>
                    <select id="id-docType" data-checkout="docType"
                            class="form-control mp-form-control  pointer noUniform"></select>
                </div>

                <!-- Input Doc Number -->
                <div id="mp-doc-number-div" class="col-md-8 col-8 pb-20 mp-m-col">
                    <label for="id-doc-number" class="pb-5">{l s='Número de documento' mod='mercadopago'}</label>
                    <input id="id-doc-number" data-checkout="docNumber" type="text" class="form-control mp-form-control"
                           onkeyup="maskInput(this, minteger);" autocomplete="off"/>
                    <small class="mp-small pt-5">{l s='Solo números' mod='mercadopago'}</small>
                    <small id="mp-error-324"
                           class="mp-erro-form pt-0">{l s='Invalid document number' mod='mercadopago'}</small>
                </div>
            </div>


            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 px-0 mp-m-col">
                    <p class="all-required"><em
                                class="mp-required text-bold">*</em> {l s='Campo obligatorio' mod='mercadopago'}</p>
                </div>
            </div>
        </div>

        <div id="mercadopago-utilities">
            <input type="hidden" id="amount" value="{$amount|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" id="card_token_id" name="mercadopago_custom[card_token_id]"/>
            <input type="hidden" id="payment_type_id" name="mercadopago_custom[payment_type_id]"/>
            <input type="hidden" id="payment_method_id" name="mercadopago_custom[payment_method_id]"/>
            <input type="hidden" id="campaignIdCustom" name="mercadopago_custom[campaign_id]"/>
            <input type="hidden" id="couponPercentCustom" name="mercadopago_custom[percent_off]"/>
            <input type="hidden" id="couponAmountCustom" name="mercadopago_custom[coupon_amount]"/>
        </div>

        <div class="row">
            <div class="col-md-12 col-xs-12 col-12 pt-25 mp-m-col">
                <button class="btn btn-primary">{l s='Finalizar pedido' mod='mercadopago'}</button>
            </div>
        </div>
    </div>

</form>

{if $public_key != ''}
    <script type="text/javascript">
        console.log("entrou public");


        window.onload = function () {
            var custom = {
                site_id: "{$site_id|escape:'javascript':'UTF-8'}",
                select_choose: "{l s='Choose' mod='mercadopago'}..."
            };
            initializeCustom(custom)
        }

        if (window.Mercadopago === undefined) {
            $.getScript("https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js").done(function (script, textStatus) {
                // Set Public_key
                Mercadopago.setPublishableKey("{$public_key|escape:'javascript':'UTF-8'}");
            });
        }

        //collapsible payments cards acepteds
        var show_payments = document.querySelector("#button-show-payments");
        var frame_payments = document.querySelector("#frame-payments");

        show_payments.onclick = function () {
            if (frame_payments.style.display == "block") {
                frame_payments.style.display = "none";
            } else {
                frame_payments.style.display = "block";
            }
        };
    </script>
{/if}

<style>
    #uniform-id-installments span {
        background-position: unset !important;
    }

    #uniform-id-docType, #uniform-id-docType span {
        width: 100% !important;
    }

</style>