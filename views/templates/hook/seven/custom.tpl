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

    <form id="mp_custom_checkout" class="mp-checkout-form" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">

        <div class="row mp-frame-checkout-custom-seven">
            <div class="col-xs-12 col-md-12 col-12">
                <a class="mp-link-checkout-custom" id="button-show-payments">
                    {l s='With what cards can I pay' mod='mercadopago'} ⌵
                </a>

                {if $site_id == 'MLA'}
                <span class="mp-separate-promotion-link"> | </span>
                <a href="https://www.mercadopago.com.ar/cuotas" target="_blank" class="mp-link-checkout-custom">
                    {l s='See current promotions' mod='mercadopago'}
                </a>
                {/if}
            </div>

            <div class="col-xs-12 col-md-12 col-12">
                <div class="mp-frame-payments" id="mp-frame-payments">
                    {if count($credit) != 0}
                    <p class="mp-subtitle-payments">{l s='Credit card' mod='mercadopago'}</p>
                    {foreach $credit as $tarjeta}
                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid mp-img-tarjetas" />
                    {/foreach}
                    {/if}

                    {if count($debit) != 0}
                    <p class="mp-subtitle-payments mp-pt-10">{l s='Debit card' mod='mercadopago'}</p>
                    {foreach $debit as $tarjeta}
                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid mp-img-tarjetas" />
                    {/foreach}
                    {/if}
                </div>
            </div>

            <!-- Title enter your card details -->
            <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
                <h3 class="mp-title-custom-checkout mp-pt-20">{l s='Enter your card details' mod='mercadopago'}</h3>

                <!-- Input Card number -->
                <div class="form-group">
                    <div class="col-md-12 col-12 mp-pb-10 mp-px-0 mp-m-col">
                        <label for="id-card-number" class="mp-pb-5">
                            {l s='Card number' mod='mercadopago'}
                            <em class="mp-required">*</em></label>
                        <input id="id-card-number" data-checkout="cardNumber" type="text"
                            class="form-control mp-form-control" onkeyup="maskInput(this, mcc);" maxlength="24"
                            autocomplete="off" />
                        <small id="mp-error-205" class="mp-erro-form" data-main="#id-card-number">{l s='Invalid card
                            number' mod='mercadopago'}</small>
                        <small id="mp-error-E301" class="mp-erro-form mp-error-E301" data-main="#id-card-number">{l
                            s='Invalid card number' mod='mercadopago'}</small>
                    </div>
                </div>

                <!-- Input Name and Surname -->
                <div id="mp-card-holder-div" class="form-group">
                    <div class="col-md-12 col-12 mp-pb-10 mp-px-0 mp-m-col">
                        <label for="id-card-holder-name" class="mp-pb-5">
                            {l s='Name and surname of the cardholder' mod='mercadopago'}
                            <em class="mp-required">*</em></label>
                        <input id="id-card-holder-name" data-checkout="cardholderName" type="text"
                            class="form-control mp-form-control" autocomplete="off" />
                        <small id="mp-error-221" class="mp-erro-form" data-main="#id-card-holder-name">
                            {l s='Invalid card holder name' mod='mercadopago'}</small>
                        <!--RESOLVER A QUESTÃO DE TRADUÇÃO - MENSAGEM DE ERRO -->
                    </div>
                </div>

                <div class="form-group">
                    <!-- Input expiration date -->
                    <div class="col-md-6 col-6 mp-pb-20 mp-pl-0 mp-m-col">
                        <label for="id-card-expiration" class="mp-pb-5">
                            {l s='Expiration date' mod='mercadopago'}
                            <em class="mp-required">*</em></label>
                        <input id="id-card-expiration" data-checkout="cardExpiration" type="text"
                            class="form-control mp-form-control" autocomplete="off" placeholder="MM/AAAA"
                            onkeyup="maskInput(this, mdate);" maxlength="7" />
                        <input id="id-card-expiration-month" type="hidden" />
                        <input id="id-card-expiration-year" type="hidden" />
                        <small id="mp-error-208" class="mp-erro-form" data-main="#id-card-expiration">
                            {l s='Invalid card expiration date' mod='mercadopago'}</small>
                        <small id="mp-error-209" class="mp-erro-form" data-main="#id-card-expiration">
                            {l s='Invalid card expiration date' mod='mercadopago'}</small>
                        <small id="mp-error-325" class="mp-erro-form" data-main="#id-card-expiration">
                            {l s='Invalid card expiration date' mod='mercadopago'}</small>
                        <small id="mp-error-326" class="mp-erro-form" data-main="#id-card-expiration">
                            {l s='Invalid card expiration date' mod='mercadopago'}</small>
                    </div>

                    <!-- Input Security Code -->
                    <div class="col-md-6 col-6 mp-pb-20 mp-pr-0 mp-m-col">
                        <label for="id-security-code" class="mp-pb-5">
                            {l s='Security code' mod='mercadopago'}
                            <em class="mp-required">*</em></label>
                        <input id="id-security-code" data-checkout="securityCode" type="text"
                            class="form-control mp-form-control" autocomplete="off"
                            placeholder="{l s='CVV' mod='mercadopago'}" onkeyup="maskInput(this, minteger);"
                            maxlength="4" />
                        <small class="mp-small mp-pt-5">
                            {l s='last 3 numbers on the back of your card' mod='mercadopago'}
                        </small>
                        <small id="mp-error-224" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                            {l s='Invalid card holder name' mod='mercadopago'}</small>
                        <small id="mp-error-E302" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                            {l s='Invalid card holder name' mod='mercadopago'}</small>
                    </div>
                </div>

                <!-- Title installments -->
                <div class="col-md-12 col-12 mp-frame-title">
                    <h3 class="mp-title-custom-checkout">{l s='In how many installments do you want to pay?'
                        mod='mercadopago'}</h3>
                </div>

                <div class="form-group">
                    <!-- Select issuer -->
                    <div id="container-issuers" class="issuers-options col-md-4 col-4 mp-pb-20 mp-pl-0 mp-m-col">
                        <label for="id-issuers-options" class="issuers-options mp-pb-5">
                            {l s='issuing bank' mod='mercadopago'}
                        </label>
                        <select id="id-issuers-options"
                            class="issuers-options form-control mp-form-control mp-select mp-pointer"
                            data-checkout="issuer" name="mercadopago_custom[issuer]" type="text"></select>
                        <small id="id-issuer-status" class="mp-erro-form"></small>
                    </div>

                    <!-- Select installments -->
                    <div id="container-installments" class="col-md-12 col-8 mp-pb-20 mp-pr-0 mp-pl-0 mp-m-col">
                        <label for="id-installments" class="mp-pb-5">
                            {l s='In how many installments do you want to pay?' mod='mercadopago'}
                        </label>
                        <select class="form-control mp-form-control mp-select mp-pointer" id="id-installments"
                            data-checkout="installments" name="mercadopago_custom[installments]" type="text"></select>
                        <small id="id-installments-status" class="mp-erro-form"></small>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mp-text-cft" id="mp-tax-cft-text"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mp-text-tea" id="mp-tax-tea-text"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Title document -->
                <div id="mp-doc-div-title" class="col-md-12 col-12 mp-frame-title">
                    <h3 class="mp-title-custom-checkout">
                        {l s='Enter your document number' mod='mercadopago'}
                    </h3>
                </div>

                <!-- Select Doc Type -->
                <div id="mp-doc-div" class="form-group">
                    <div id="mp-doc-type-div" class="col-md-4 col-4 mp-pb-20 mp-pl-0 mp-m-col">
                        <label for="id-docType" class="mp-pb-5">
                            {l s='Type' mod='mercadopago'}
                        </label>
                        <select id="id-docType" data-checkout="docType"
                            class="form-control mp-form-control mp-select mp-pointer"></select>
                    </div>

                    <!-- Input Doc Number -->
                    <div id="mp-doc-number-div" class="col-md-8 col-8 mp-pb-20 mp-pr-0 mp-m-col">
                        <label for="id-doc-number" class="mp-pb-5">{l s='Document number' mod='mercadopago'}</label>
                        <input id="id-doc-number" data-checkout="docNumber" type="text"
                            class="form-control mp-form-control" autocomplete="off" />
                        <small class="mp-small mp-pt-5">{l s='Only numbers' mod='mercadopago'}</small>
                        <small id="mp-error-214" class="mp-erro-form mp-pt-0" data-main="#id-doc-number">
                            {l s='Invalid document number' mod='mercadopago'}</small>
                        <small id="mp-error-324" class="mp-erro-form mp-pt-0" data-main="#id-doc-number">
                            {l s='Invalid document number' mod='mercadopago'}</small>
                    </div>
                </div>

                <div class="col-md-12 col-xs-12 col-12 mp-px-0 mp-m-col">
                    <p class="mp-all-required"><em class="mp-required text-bold">*</em> {l s='Obligatory field'
                        mod='mercadopago'}</p>
                </div>

                <div class="col-md-12 col-xs-12 col-12 mp-px-0 mp-m-col mp-pt-5">
                    <label class="mp-pb-20">
                        {l s='By continuing, you agree to our ' mod='mercadopago'}
                        <u><a class="mp-link-checkout-custom" href="{$terms_url|escape:'html':'UTF-8'}" target="_blank">
                                {l s='Terms and Conditions' mod='mercadopago'}
                            </a></u>
                    </label>
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

    <script type="text/javascript" src='https://sdk.mercadopago.com/js/v2'></script>
    <script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/jquery-1.11.0.min.js"></script>

    {if $public_key != ''}
    <script type="text/javascript">

        // Collapsible payments cards acepteds
        var show_payments = document.querySelector('#button-show-payments');
        var frame_payments = document.querySelector('#mp-frame-payments');

        show_payments.onclick = function () {
            if (frame_payments.style.display == 'block') {
                frame_payments.style.display = 'none';
            } else {
                frame_payments.style.display = 'block';
            }
        };

        var additionalInfoNeeded = {};
        var mp = null;
        var mpCardForm = null;
        var site_id = '{$site_id|escape:"javascript":"UTF-8"}';
        var psVersion = 'seven';

        loadCustom();
        setChangeEventOnExpirationDate();
        setChangeEventOnCardNumber();

        /**
        * Disable finish order button
        *
        * @param string psVersion
        */
        function disableFinishOrderButton(psVersion) {
            if (psVersion === 'six') {
                var sixButton = document.getElementById('mp-custom-finish-order');
                sixButton.setAttribute('disabled', 'disabled');
            } else if (psVersion === 'seven') {
                var sevenButton = document.getElementById('payment-confirmation').childNodes[1].childNodes[1];
                sevenButton.setAttribute('disabled', 'disabled');
            }
        }

        /**
        *Create instance of Mercado Pago sdk v2 and mount form
        *
        */
        function loadCustom() {
            mp = new MercadoPago('{$public_key|escape:"javascript":"UTF-8"}');

            mpCardForm = mp.cardForm({
                amount: getAmount(),
                autoMount: true,
                processingMode: 'aggregator',
                form: {
                    id: 'mp_custom_checkout',
                    cardNumber: { id: 'id-card-number' },
                    cardholderName: { id: 'id-card-holder-name' },
                    cardExpirationMonth: { id: 'id-card-expiration-month' },
                    cardExpirationYear: { id: 'id-card-expiration-year' },
                    securityCode: { id: 'id-security-code' },
                    installments: { id: 'id-installments' },
                    identificationType: { id: 'id-docType' },
                    identificationNumber: { id: 'id-doc-number' },
                    issuer: { id: 'id-issuers-options' }
                },
                callbacks: {
                    onFormMounted: error => {
                        if (error) return console.warn('Form Mounted handling error: ', error)
                        console.log('Form mounted')
                    },
                    onIdentificationTypesReceived: (error, identificationTypes) => {
                        if (error) return console.warn('identificationTypes handling error: ', error)
                        console.log('Identification types available: ', identificationTypes);
                    },
                    onPaymentMethodsReceived: (error, paymentMethods) => {
                        if (error) {

                            return console.warn('paymentMethods handling error: ', error)
                        }
                        console.log('Payment Methods available: ', paymentMethods)

                        validateFixedInputs();
                        clearInputs();
                        setImageCard(paymentMethods[0]['thumbnail']);
                        loadAdditionalInfo(paymentMethods[0]['additional_info_needed']);
                        additionalInfoHandler();
                    },
                    onIssuersReceived: (error, issuers) => {
                        if (error) return console.warn('issuers handling error: ', error)
                        console.log('Issuers available: ', issuers);
                    },
                    onInstallmentsReceived: (error, installments) => {

                        if (error) return console.warn('installments handling error: ', error)
                        console.log('Installments available: ', installments);

                        setChangeEventOnInstallments(site_id, installments['payer_costs']);
                    },
                    onCardTokenReceived: (error, token) => {
                        // console.log('token received');
                        if (error) {
                            showErrors(error);
                            return console.warn('Token handling error: ', error)
                        }
                        console.log('Token available: ', token);

                        sdkResponseHandler(error, token['token']);
                    },
                }
            });
        }

        /**
        * Split the date into month and year
        */
        function setChangeEventOnExpirationDate() {
            document
                .getElementById('id-card-expiration')
                .addEventListener("change", function (event) {
                    var cardExpirationDate = document.getElementById('id-card-expiration').value;
                    var cardExpirationMonth = cardExpirationDate.split('/')[0] | " ";
                    var cardExpirationYear = cardExpirationDate.split('/')[1] | " ";
                    document.getElementById('id-card-expiration-month').value = ('0' + cardExpirationMonth).slice(-2);
                    document.getElementById('id-card-expiration-year').value = cardExpirationYear;
                });
        }

        /**
        * Get Amount end calculate discount for hide inputs
        */
        function getAmount() {
            return document.getElementById('amount').value;
        }

        /**
        * Set Imagem card on element
        *
        * @param string secureThumbnail
        */
        function setImageCard(secureThumbnail) {
            var mpCardNumber = document.getElementById('id-card-number');
            mpCardNumber.style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
            mpCardNumber.style.backgroundSize = 'auto 24px';
        }

        /**
        *
        * Load Additional Info to use for build payment form
        *
        * @param array sdkAdditionalInfoNeeded
        */
        function loadAdditionalInfo(sdkAdditionalInfoNeeded) {
            additionalInfoNeeded = {
                issuer: false,
                cardholder_name: false,
                cardholder_identification_type: false,
                cardholder_identification_number: false
            };

            for (var i = 0; i < sdkAdditionalInfoNeeded.length; i++) {
                if (sdkAdditionalInfoNeeded[i] === 'issuer_id') {
                    additionalInfoNeeded.issuer = true;
                }
                if (sdkAdditionalInfoNeeded[i] === 'cardholder_name') {
                    additionalInfoNeeded.cardholder_name = true;
                }
                if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_type') {
                    additionalInfoNeeded.cardholder_identification_type = true;
                }
                if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_number') {
                    additionalInfoNeeded.cardholder_identification_number = true;
                }
            }
        }

        /**
        * Check what information is necessary to pay and show inputs
        */
        function additionalInfoHandler() {
            if (additionalInfoNeeded.cardholder_name) {
                document.getElementById('mp-card-holder-div').style.display = 'block';
            } else {
                document.getElementById('mp-card-holder-div').style.display = 'none';
            }

            if (additionalInfoNeeded.issuer) {
                document.getElementById('container-issuers').style.display = 'block';
                document.getElementById('container-installments').classList.remove('col-md-12');
                document.getElementById('container-installments').classList.add('col-md-8');
                //   Mercadopago.getIssuers(objPaymentMethod.id, getBin(), issuersHandler);
                //   mp.getIssuers(getBin());// revalidar
            }
            else {
                clearIssuer();
                clearTax();
            }
            if (additionalInfoNeeded.cardholder_identification_type) {
                document.getElementById('mp-doc-div-title').style.display = 'block';
                document.getElementById('mp-doc-div').style.display = 'block';
                document.getElementById('mp-doc-type-div').style.display = 'block';
                mp.getIdentificationTypes();
            } else {
                document.getElementById('mp-doc-type-div').style.display = 'none';
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
         * Get Bin from Card Number
         */
        function getBin() {
            var cardnumber = $('#id-card-number').val().replace(/ /g, '').replace(/-/g, '').replace(/\./g, '');
            return cardnumber.substr(0, 6);
        }

        /**
         * Remove background image from imput
         */
        function resetBackgroundCard() {
            document.getElementById('id-card-number').style.background = 'no-repeat #fff';
        }

        /*
        * Execute before event focusout on input Card Number
        *
        * @param object event
        */
        function guessingPaymentMethod(event) {
            loadCustom();
            var bin = getBin();

            if (bin.length < 6) {
                resetBackgroundCard();
                clearDoc();
                clearIssuer();

                clearTax();
                clearInputs();

                return;
            }
        }

        /**
         * Clear input select and change to default layout
         */
        function clearDoc() {
            console.log('ClearDoc OK!');
            document.getElementById('mp-doc-div-title').style.display = 'none';
            document.getElementById('mp-doc-div').style.display = 'none';
            document.getElementById('mp-doc-type-div').style.display = 'none';
            document.getElementById('mp-doc-number-div').style.display = 'none';
            // document.getElementById('id-docType').innerHTML = ''; // não limpar o campo Tipo de Documento.
            document.getElementById('id-doc-number').value = '';
            mp
        }


        /**
       * Clear input select and change to default layout
       */
        function clearIssuer() {
            document.getElementById('container-issuers').style.display = 'none';
            document.getElementById('container-installments').classList.remove('col-md-8');
            document.getElementById('container-installments').classList.add('mp-md-12');
            document.getElementById('id-issuers-options').innerHTML = '';
        }

        /**
         * Clear Tax
         */
        function clearTax() {
            document.querySelector('.mp-text-cft').innerHTML = '';
            document.querySelector('.mp-text-tea').innerHTML = '';
        }

        /**
         * Clear Inputs
         */
        function clearInputs() {
            hideErrors();
            document.getElementById('id-card-number').style.background = 'no-repeat #fff';
            document.getElementById('id-card-expiration').value = '';
            document.getElementById('id-doc-number').value = '';
            document.getElementById('id-security-code').value = '';
            document.getElementById('id-card-holder-name').value = '';
        }

        /**
        * Disable select for debit cards
        *
        * @param string payment_type_id
        */
        function handleInstallments(payment_type_id) {
            if (payment_type_id === 'debit_card') {
                document.getElementById('id-installments').setAttribute("disabled", "disabled");
            } else {
                document.getElementById('id-installments').removeAttribute("disabled");
            }
        }

        /**
        * Validate fixed Inputs is empty
        *
        * @return bool
        */
        function validateFixedInputs() {
            var emptyInputs = false;
            var form = getFormCustom();
            var formInputs = form.querySelectorAll('[data-checkout]');
            var fixedInputs = [
                'cardNumber',
                'cardExpiration',
                'securityCode',
                'installments'
            ];

            for (var x = 0; x < formInputs.length; x++) {
                var element = formInputs[x];

                // Check is a input to create token.
                if (fixedInputs.indexOf(element.getAttribute('data-checkout')) > -1) {

                    if (element.value === -1 || element.value === '') {
                        var span = form.querySelectorAll('small[data-main="#' + element.id + '"]');
                        console.log('Span: ', span);

                        if (span.length > 0) {
                            span[0].style.display = 'block';
                        }
                        element.classList.add('mp-form-control-error');
                        emptyInputs = true;
                    }
                }
            }
            return emptyInputs;
        }


        /**
        * Show governmental taxes in MLA
        *
        * @params any payer_costs
        */
        function setChangeEventOnInstallments(siteId, payer_costs) {
            if (siteId === 'MLA') {
                clearTax();
                document.querySelector('#id-installments').addEventListener('change', function (e) {
                    showTaxes(payer_costs);
                });
            }
        }

        /**
        * Show governmental taxes in MLA
        *
        * @params any payer_costs
        */
        function showTaxes(payer_costs) {
            console.log('dentro de show taxes:', payer_costs);
            var installmentsSelect = document.querySelector('#id-installments');

            for (var i = 0; i < payer_costs.length; i++) {
                console.log('estamos dentro do for');
                if (payer_costs[i].installments === installmentsSelect.value) {
                    console.log('estamos dentro do if');
                    var taxes_split = payer_costs[i].labels[0].split('|');
                    var cft = taxes_split[0].replace('_', ' ');
                    var tea = taxes_split[1].replace('_', ' ');

                    if (cft === 'CFT 0,00%' && tea === 'TEA 0,00%') {
                        cft = '';
                        tea = '';
                    }
                    document.querySelector('#mp-tax-cft-text').innerHTML = cft;
                    document.querySelector('#mp-tax-tea-text').innerHTML = tea;
                }
            }
        }

        /**
        * Clears card number input on keyup when there's less than 4 digits
        *
        */
        function setChangeEventOnCardNumber() {
            document.getElementById('id-card-number').addEventListener('keyup', function (e) {
                if (e.target.value.length <= 4) {
                    clearInputs();
                }
            });
        }

        /**
         * Function ShowError
         * @param  obje  error
         */
        function showErrors(error) {

            var form = getFormCustom();
            var serializedError = error.cause || error;
            var scValue = document.getElementById('id-security-code').value;
            var chnValue = document.getElementById('id-card-holder-name').value;

            for (var x = 0; x < serializedError.length; x++) {
                var code = serializedError[x].code;
                var span = undefined;

                if (code === '208' || code === '209' || code === '325' || code === '326') {
                    span = form.querySelector('#mp-error-208');
                    console.log("ShowErrors:", code);
                } else {
                    console.log("ShowErrors:", code);
                    span = form.querySelector('#mp-error-' + code);
                    // span = form.querySelector('#mp-error-224');
                }

                if (span !== undefined) {
                    span.style.display = 'block';
                    form.querySelector(span.getAttribute('data-main')).classList.add('mp-form-control-error');
                }
            }

            getConditionTerms();
        }

        /**
        * Handler Response of mp.createToken
        *
        * @param number error
        * @param object token
        */
        function sdkResponseHandler(error, token) {

            if (error) {
                showErrors(error);
            } else {
                var responseToken = document.querySelector('#card_token_id');
                responseToken.value = token;
                // document.forms.mp_custom_checkout.submit(); // habilitar depois
            }
        }

        /**
        * Validate inputs
        *
        */
        function validateInputs() {
            hideErrors();
            var fixedInputs = validateFixedInputs();
            var additionalInputs = validateAdditionalInputs();

            if (fixedInputs || additionalInputs) {
                console.log('Entra do IF')
                focusInputError();
                return false;
            }
            return true;
        }

        /**
        * Focus input with error
        *
        * @return bool
        */
        function focusInputError() {
            if (document.querySelectorAll('.mp-form-control-error') !== undefined) {
                var formInputs = document.querySelectorAll('.mp-form-control-error');
                formInputs[0].focus();

                console.log('focus input:', formInputs[0]); //limpar depois
            }
        }

        jQuery(function () {
            $('input[data-checkout="cardNumber"]').on('focusout', guessingPaymentMethod);

            if (document.forms.mp_custom_checkout !== undefined) {

                document.forms.mp_custom_checkout.onsubmit = function () {

                    if (validateInputs()) {
                        disableFinishOrderButton(psVersion);
                        mpCardForm.createCardToken();
                        return false;
                    }

                    getConditionTerms();
                    return false;
                };
            }
        })

    </script>
    {/if}