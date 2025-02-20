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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form action="{$redirect|escape:'htmlall':'UTF-8'}" method="post" class="mp-custom-checkout-six" id="mp_custom_checkout">
    <div class="row mp-frame-checkout-six">
        <!-- Logo & Title -->
        <div class="mp-title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}" />
            <p class="mp-m-pt-10">
                {l s='Pay with credit and debit cards' mod='mercadopago'}
                {if $discount != ""} ({$discount|escape:'htmlall':'UTF-8'}% OFF) {/if}
            </p>
        </div>

        {if $wallet_button}
            <div class='col-xs-12 col-md-12 col-12 mp-pt-25 mp-m-px-0'>
                <div class='mp-wallet-button-container'>
                    <div class='mp-wallet-button-title'>
                        <img src='{$module_dir|escape:"html":"UTF-8"}views/img/mp_logo.png'>
                        <span>{l s='Use your saved cards' mod='mercadopago'}</span>
                    </div>

                    <div class='mp-wallet-button-description'>
                        {l s='Those who already use Mercado Livre or Mercado Pago can pay without entering any details.' mod='mercadopago'}
                    </div>

                    <div class='mp-wallet-button-button'>
                        <button type='button' id='mp-wallet-button-btn'>
                            {l s='Pay with saved card' mod='mercadopago'}
                        </button>
                    </div>
                </div>
            </div>
        {/if}

        <!-- Cards Type -->
        <div class="col-xs-12 col-md-12 col-12 mp-pt-25 mp-m-px-0">
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
                    <div class="mp-payment-methods-container">
                        {foreach $credit as $tarjeta}
                            <div class="mp-payment-method-logo-container">
                                <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                            </div>
                        {/foreach}
                    </div>
                {/if}

                {if count($debit) != 0}
                    <p class="mp-subtitle-payments mp-pt-10">{l s='Debit card' mod='mercadopago'}</p>
                    <div class="mp-payment-methods-container">
                        {foreach $debit as $tarjeta}
                            <div class="mp-payment-method-logo-container">
                                <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>
        </div>


        <!-- MP FORM -->
        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <div id="mp-subtitle-div-title" class="col-md-12 col-12 mp-frame-title">
                <p class="submp-title-checkout-six mp-pt-10">{l s='Enter your card details' mod='mercadopago'}</p>
            </div>

            <!-- Input Card number -->
            <div class="row">
                <div class="col-md-12 col-12 mp-pt-10 mp-m-col">
                    <label for="id-card-number" class="mp-pb-5">
                        {l s='Card number' mod='mercadopago'} <em class="mp-required">*</em>
                    </label>
                    <input
                        id="id-card-number"
                        class="form-control mp-form-control"
                        type="text"
                        maxlength="24"
                        autocomplete="off"
                        data-checkout="cardNumber"
                        onkeyup="maskInput(this, mcc);"
                    />
                    <small id="mp-error-205" class="mp-erro-form" data-main="#id-card-number">
                        {l s='Invalid card number' mod='mercadopago'}
                    </small>
                    <small id="mp-error-E301" class="mp-erro-form mp-error-E301" data-main="#id-card-number">
                        {l s='Invalid card number' mod='mercadopago'}
                    </small>
                </div>
            </div>

            <!-- Input Name and Surname -->
            <div id="mp-card-holder-div" class="row">
                <div class="col-md-12 col-12 mp-pt-10 mp-m-col">
                    <label for="id-card-holder-name" class="mp-pb-5">
                        {l s='Name and surname of the cardholder' mod='mercadopago'} <em class="mp-required">*</em>
                    </label>
                    <input
                        id="id-card-holder-name"
                        class="form-control mp-form-control"
                        type="text"
                        autocomplete="off"
                        data-checkout="cardholderName"
                    />
                    <small id="mp-error-221" class="mp-erro-form" data-main="#id-card-holder-name">
                        {l s='Invalid card holder name' mod='mercadopago'}
                    </small>
                    <small id="mp-error-316" class="mp-erro-form" data-main="#id-card-holder-name">
                        {l s='Invalid card holder name' mod='mercadopago'}
                    </small>
                </div>
            </div>

            <div class="row">
                <!-- Input expiration date -->
                <div class="col-md-6 col-6 mp-pt-10 mp-m-col">
                    <label for="id-card-expiration" class="mp-pb-5">
                        {l s='Expiration date' mod='mercadopago'} <em class="mp-required">*</em>
                    </label>
                    <input
                        id="id-card-expiration"
                        class="form-control mp-form-control"
                        type="text"
                        autocomplete="off"
                        placeholder="MM/AAAA"
                        maxlength="7"
                        data-checkout="cardExpiration"
                        onkeyup="maskInput(this, mdate);"
                    />

                    <input id="id-card-expiration-month" type="hidden" />
                    <input id="id-card-expiration-year" type="hidden" />

                    <small id="mp-error-208" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                    <small id="mp-error-209" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                    <small id="mp-error-325" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                    <small id="mp-error-326" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                    <small id="mp-error-E205" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                    <small id="mp-error-301" class="mp-erro-form" data-main="#id-card-expiration">
                        {l s='Invalid card expiration date' mod='mercadopago'}
                    </small>
                </div>

                <!-- Input Security Code -->
                <div class="col-md-6 col-6 mp-pt-10 mp-m-col">
                    <label for="id-security-code" class="mp-pb-5">
                        {l s='Security code' mod='mercadopago'} <em class="mp-required">*</em>
                    </label>
                    <input
                        id="id-security-code"
                        class="form-control mp-form-control"
                        type="text"
                        autocomplete="off"
                        placeholder="{l s='CVV' mod='mercadopago'}"
                        maxlength="4"
                        data-checkout="securityCode"
                        onkeyup="maskInput(this, minteger);"
                    />
                    <small class="mp-small mp-pt-5">
                        {l s='last 3 numbers on the back of your card' mod='mercadopago'}
                    </small>

                    <small id="mp-error-224" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                        {l s='Invalid Security code' mod='mercadopago'}
                    </small>
                    <small id="mp-error-E302" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                        {l s='Invalid Security code' mod='mercadopago'}
                    </small>
                </div>
            </div>

            <!-- Title installments -->
            <div class="col-md-12 col-12 mp-frame-title">
                <p class="submp-title-checkout-six mp-pt-10">{l s='In how many installments do you want to pay?' mod='mercadopago'}</p>
            </div>

            <div class="row">
                <!-- Select issuer -->
                <div id="container-issuers" class="issuers-options col-md-4 col-4 mp-m-col">
                    <label for="id-issuers-options" class="issuers-options mp-pb-5 mp-pt-10">{l s='issuing bank' mod='mercadopago'}</label>
                    <select
                        id="id-issuers-options"
                        class="issuers-options form-control mp-form-control mp-select mp-pointer noUniform"
                        type="text"
                        data-checkout="issuer"
                    >
                    </select>
                    <small id="id-issuer-status" class="mp-erro-form"></small>
                </div>

                <!-- Select installments -->
                <div id="container-installments" class="col-md-8 col-8 col-md-4 mp-m-col mp-pl-15">
                    <label for="id-installments" class="mp-pb-5 mp-pt-10">
                        {l s='In how many installments do you want to pay?' mod='mercadopago'}
                    </label>
                    <select
                        id="id-installments"
                        class="form-control mp-form-control mp-pointer not_uniform noUniform"
                        type="text"
                        data-no-uniform="true"
                        data-checkout="installments"
                    >
                    </select>
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
            <div id="mp-doc-div-title" class="col-md-12 col-12 mp-frame-title">
                <p class="submp-title-checkout-six">{l s='Enter your document number' mod='mercadopago'}</p>
            </div>

            <div id="mp-doc-div" class="row">
                <div id="mp-doc-type-div" class="col-md-4 col-4 mp-pb-20 mp-m-col">
                    <label for="id-docType" class="mp-pb-5">{l s='Type' mod='mercadopago'}</label>
                    <select
                        id="id-docType"
                        class="form-control mp-form-control mp-pointer noUniform"
                        data-checkout="docType"
                    >
                    </select>
                </div>

                <!-- Input Doc Number -->
                <div id="mp-doc-number-div" class="col-md-8 col-8 mp-pb-20 mp-m-col">
                    <label for="id-doc-number" class="mp-pb-5">{l s='Document number' mod='mercadopago'}</label>
                    <input id="id-doc-number" data-checkout="docNumber" type="text" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-small mp-pt-5">{l s='Only numbers' mod='mercadopago'}</small>

                    <small id="mp-error-324" class="mp-erro-form mp-pt-0" data-main="#id-doc-number">
                        {l s='Invalid document number' mod='mercadopago'}
                    </small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-m-col mp-pt-20">
                    <p class="mp-all-required">
                        <em class="mp-required text-bold">*</em> {l s='Obligatory field' mod='mercadopago'}
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-md-12 col-12 mp-pt-25">
                    <label> {l s='By continuing, you agree to our ' mod='mercadopago'}
                        <u>
                            <a class="mp-link-checkout-terms" href="{$terms_url|escape:'html':'UTF-8'}" target="_blank">
                                {l s='Terms and Conditions' mod='mercadopago'}
                            </a>
                        </u>
                    </label>
                </div>
            </div>

            <div id="mercadopago-utilities">
                <input type="hidden" id="amount" value="{$amount|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" id="card_token_id" name="mercadopago_custom[card_token_id]" />
                <input type="hidden" id="payment_type_id" name="mercadopago_custom[payment_type_id]" />
                <input type="hidden" id="payment_method_id" name="mercadopago_custom[payment_method_id]" />
                <input type="hidden" id="mp_issuer" name="mercadopago_custom[issuer]" />
                <input type="hidden" id="mp_installments" name="mercadopago_custom[installments]" />
                <input type="hidden" id="campaignIdCustom" name="mercadopago_custom[campaign_id]" />
                <input type="hidden" id="couponPercentCustom" name="mercadopago_custom[percent_off]" />
                <input type="hidden" id="couponAmountCustom" name="mercadopago_custom[coupon_amount]" />
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-pt-15 mp-m-col">
                    <button id="mp-custom-finish-order" class="btn btn-primary mp-btn-primary">{l s='Check out' mod='mercadopago'}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{if $public_key != ''}
    <script type="text/javascript" src='https://sdk.mercadopago.com/js/v2'></script>
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

        // Set params to custom-card
        window.onload = loadCustom();

        function loadCustom() {
            var mp_custom = {
                site_id: '{$site_id|escape:"javascript":"UTF-8"}',
                public_key: '{$public_key|escape:"javascript":"UTF-8"}',
                ps_version: 'six',
            }

            initializeCustom(mp_custom);
        }
    </script>
{/if}

{if $wallet_button}
    <script>
        window.addEventListener('load', (event) => {
            var wallet_button_button = document.querySelector('#mp-wallet-button-btn');
            var mp_button = {};

            wallet_button_button.onclick = function (e) {
                e.preventDefault();

                fetch('index.php?fc=module&module=mercadopago&controller=walletbutton')
                .then(response => response.json())
                .then(function(response) {
                    if (response.preference) {
                        mp_button = {
                            'preference': {
                                'id': response.preference['id'],
                            },
                            'autoOpen': true,
                        };

                        var mp = new MercadoPago('{$public_key|escape:"javascript":"UTF-8"}');
                        mp.checkout(mp_button);

                        return false;
                    }
                    window.location.href = 'index.php?controller=order&step=3&typeReturn=failure';
                });
            };
        });
    </script>
{/if}

<style>
    {if $site_id != 'MLB'}
        #container-issuers {
            display: block;
        }
    {/if}

    #uniform-id-installments span {
        background-position: unset !important;
    }

    #uniform-id-docType,
    #uniform-id-docType span {
        width: 100% !important;
    }
</style>
