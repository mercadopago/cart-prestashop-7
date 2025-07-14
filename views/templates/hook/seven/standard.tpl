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

    <form id="mp_standard_checkout" class="mp-checkout-form" method="post" action="{$redirect|escape:'html':'UTF-8'}">
        <div class="row mp-frame-checkout-seven">
            {if !empty($standardIcons)}
                {assign var='icon1' value=$standardIcons[0]->link}
                {assign var='icon2' value=$standardIcons[1]->link}
            {/if}
            <div class="col-xs-12 col-md-12 col-12">
                <p class="mp-title-standard-checkout">
                    {l s='Discover how practical Mercado Pago is' mod='mercadopago'}
                </p>
                <div class="mp-container-standard-checkout">
                    <p class="mp-text-standard-checkout">
                        <img src="{$icon1|escape:'html':'UTF-8'}" class="mp-img-standard-checkout" />
                        <span>{l s='Pay with your saved cards' mod='mercadopago'}</span> {l s='or account money without filling out personal details.' mod='mercadopago'}
                    </p>
                    <p class="mp-text-standard-checkout">
                        <img src="{$icon2|escape:'html':'UTF-8'}" class="mp-img-standard-checkout" />
                        <span>{l s='Buy safely' mod='mercadopago'}</span> {l s='with your preferred payment method.' mod='mercadopago'}
                    </p>
                </div>
            </div>
            
            <div class="col-xs-12 col-lg-12 col-md-12 col-12">
                <div class="frame-tarjetas">
                    <div class="mp-payment-methods-container">
                        {foreach $tarjetas as $tarjeta}
                            <div class="mp-payment-method-logo-container">
                                <img src="{$tarjeta.image|default:$tarjeta.thumbnail|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
            
            <div class="col-xs-12 col-lg-12 col-md-12 col-12">
                <div class="mp-payment-methods-message-container">
                    <p class="mp-payment-methods-message">
                        <img src="https://http2.mlstatic.com/storage/cpp/static-files/8afdd939-4197-4030-bf24-90a391cc1dd8.png" class="mp-img-standard-checkout-bw" />
                        <span>{l s='We`ll take you to Mercado Pago' mod='mercadopago'}</span>
                    </p>
                    <p class="mp-payment-methods-message">
                        {l s='If you don`t have an account, you can use your email.' mod='mercadopago'}
                    </p>              
                </div>
            </div>

            {if $modal != true}
                <div class="col-md-12 mp-pt-20">
                    <div class="mp-redirect-frame">
                        <img src="{$module_dir|escape:'html':'UTF-8'}views/img/redirect_checkout.png" class="img-fluid" />
                        <p>{l s='We take you to our site to complete the payment' mod='mercadopago'}</p>
                    </div>
                </div>
            {/if}

            <div class="col-xs-12 col-md-12 col-12 mp-pt-15">
                <label class="mp-pb-5">
                    {l s='By continuing, you agree to our ' mod='mercadopago'}
                    <u>
                        <a class="mp-link-checkout-custom" href="{$terms_url|escape:'html':'UTF-8'}" target="_blank">
                            {l s='Terms and Conditions' mod='mercadopago'}
                        </a>
                    </u>
                </label>
            </div>
        </div>
    </form>

    <script type="text/javascript" src='https://sdk.mercadopago.com/js/v2'></script>

    {if $count == 0}
        <style>
            .mp-frame-checkout-seven {
                padding: 0px;
            }
        </style>
    {/if}

    {if $modal == true}
        {literal}
        <script>
            // support module: onepagecheckoutps - PresTeamShop - Checkout 5.0.
            if (typeof OPC !== typeof undefined) {
                prestashop.on('opc-payment-getPaymentList-complete', () => {
                    initMercadoPagoStandar();
                });
            }

            window.addEventListener('load', (event) => {
                initMercadoPagoStandar();
            });

            function initMercadoPagoStandar() {
                var mp_button = {};

                document.forms['mp_standard_checkout'].onsubmit = function (e) {
                    e.preventDefault();

                    fetch('index.php?fc=module&module=mercadopago&controller=standard')
                    .then(response => response.json())
                    .then(function(response) {
                        if (response.preference) {
                            mp_button = {
                                'preference': {
                                    'id': response.preference['id'],
                                },
                                'autoOpen': true,
                            };

                            var mp = new MercadoPago('{$public_key|escape:"html":"UTF-8"}');
                            mp.checkout(mp_button);

                            return false;
                        }
                        window.location.href = 'index.php?controller=order&step=3&typeReturn=failure';
                    });
                };
            }
        </script>
        {/literal}
    {/if}