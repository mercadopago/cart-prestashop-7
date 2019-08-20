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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2019 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form id="mp_standard_checkout" method="post" action="{$redirect}">
    <div class="row frame-checkout-custom-seven">
        <div class="col-xs-12 col-md-12 col-12">
            <a class="link-checkout-custom" id="button-show-payments">{l s='Con qué tarjetas puedo pagar' mod='mercadopago'} ⌵ </a> |
            <a class="link-checkout-custom" id="mp_checkout_link" href="https://www.mercadopago.com.ar/cuotas" target="_blank">{l s='Ver promociones vigentes' mod='mercadopago'}</a>
        </div>

        <div class="col-xs-12 col-md-12 col-12">
            <div class="frame-payments" id="frame-payments">
                {if count($credit) != 0}
                    <p class="subtitle-payments">{l s='Tarjetas de crédito' mod='mercadopago'}</p>
                    {foreach $credit as $tarjeta}
                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid img-tarjetas" />
                    {/foreach}
                {/if}

                {if count($debit) != 0}
                    <p class="subtitle-payments pt-10">{l s='Tarjetas de débito' mod='mercadopago'}</p>
                    {foreach $debit as $tarjeta}
                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid img-tarjetas" />
                    {/foreach}
                {/if}
            </div>
        </div>

        <div id="mercadopago-form-coupon" class="col-xs-12 col-md-12 col-12">
            <h3 class="title-custom-checkout">{l s='Ingresa tu cupón de descuento' mod='mercadopago'}</h3>

            <div class="form-group">
                <div class="col-md-9 col-xs-8 pb-10 pl-0 mp-m-col">
                    <input type="text" id="couponCode" class="form-control mp-form-control" autocomplete="off" maxlength="24" placeholder="{l s='Ingresá tu cupón' mod='mercadopago'}" />
                </div>

                <div class="col-md-3 col-xs-4 pb-10 pr-0 text-center mp-m-col">
                    <input type="button" class="btn btn-primary mp-btn" id="applyCoupon" value="{l s='Aplicar' mod='mercadopago'}">
                </div>
            </div>
        </div>

        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <h3 class="title-custom-checkout">{l s='Ingresa los datos de tu tarjeta' mod='mercadopago'}</h3>

            <div class="form-group">
                <div class="col-md-12 col-12 pb-10 px-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Número de Tarjeta' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" class="form-control mp-form-control" autocomplete="off" />
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-12 col-12 pb-10 px-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Nombre y apellido del titular de la tarjeta' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" class="form-control mp-form-control" autocomplete="off" />
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-6 col-6 pb-20 pl-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Fecha de vencimiento' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" class="form-control mp-form-control" autocomplete="off" placeholder="MM/AAAA" />
                </div>

                <div class="col-md-6 col-6 pb-20 pr-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Código de seguridad' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" class="form-control mp-form-control" autocomplete="off" placeholder="{l s='CVV' mod='mercadopago'}" />
                    <small class="pt-5">Últimos 3 números del dorso</small>
                </div>
            </div>

            <div class="col-md-12 col-12 frame-title">
                <h3 class="title-custom-checkout">{l s='Cuántas cuotas querés pagar' mod='mercadopago'}</h3>
            </div>

            <div class="form-group">
                <div class="col-md-6 col-6 pb-20 pl-0 mp-m-col">
                    <label for="issuer" class="pb-5">{l s='Banco emisor' mod='mercadopago'}</label>
                    <select class="form-control mp-form-control mp-select pointer" id="issuer" data-checkout="issuer" name="mercadopago_custom[issuer]"></select>
                </div>

                <div class="col-md-6 col-6 pb-20 pr-0 mp-m-col">
                    <label for="installments" class="pb-5">{l s='Seleccione el número de cotas' mod='mercadopago'}</label>
                    <select class="form-control mp-form-control mp-select pointer" id="installments" data-checkout="installments" name="mercadopago_custom[installments]"></select>
                </div>
            </div>

            <div class="col-md-12 col-12 frame-title">
                <h3 class="title-custom-checkout">{l s='Ingresá tu número de documento' mod='mercadopago'}</h3>
            </div>

            <div class="form-group">
                <div class="col-md-4 col-4 pb-20 pl-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Tipo' mod='mercadopago'}</label>
                    <select class="form-control mp-form-control mp-select pointer"></select>
                </div>

                <div class="col-md-8 col-8 pb-20 pr-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Número de documento' mod='mercadopago'}</label>
                    <input type="text" class="form-control mp-form-control" autocomplete="off" />
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <div class="col-md-12 col-xs-12 col-12 pb-10 px-0 mp-m-col">
                        <input class="form-check-input mp-checkbox" type="checkbox" value="" id="defaultCheck1">
                        <label class="form-check-label fl-left pl-10" for="defaultCheck1">{l s='Guardar la tarjeta' mod='mercadopago'}</label>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-xs-12 col-12 px-0 mp-m-col">
                <p class="all-required"><em class="mp-required text-bold">*</em> {l s='Campo obligatorio' mod='mercadopago'}</p>
            </div>
        </div>

    </div>
</form>

<script type="text/javascript">
	//collapsible payments
	var show_payments = document.querySelector("#button-show-payments")
	var frame_payments = document.querySelector("#frame-payments");

	show_payments.onclick = function() {
		if (frame_payments.style.display == "block") {
			frame_payments.style.display = "none";
		} else {
			frame_payments.style.display = "block";
		}
	};
</script>