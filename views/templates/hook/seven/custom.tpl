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

<form id="mp_custom_checkout" class="mp-checkout-form" method="post" action="{$redirect}">

    <div class="row frame-checkout-custom-seven">
        <div class="col-xs-12 col-md-12 col-12">
            <a class="link-checkout-custom" id="button-show-payments">{l s='Con qué tarjetas puedo pagar' mod='mercadopago'} ⌵ </a>
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

        {if $coupon == true}
        <div id="mercadopago-form-custom-coupon" class="col-xs-12 col-md-12 col-12">
            <h3 class="title-custom-checkout">{l s='Ingresa tu cupón de descuento' mod='mercadopago'}</h3>

            <div class="form-group">
                <div class="col-md-9 col-xs-8 pb-10 pl-0 mp-m-col">
                    <input type="text" id="couponCodeCustom" name="mercadopago_custom[coupon_code]" class="form-control mp-form-control" autocomplete="off" maxlength="24" placeholder="{l s='Ingresá tu cupón' mod='mercadopago'}" />

                    <small class="mp-sending-coupon" id="mpSendingCouponCustom">{l s='Validando cupón de descuento...' mod='mercadopago'}</small>
                    <small class="mp-success-coupon" id="mpCouponApplyedCustom">{l s='Cupón de descuento aplicado!' mod='mercadopago'}</small>
                    <small class="mp-erro-febraban" id="mpCouponErrorCustom">{l s='El código que ingresaste no es válido!' mod='mercadopago'}</small>
                    <small class="mp-erro-febraban" id="mpResponseErrorCustom">{l s='Lo sentimos, ocurrió un error. Por favor, inténtelo de nuevo.' mod='mercadopago'}</small>
                </div>

                <div class="col-md-3 col-xs-4 pb-10 pr-0 text-center mp-m-col">
                    <input type="button" class="btn btn-primary mp-btn" id="applyCouponCustom" onclick="mpCustomApplyAjax()" value="{l s='Aplicar' mod='mercadopago'}" />
                </div>
            </div>
        </div>
        {/if}

      	<!-- Title enter your card details -->
        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <h3 class="title-custom-checkout pt-20">{l s='Ingresa los datos de tu tarjeta' mod='mercadopago'}</h3>

          	<!-- Input Card number -->
            <div class="form-group">
                <div class="col-md-12 col-12 pb-10 px-0 mp-m-col">
                    <label for="id-card-number" class="pb-5">{l s='Número de Tarjeta' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input id="id-card-number" data-checkout="cardNumber" type="text" class="form-control mp-form-control" onkeyup="maskInput(this, mcc);" maxlength="24" autocomplete="off" />
                    <small id="mp-error-205" class="mp-erro-form">{l s='Invalid card number' mod='mercadopago'}</small>
                    <small id="mp-error-E301" class="mp-erro-form mp-error-E301">{l s='Invalid card number' mod='mercadopago'}</small>     
                </div>
            </div>
          
            <!-- Input Name and Surname -->
            <div class="form-group">
                <div class="col-md-12 col-12 pb-10 px-0 mp-m-col">
                    <label for="id-card-holder-name" class="pb-5">{l s='Nombre y apellido del titular de la tarjeta' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input id="id-card-holder-name" data-checkout="cardholderName" type="text" class="form-control mp-form-control" autocomplete="off" />
                    <small id="mp-error-221" class="mp-erro-form">{l s='Invalid card holder name' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="form-group">
                <!-- Input expiration date -->
                <div class="col-md-6 col-6 pb-20 pl-0 mp-m-col">
                    <label for="id-card-expiration" class="pb-5">{l s='Fecha de vencimiento' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input id="id-card-expiration" data-checkout="cardExpiration" type="text" class="form-control mp-form-control" autocomplete="off" placeholder="MM/AAAA" onkeyup="maskInput(this, mdate);" maxlength="7" />
                    <small id="mp-error-208" class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-209" class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-325" class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                    <small id="mp-error-326" class="mp-erro-form">{l s='Invalid card expiration date' mod='mercadopago'}</small>
                </div>

                <!-- Input Security Code -->
                <div class="col-md-6 col-6 pb-20 pr-0 mp-m-col">
                    <label for="id-security-code" class="pb-5">{l s='Código de seguridad' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input id="id-security-code" data-checkout="securityCode" type="text" class="form-control mp-form-control" autocomplete="off" placeholder="{l s='CVV' mod='mercadopago'}" onkeyup="maskInput(this, minteger);" maxlength="4" />
                    <small class="mp-small pt-5">{l s='Últimos 3 números del dorso' mod='mercadopago'}</small>
                    <small id="mp-error-224" class="mp-erro-form pt-0">{l s='Invalid card holder name' mod='mercadopago'}</small>
                    <small id="mp-error-E302" class="mp-erro-form pt-0">{l s='Invalid card holder name' mod='mercadopago'}</small>
                </div>
            </div>

            <!-- Title installments -->
            <div class="col-md-12 col-12 frame-title">
                <h3 class="title-custom-checkout">{l s='Cuántas cuotas querés pagar' mod='mercadopago'}</h3>
            </div>

            <div class="form-group">
                <!-- Select issuer -->
                <div id="container-issuers" class="issuers-options col-md-4 col-4 pb-20 pl-0 mp-m-col">
                    <label for="id-issuers-options" class="issuers-options pb-5">{l s='Banco emisor' mod='mercadopago'}</label>
                    <select id="id-issuers-options"  class="issuers-options form-control mp-form-control mp-select pointer" data-checkout="issuer" name="mercadopago_custom[issuer]" type="text"></select>
                    <small id="id-issuer-status" class="mp-erro-form"></small>
                </div>

                <!-- Select installments -->
                <div id="container-installments" class="col-md-12 col-8 pb-20 pr-0 pl-0 mp-m-col">
                    <label for="id-installments" class="pb-5">{l s='Seleccione el número de cotas' mod='mercadopago'}</label>
                    <select class="form-control mp-form-control mp-select pointer" id="id-installments" data-checkout="installments" name="mercadopago_custom[installments]" type="text"></select>
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

            <!-- Title document -->
            <div id="mp-doc-div-title" class="col-md-12 col-12 frame-title">
                <h3 class="title-custom-checkout">{l s='Ingresá tu número de documento' mod='mercadopago'}</h3>
            </div>

            <!-- Select Doc Type -->
            <div id="mp-doc-div" class="form-group">
                <div id="mp-doc-type-div" class="col-md-4 col-4 pb-20 pl-0 mp-m-col">
                    <label for="id-docType" class="pb-5">{l s='Tipo' mod='mercadopago'}</label>
                    <select id="id-docType" data-checkout="docType" class="form-control mp-form-control mp-select pointer"></select>
                </div>
 
                <!-- Input Doc Number -->
                <div id="mp-doc-number-div" class="col-md-8 col-8 pb-20 pr-0 mp-m-col">
                    <label for="id-doc-number" class="pb-5">{l s='Número de documento' mod='mercadopago'}</label>
                    <input id="id-doc-number" data-checkout="docNumber" type="text" class="form-control mp-form-control" onkeyup="maskInput(this, minteger);" autocomplete="off" />
                    <small class="mp-small pt-5">{l s='Solo números' mod='mercadopago'}</small>
                    <small id="mp-error-324" class="mp-erro-form pt-0">{l s='Invalid document number' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="col-md-12 col-xs-12 col-12 px-0 mp-m-col">
                <p class="all-required"><em class="mp-required text-bold">*</em> {l s='Campo obligatorio' mod='mercadopago'}</p>
            </div>
        </div>

        <div id="mercadopago-utilities">
            <input type="hidden" id="amount" value="{$amount|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" id="card_token_id" name="mercadopago_custom[card_token_id]" />
            <input type="hidden" id="payment_type_id" name="mercadopago_custom[payment_type_id]" />
            <input type="hidden" id="payment_method_id" name="mercadopago_custom[payment_method_id]" />
            <input type="hidden" id="campaignIdCustom" name="mercadopago_custom[campaign_id]" />
            <input type="hidden" id="couponPercentCustom" name="mercadopago_custom[percent_off]" />
            <input type="hidden" id="couponAmountCustom" name="mercadopago_custom[coupon_amount]" />
        </div>

    </div>
</form>

<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}/views/js/jquery-1.11.0.min.js"></script>

{if $public_key != ''}
<script type="text/javascript">
    if (window.Mercadopago === undefined) {
        $.getScript("https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js").done(function (script, textStatus) {
            // Set Public_key
            Mercadopago.setPublishableKey("{$public_key|escape:'javascript':'UTF-8'}");
        });
    }
</script>
{/if}

<script type="text/javascript">
  
  var seller = {
    site_id: "{$site_id|escape:'javascript':'UTF-8'}"
  }
  
  var objPaymentMethod = {};
  var additionalInfoNeeded = {}
  
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

    $("input[data-checkout='cardNumber'], input[name='card-types']").on('focusout', guessingPaymentMethod);

        /*
         * Execute before event focusout on input Card Number
         * 
         * @param object event 
         */
         function guessingPaymentMethod(event) {
            clearIssuer();
            clearInstallments();
            clearTax();
            clearDoc();
           
            var bin = getBin();

            if (bin.length < 6) {
                resetBackgroundCard();
                return;
            }

            if (event.type == "keyup") {
                if (bin.length >= 6) {
                    Mercadopago.getPaymentMethod({
                        "bin": bin
                    }, paymentMethodHandler);
                }
            } else {
                setTimeout(function () {
                    if (bin.length >= 6) {
                        Mercadopago.getPaymentMethod({
                            "bin": bin
                        }, paymentMethodHandler);
                    }
                }, 100);
            }
        }
  
        /**
         * Handle payment Method response
         * 
         * @param number status 
         * @param object response 
         */
        function paymentMethodHandler(status, response) {
            if (status == 200) {
                objPaymentMethod = response[0];
                setPaymentMethodId(objPaymentMethod.id);
                setImageCard(objPaymentMethod.secure_thumbnail);
                loadAdditionalInfo(objPaymentMethod.additional_info_needed);
                additionalInfoHandler();
            } else {
                document.getElementById('id-card-number').innerHTML = '';
            }
        }
  
        /**
         * Set value on paymentMethodId element
         * 
         * @param string paymentMethodId 
         */
        function setPaymentMethodId(paymentMethodId) {
            var paymentMethodElement = document.getElementById('payment_method_id');
            paymentMethodElement.value = paymentMethodId;
        }
  
        /**
         * Set Imagem card on element
         * 
         * @param string secureThumbnail 
         */
        function setImageCard(secureThumbnail) {
            document.getElementById('id-card-number').style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
        }
  
        /**
         * 
         * Load Additional Info to use for build payment form
         * 
         * @param array additional_info_needed 
         */
        function loadAdditionalInfo(additional_info_needed) {
            additionalInfoNeeded = {
                'issuer': false,
                'cardholder_name': false,
                'cardholder_identification_type': false,
                'cardholder_identification_number': false
            }

            for (var i = 0; i < additional_info_needed.length; i++) {
                if (additional_info_needed[i] == 'issuer_id') {
                    additionalInfoNeeded.issuer = true;
                }
                if (additional_info_needed[i] == 'cardholder_name') {
                    additionalInfoNeeded.cardholder_name = true;
                }
                if (additional_info_needed[i] == 'cardholder_identification_type') {
                    additionalInfoNeeded.cardholder_identification_type = true;
                }
                if (additional_info_needed[i] == 'cardholder_identification_number') {
                    additionalInfoNeeded.cardholder_identification_number = true;
                }
            };
        }
  
        /**
         * Check what information is necessary to pay and show inputs 
         */
        function additionalInfoHandler() {
            if (additionalInfoNeeded.issuer) {
                document.getElementById('container-issuers').style.display = 'block';
                document.getElementById('container-installments').classList.remove('col-md-12');
                document.getElementById('container-installments').classList.remove('pl-0');
                document.getElementById('container-installments').classList.add('col-md-8');
                Mercadopago.getIssuers(objPaymentMethod.id, issuersHandler);
            } else {
                clearIssuer();
                setInstallments();
            }

            if (additionalInfoNeeded.cardholder_identification_type) {
                document.getElementById('mp-doc-div-title').style.display = 'block';
                document.getElementById('mp-doc-div').style.display = 'block';
                document.getElementById('mp-doc-type-div').style.display = 'block';
                Mercadopago.getIdentificationTypes();
            } else {
                document.getElementById('mp-doc-type-div').style.display = 'none'
            }

            if (additionalInfoNeeded.cardholder_identification_number) {
                document.getElementById('mp-doc-div-title').style.display = 'block';
                document.getElementById('mp-doc-div').style.display = 'block';
                document.getElementById('mp-doc-number-div').style.display = 'block';
            } else {
                document.getElementById('mp-doc-number-div').style.display = 'none';
            }

            if (!additionalInfoNeeded.cardholder_identification_type &&
                !additionalInfoNeeded.cardholder_identification_number) {
                document.getElementById('mp-doc-div-title').style.display = 'none';
                document.getElementById('mp-doc-div').style.display = 'none';
            }
        }
  
        /**
         * Handle issuers response and build select
         * 
         * @param status status 
         * @param object response 
         */
        function issuersHandler(status, response) {
            if (status == 200) {
                // If the API does not return any bank.
                var issuersSelector = document.getElementById('id-issuers-options');
                var fragment = document.createDocumentFragment();

                issuersSelector.options.length = 0;
                var option = new Option("{l s='Choose' mod='mercadopago'}...", "-1");
                fragment.appendChild(option);

                for (var i = 0; i < response.length; i++) {
                    var name = response[i].name == 'default' ? 'Otro' : response[i].name;
                    fragment.appendChild(new Option(name, response[i].id));
                }

                issuersSelector.appendChild(fragment);
                issuersSelector.removeAttribute("disabled");
                $('body').on('change', '#id-issuers-options', setInstallments);
            }
            else {
                clearIssuer();
            }
        }
  
        /**
         * Call insttalments with issuer ou not, depends on additionalInfoHandler()
         */
        function setInstallments() {
            var params_installments = {};
            var amount = getAmount();
            var issuer = false;
            for (var i = 0; i < objPaymentMethod.additional_info_needed.length; i++) {
                if (objPaymentMethod.additional_info_needed[i] == 'issuer_id') {
                    issuer = true;
                }
            }
            if (issuer) {
                var issuerId = document.getElementById('id-issuers-options').value;
                params_installments = {
                    "bin": getBin(),
                    "amount": amount,
                    "issuer_id": issuerId
                }

                if (issuerId === "-1") {
                    return;
                }
            } else {
                params_installments = {
                    "bin": getBin(),
                    "amount": amount
                }
            }
            Mercadopago.getInstallments(params_installments, installmentHandler);
        }
  
        /**
         * Get instalments
         * 
         * @param number status 
         * @param object response 
         */
        function installmentHandler(status, response) {
            clearInstallments();
            if (status == 200) {
                var selectorInstallments = document.getElementById('id-installments');
                var html_option = new Option("{l s='Choose...' mod='mercadopago'}", "", true, true);
                $("#id-installments").append(html_option);

                var payerCosts = [];
                for (var i = 0; i < response.length; i++) {
                    if (response[i].processing_mode == 'aggregator') {
                        payerCosts = response[i].payer_costs;
                    }
                }

                for (var i = 0; i < payerCosts.length; i++) {
                    html_option = new Option((payerCosts[i].recommended_message || payerCosts[i].installments), payerCosts[i].installments); 
                    html_option.setAttribute('data-tax',argentinaResolution(payerCosts[i].labels));
                    $("#id-installments").append(html_option);
                }
                
                if (seller.site_id == "MLA") {
                    clearTax();
                    $('body').on('change', '#id-installments', showTaxes);
                }
            } else {
                clearInstallments();
                clearTax();
            }
        }
  
        /**
         * Resolution 51/2017
         * 
         * @param * payerCosts 
         * @returns string
         */
        function argentinaResolution(payerCosts) {
            var dataInput = '';
            if (seller.site_id == 'MLA') {
                for (var l = 0; l < payerCosts.length; l++) {
                    if (payerCosts[l].indexOf('CFT_') !== -1) {
                        dataInput = payerCosts[l];
                    }
                }
                return dataInput;
            }
            return dataInput;
        }
  
        /**
        * Show taxes resolution 51/2017 for MLA
        */
        function showTaxes() {
            var selectorInstallments = document.querySelector('#id-installments');
            var tax = selectorInstallments.options[selectorInstallments.selectedIndex].getAttribute("data-tax");
            var cft = "";
            var tea = "";
            if (tax != null) {
                var tax_split = tax.split("|");
                cft = tax_split[0].replace("_", " ");
                tea = tax_split[1].replace("_", " ");
                if (cft == "CFT 0,00%" && tea == "TEA 0,00%") {
                    cft = "";
                    tea = "";
                }
            }
            document.querySelector('.mp-text-cft').innerHTML = cft;
            document.querySelector('.mp-text-tea').innerHTML = tea;
        }
  
        /**
        * Get Amount end calculate discount for hide inputs
        */
        function getAmount() {
            return document.getElementById('amount').value;
        }
  
        /**
         * Get Bin from Card Number
         */
        function getBin() {
          var cardnumber = $("#id-card-number").val().replace(/ /g, '').replace(/-/g,'').replace(/\./g, '');
          return cardnumber.substr(0, 6);
        }
  
        /**
        * Remove background image from imput
        */
        function resetBackgroundCard() {
            document.getElementById('id-card-number').style.background = 'no-repeat #fff';
        }
  
        /**
        * Clear input select
        */
        function clearInstallments() {
            document.getElementById('id-installments').innerHTML = '';
        }
  
        /**
        * Clear Tax
        */
        function clearTax() {
            document.querySelector('.mp-text-cft').innerHTML = '';
            document.querySelector('.mp-text-tea').innerHTML = '';
        }
  
        /**
         * Clear input select and change to default layout
         */
        function clearIssuer() {
            document.getElementById('container-issuers').style.display = 'none';
            document.getElementById('container-installments').classList.remove('col-md-8');
            document.getElementById('container-installments').classList.add('mp-md-12');
            document.getElementById('container-installments').classList.add('pl-0');
            document.getElementById('id-issuers-options').innerHTML = '';
        }
  
        /**
         * Clear input select and change to default layout
         */
        function clearDoc() {
            document.getElementById('mp-doc-div-title').style.display = 'none';
            document.getElementById('mp-doc-div').style.display = 'none';
            document.getElementById('mp-doc-type-div').style.display = 'none';
            document.getElementById('mp-doc-number-div').style.display = 'none';
            document.getElementById('id-docType').innerHTML = '';
            document.getElementById('id-doc-number').value = '';
        }
  
        document.forms['mp_custom_checkout'].onsubmit = function () {
          
            if (validateInputsCreateToken()) {
                return createToken();
            }
 
            return false;
        }
        
        /** 
        * Validate Inputs to Create Token
        * 
        * @return bool
        */
        function validateInputsCreateToken() {
            var form_inputs = getForm().querySelectorAll("[data-checkout]");
            var fixed_inputs = [
                'cardNumber',
                'cardExpiration',
                'securityCode',
                'installments'
            ];

            for (var x = 0; x < form_inputs.length; x++) {
                var element = form_inputs[x];
                // Check is a input to create token.
                if (fixed_inputs.indexOf(element.getAttribute("data-checkout")) > -1) {
                    if (element.value == -1 || element.value == "") {
                        element.focus();
                        return false;
                    }
                }
            }

            if (objPaymentMethod.length == 0) {
                document.getElementById('id-card-number').focus();
                return false;
            }

            if (!validateAdditionalInputs()) {
                return false;
            }

            return true;
        }
  
        /**
         * Validate Additional Inputs
         * 
         * @return bool
         */
        function validateAdditionalInputs() {
            if (additionalInfoNeeded.issuer) {
                var inputMpIssuer = document.getElementById('id-issuers-options');
                if (inputMpIssuer.value == -1 || inputMpIssuer.value == "") {
                    inputMpIssuer.focus();
                    return false;
                }
            }
            if (additionalInfoNeeded.cardholder_name) {
                var inputCardholderName = document.getElementById('id-card-holder-name');
                if (inputCardholderName.value == -1 || inputCardholderName.value == "") {
                    inputCardholderName.focus();
                    return false;
                }
            }
            if (additionalInfoNeeded.cardholder_identification_type) {
                var inputDocType = document.getElementById('id-docType');
                if (inputDocType.value == -1 || inputDocType.value == "") {
                    docType.focus();
                    return false;
                }
            }
            if (additionalInfoNeeded.cardholder_identification_number) {
                var docNumber = document.getElementById('id-doc-number');
                if (docNumber.value == -1 || docNumber.value == "") {
                    docNumber.focus();
                    return false;
                }
            }
            return true;
        }
  
        /**
         *  Create Token call Mercadopago.createToken
         * 
         *  @return bool 
         */
        function createToken() {
          hideErrors();

            // Form.
            var form = getForm();

          Mercadopago.createToken(form, sdkResponseHandler);

            return false;
        }
  
        /**
         * Handler Response of Mercadopago.createToken
         * 
         * @param number status 
         * @param object response 
         */
        function sdkResponseHandler(status, response) {
            if (status != 200 && status != 201) {
                showErrors(response);
            } else {
                var token = document.querySelector('#card_token_id');
                token.value = response.id;
                mercado_pago_submit = true;
                document.forms['mp_custom_checkout'].submit();
            }
        }
  
        /**
         * 
         * @param  obje  response
        */
        function showErrors(response) {
            var form = getForm();
            for (var x = 0; x < response.cause.length; x++) {
                var error = response.cause[x];

                if (error.code == 208 || error.code == 209 || error.code == 325 || error.code == 326) {
                    var small = form.querySelector("#mp-error-208");
                } else {
                    var small = form.querySelector("#mp-error-" + error.code);
                }

                if (small != undefined) {
                    small.style.display = "block";
                }
            }
            return;
        }
  
        /**
         * Hide errors when return of cardToken error
         */
        function hideErrors() {
            for (var x = 0; x < document.querySelectorAll(".mp-erro-form").length; x++) {
                var small = document.querySelectorAll(".mp-erro-form")[x];
                small.style.display = "none";
            }
        }
  
        /**
         * Get form
         */
        function getForm() {
            return document.querySelector('#mp_custom_checkout');
        }

</script>