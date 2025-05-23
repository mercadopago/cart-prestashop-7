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

<form id="mp_pix_checkout" class="mp-custom-checkout-six" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">
    <div class="row mp-frame-checkout-six">
        <div class="mp-title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}" />
            <p class="mp-m-pt-10">
                {l s='Pix' mod='mercadopago'}
                {if $discount != ""} ({$discount|escape:'htmlall':'UTF-8'}% OFF) {/if}
            </p>
        </div>

        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <div class="row">
                <div class="col-xs-12 col-md-12 col-12 mp-m-col">
                    <div class="mp-pix-container mp-pix-container-column mp-pt-25">
                        <img class="mp-pix-logo" src="{$module_dir|escape:'html':'UTF-8'}views/img/logo_pix.png"/>
                        <label class="mp-pix-text-label mp-pt-20">
                            {l s='[1]When you confirm the purchase,[/1][2]you will be able to see the code to make the instant payment.' tags=['<strong>', '<br>'] mod='mercadopago'}
                        </label>
                    </div>
                    <div class="mp-pix-container mp-pt-25">
                        <img class="mp-badge-info" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/badge_info_gray.png"/>
                        <label class="mp-pix-text-info">
                            {l s='Pix has a daily transfer limit.[1]Please contact your bank for more information.' tags=['<br>'] mod='mercadopago'}
                        </label>
                    </div>
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

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-pt-25 mp-m-col">
                    <button id="mp-pix-finish-order" class="btn btn-primary mp-btn-primary">{l s='Check out' mod='mercadopago'}</button>
                </div>
            </div>
        </div>
    </div>
</form>
