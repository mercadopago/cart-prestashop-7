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
* @copyright 2007-2020 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form id="mp_custom_checkout" class="mp-checkout-form" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">

    <div class="container">
        <div class="row title">
            <div class="col-12 mp-pb-10">
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
            <div class="col-12 mp-pb-20">
                <div class="mp-frame-payments" id="mp-frame-payments">
                    {if count($credit) != 0}
                        <p class="mp-subtitle-payments">{l s='Credit card' mod='mercadopago'}</p>
                        {foreach $credit as $tarjeta}
                            <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid mp-img-tarjetas"/>
                        {/foreach}
                    {/if}

                    {if count($debit) != 0}
                        <p class="mp-subtitle-payments mp-pt-10">{l s='Debit card' mod='mercadopago'}</p>
                        {foreach $debit as $tarjeta}
                            <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="img-fluid mp-img-tarjetas"/>
                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>
        <div class="row data">
            <div class="model col-sm-6">
                <div class="creditcard">
                    <div class="front">
                        <div id="ccsingle"></div>
                        <svg version="1.1" id="cardfront" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
                            <g id="Front">
                                <g id="CardBackground">
                                    <g id="Page-1_1_">
                                        <g id="amex_1_">
                                            <path id="Rectangle-1_1_" class="lightcolor grey" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
                                    C0,17.9,17.9,0,40,0z"></path>
                                        </g>
                                    </g>
                                    <path class="darkcolor greydark" d="M750,431V193.2c-217.6-57.5-556.4-13.5-750,24.9V431c0,22.1,17.9,40,40,40h670C732.1,471,750,453.1,750,431z"></path>
                                </g>
                                <text transform="matrix(1 0 0 1 60.106 295.0121)" id="svgnumber" class="st2 st3 st4">0123 4567 8910 1112</text>
                                <text transform="matrix(1 0 0 1 54.1064 428.1723)" id="svgname" class="st2 st5 st6">JOHN DOE</text>
                                <text transform="matrix(1 0 0 1 54.1074 389.8793)" class="st7 st5 st8">cardholder name</text>
                                <text transform="matrix(1 0 0 1 479.7754 388.8793)" class="st7 st5 st8">expiration</text>
                                <text transform="matrix(1 0 0 1 65.1054 241.5)" class="st7 st5 st8">card number</text>
                                <g>
                                    <text transform="matrix(1 0 0 1 574.4219 433.8095)" id="svgexpire" class="st2 st5 st9">01/23</text>
                                    <text transform="matrix(1 0 0 1 479.3848 417.0097)" class="st2 st10 st11">VALID</text>
                                    <text transform="matrix(1 0 0 1 479.3848 435.6762)" class="st2 st10 st11">THRU</text>
                                    <polygon class="st2" points="554.5,421 540.4,414.2 540.4,427.9      "></polygon>
                                </g>
                                <g id="cchip">
                                    <g>
                                        <path class="st2" d="M168.1,143.6H82.9c-10.2,0-18.5-8.3-18.5-18.5V74.9c0-10.2,8.3-18.5,18.5-18.5h85.3
                                c10.2,0,18.5,8.3,18.5,18.5v50.2C186.6,135.3,178.3,143.6,168.1,143.6z"></path>
                                    </g>
                                    <g>
                                        <g>
                                            <rect x="82" y="70" class="st12" width="1.5" height="60"></rect>
                                        </g>
                                        <g>
                                            <rect x="167.4" y="70" class="st12" width="1.5" height="60"></rect>
                                        </g>
                                        <g>
                                            <path class="st12" d="M125.5,130.8c-10.2,0-18.5-8.3-18.5-18.5c0-4.6,1.7-8.9,4.7-12.3c-3-3.4-4.7-7.7-4.7-12.3
                                    c0-10.2,8.3-18.5,18.5-18.5s18.5,8.3,18.5,18.5c0,4.6-1.7,8.9-4.7,12.3c3,3.4,4.7,7.7,4.7,12.3
                                    C143.9,122.5,135.7,130.8,125.5,130.8z M125.5,70.8c-9.3,0-16.9,7.6-16.9,16.9c0,4.4,1.7,8.6,4.8,11.8l0.5,0.5l-0.5,0.5
                                    c-3.1,3.2-4.8,7.4-4.8,11.8c0,9.3,7.6,16.9,16.9,16.9s16.9-7.6,16.9-16.9c0-4.4-1.7-8.6-4.8-11.8l-0.5-0.5l0.5-0.5
                                    c3.1-3.2,4.8-7.4,4.8-11.8C142.4,78.4,134.8,70.8,125.5,70.8z"></path>
                                        </g>
                                        <g>
                                            <rect x="82.8" y="82.1" class="st12" width="25.8" height="1.5"></rect>
                                        </g>
                                        <g>
                                            <rect x="82.8" y="117.9" class="st12" width="26.1" height="1.5"></rect>
                                        </g>
                                        <g>
                                            <rect x="142.4" y="82.1" class="st12" width="25.8" height="1.5"></rect>
                                        </g>
                                        <g>
                                            <rect x="142" y="117.9" class="st12" width="26.2" height="1.5"></rect>
                                        </g>
                                    </g>
                                </g>
                            </g>
                            <g id="Back">
                            </g>
                        </svg>
                    </div>
                    <div class="back">
                        <svg version="1.1" id="cardback" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve">
                            <g id="Front">
                                <line class="st0" x1="35.3" y1="10.4" x2="36.7" y2="11"></line>
                            </g>
                            <g id="Back">
                                <g id="Page-1_2_">
                                    <g id="amex_2_">
                                        <path id="Rectangle-1_2_" class="darkcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
                                C0,17.9,17.9,0,40,0z"></path>
                                    </g>
                                </g>
                                <rect y="61.6" class="st2" width="750" height="78"></rect>
                                <g>
                                    <path class="st3" d="M701.1,249.1H48.9c-3.3,0-6-2.7-6-6v-52.5c0-3.3,2.7-6,6-6h652.1c3.3,0,6,2.7,6,6v52.5
                            C707.1,246.4,704.4,249.1,701.1,249.1z"></path>
                                    <rect x="42.9" y="198.6" class="st4" width="664.1" height="10.5"></rect>
                                    <rect x="42.9" y="224.5" class="st4" width="664.1" height="10.5"></rect>
                                    <path class="st5" d="M701.1,184.6H618h-8h-10v64.5h10h8h83.1c3.3,0,6-2.7,6-6v-52.5C707.1,187.3,704.4,184.6,701.1,184.6z"></path>
                                </g>
                                <text transform="matrix(1 0 0 1 621.999 227.2734)" id="svgsecurity" class="st6 st7">985</text>
                                <g class="st8">
                                    <text transform="matrix(1 0 0 1 518.083 280.0879)" class="st9 st6 st10">security code</text>
                                </g>
                                <rect x="58.1" y="378.6" class="st11" width="375.5" height="13.5"></rect>
                                <rect x="58.1" y="405.6" class="st11" width="421.7" height="13.5"></rect>
                                <text transform="matrix(1 0 0 1 59.5073 228.6099)" id="svgnameback" class="st12 st13">John Doe</text>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
            <div id="mercadopago-form" class="form col-sm-6">
                <div class="container">
                    <div class="row">
                        <div class="card-number col-sm-12">
                            <!-- Input Card number -->
                            <label for="id-card-number">
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
                    <div class="row">
                        <div class="expiration col-xs-6">
                            <!-- Input expiration date -->
                            <label for="id-card-expiration"> {l s='Expiration date' mod='mercadopago'} <em class="mp-required">*</em></label>
                            <input id="id-card-expiration" data-checkout="cardExpiration" type="text"
                              class="form-control mp-form-control" autocomplete="off" placeholder="MM/AAAA"
                              onkeyup="maskInput(this, mdate);" maxlength="7" />
                            <small id="mp-error-208" class="mp-erro-form" data-main="#id-card-expiration">
                              {l s='Invalid card expiration date' mod='mercadopago'}</small>
                            <small id="mp-error-209" class="mp-erro-form" data-main="#id-card-expiration">
                              {l s='Invalid card expiration date' mod='mercadopago'}</small>
                            <small id="mp-error-325" class="mp-erro-form" data-main="#id-card-expiration">
                              {l s='Invalid card expiration date' mod='mercadopago'}</small>
                            <small id="mp-error-326" class="mp-erro-form" data-main="#id-card-expiration">
                              {l s='Invalid card expiration date' mod='mercadopago'}</small>
                        </div>
                        <div class="cvv col-xs-6 mp-pl-10">
                            <label for="id-security-code"> {l s='Security code' mod='mercadopago'} <em class="mp-required">*</em></label>
                            <input id="id-security-code" data-checkout="securityCode" type="text"
                              class="form-control mp-form-control" autocomplete="off" 
                              placeholder="{l s='CVV' mod='mercadopago'}" onkeyup="maskInput(this, minteger);"
                                 maxlength="4"/>
                            {*<small class="mp-small mp-pt-5">
                              {l s='last 3 numbers on the back of your card' mod='mercadopago'}
                            </small>*}
                            <small id="mp-error-224" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                              {l s='Invalid card holder name' mod='mercadopago'}</small>
                            <small id="mp-error-E302" class="mp-erro-form mp-pt-0" data-main="#id-security-code">
                              {l s='Invalid card holder name' mod='mercadopago'}</small>
                        </div>
                    </div>
                    <div class="row">
                        <div id="mp-card-holder-div" class="name col-sm-12">
                            <label for="id-card-holder-name"> {l s='Name and surname of the cardholder' mod='mercadopago'} <em class="mp-required">*</em></label>
                            <input id="id-card-holder-name" data-checkout="cardholderName" type="text"
                              class="form-control mp-form-control" autocomplete="off" 
                              onkeyup="javascript:this.value=this.value.toUpperCase();" style="text-transform:uppercase;"/>
                            <small id="mp-error-221" class="mp-erro-form" data-main="#id-card-holder-name">
                              {l s='Invalid card holder name' mod='mercadopago'}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row col-12 mp-pt-15">
                <div class="col-12">
                    <!-- Title installments -->
                    <p class="submp-title-checkout-six mp-pt-10">{l s='In how many installments do you want to pay?' mod='mercadopago'}</p>
                </div>
            </div>
            <div class="row col-12">
                <div class="col-12">
                    <!-- Select issuer -->
                    <div id="container-issuers" class="issuers-options col-md-4 col-4 col-xs-12 mp-m-col">
                        <label for="id-issuers-options" class="issuers-options mp-pb-5 mp-pt-10">{l s='issuing bank' mod='mercadopago'}</label>
                        <select id="id-issuers-options"
                            class="issuers-options form-control mp-form-control mp-select mp-pointer noUniform"
                            data-checkout="issuer" name="mercadopago_custom[issuer]" type="text"></select>
                        <small id="id-issuer-status" class="mp-erro-form"></small>
                    </div>
                </div>
                <div class="col-12">
                    <!-- Select installments -->
                    <div id="container-installments" class="col-md-8 col-8 col-xs-12 mp-m-col">
                        <label for="id-installments" class="mp-pb-5 mp-pt-10">{l s='In how many installments do you want to pay?' mod='mercadopago'}</label>
                        <select class="form-control mp-form-control  mp-pointer not_uniform noUniform" id="id-installments"
                            data-no-uniform="true" data-checkout="installments" name="mercadopago_custom[installments]"
                            type="text"></select>
                        <small id="id-installments-status" class="mp-erro-form"></small>

                            <div class="col-md-12">
                                <div class="mp-text-cft"></div>
                            </div>

                            <div class="col-md-12">
                                <div class="mp-text-tea"></div>
                            </div>
                    </div>
                </div>
            </div>

            <div class="row col-12 mp-pt-15">
                <div class="col-12">
                    <!-- Title Document -->
                    <p class="submp-title-checkout-six mp-pt-10">{l s='Enter your document number' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row col-12">
                <div id="mp-doc-div" class="row col-12">
                    <div id="mp-doc-type-div" class="col-md-4 col-4 mp-pb-20 mp-m-col">
                        <label for="id-docType" class="mp-pb-5">{l s='Type' mod='mercadopago'}</label>
                        <select id="id-docType" data-checkout="docType"
                            class="form-control mp-form-control mp-pointer noUniform"></select>
                    </div>

                    <!-- Input Doc Number -->
                    <div id="mp-doc-number-div" class="col-md-8 col-8 mp-pb-20 mp-m-col">
                        <label for="id-doc-number" class="mp-pb-5">{l s='Document number' mod='mercadopago'}</label>
                        <input id="id-doc-number" data-checkout="docNumber" type="text"
                            class="form-control mp-form-control" onkeyup="maskInput(this, minteger);"
                            autocomplete="off" />
                        </br><small class="mp-small mp-pt-5">{l s='Only numbers' mod='mercadopago'}</small>
                        <small id="mp-error-324" class="mp-erro-form mp-pt-0" data-main="#id-doc-number">
                            {l s='Invalid document number' mod='mercadopago'}</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-m-col mp-pt-20">
                    <p class="mp-all-required"><em class="mp-required text-bold">*</em> {l s='Obligatory field' mod='mercadopago'}</p>
                </div>
            </div>

            <div id="mercadopago-utilities">
                <input type="hidden" id="amount" value="{$amount|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" id="card_token_id" name="mercadopago_custom[card_token_id]" />
                <input type="hidden" id="payment_type_id" name="mercadopago_custom[payment_type_id]" />
                <input type="hidden" id="payment_method_id" name="mercadopago_custom[payment_method_id]" />
                <input type="hidden" id="campaignIdCustom" name="mercadopago_custom[campaign_id]" />
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-pt-15 mp-m-col">
                    <button class="btn btn-default">{l s='Check out' mod='mercadopago'}</button>
                </div>
            </div>

        </div>   
    </div>
</form>
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/custom-script.js" />
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/custom-card.js" />
{if $public_key != ''}
    <script type="text/javascript">
        // Set params to custom-card
        window.onload = loadCustom();
        function loadCustom() {
            var mp_custom = {
                site_id: '{$site_id|escape:"javascript":"UTF-8"}',
                select_choose: '{l s='Choose' mod='mercadopago'}...'
            };
            initializeCustom(mp_custom);
        }

        // Set mercadopago public_key
        if (window.Mercadopago === undefined) {
            $.getScript('https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js').done(function (script, textStatus) {
                // Set Public_key
                Mercadopago.setPublishableKey('{$public_key|escape:"javascript":"UTF-8"}');
            });
        }

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
    </script>
{/if}