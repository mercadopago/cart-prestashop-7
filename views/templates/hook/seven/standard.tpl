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
            {if count($credit) != 0}
                <div class="col-xs-12 col-md-12 col-12">
                    <div class="frame-tarjetas">
                        <p class="mp-subtitle-standard-checkout">
                            {l s='Credit card' mod='mercadopago'}
                            <span class="mp-badge-checkout">
                                {l s='Up to' mod='mercadopago'} {$installments|escape:'html':'UTF-8'} {l s='installments' mod='mercadopago'}
                            </span>
                        </p>

                        <div class="mp-payment-methods-container">
                            {foreach $credit as $tarjeta}
                                <div class="mp-payment-method-logo-container">
                                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}

            {if count($debit) != 0}
                <div class="col-xs-12 col-lg-6 col-md-6 col-12">
                    <div class="frame-tarjetas">
                        <p class="mp-subtitle-standard-checkout">{l s='Debit card' mod='mercadopago'}</p>
                        <div class="mp-payment-methods-container">
                            {foreach $debit as $tarjeta}
                                <div class="mp-payment-method-logo-container">
                                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}

            {if count($ticket) != 0}
                <div class="col-xs-12 col-lg-6 col-md-6 col-12">
                    <div class="frame-tarjetas">
                        <p class="mp-subtitle-standard-checkout">{l s='Wire transfer' mod='mercadopago'}</p>
                        <div class="mp-payment-methods-container">
                            {foreach $ticket as $tarjeta}
                                <div class="mp-payment-method-logo-container">
                                    <img src="{$tarjeta['image']|escape:'html':'UTF-8'}" class="mp-payment-method-logo-image" />
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}

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
        <script>
            window.addEventListener('load', (event) => {
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
            });
        </script>
    {/if}
