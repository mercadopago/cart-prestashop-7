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

<form id="mp_pix_checkout" class="mp-checkout-form" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">
    <div class="row mp-frame-checkout-custom-seven">
        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            
            <div class="form-group">
                <div class="col-xs-12 col-md-12 col-12 mp-m-col">  
                    <div class="mp-pix-container mp-pix-container-column mp-pt-25">
                        <img class="mp-pix-logo" src="{$logo_pix|escape:'html':'UTF-8'}"/>
                        <label class="mp-pix-text-label mp-pt-20">
                            {l s='[1]Ao confirmar a compra,[/1][2]você verá o código para fazer o pagamento instantâneo.' tags=['<strong>', '<br>'] mod='mercadopago'}
                        </label>
                    </div>
                    <div class="mp-pix-container mp-pt-25">
                        <img class="mp-badge-info" src="{$badge_info_gray|escape:'html':'UTF-8'}"/>
                        <label class="mp-pix-text-info">
                            {l s='O Pix possui limite diário de transferência.[1]Consulte o seu banco para mais informações.' tags=['<br>'] mod='mercadopago'}
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-xs-12 col-md-12 col-12 mp-px-0 mp-m-col mp-pt-25">
                    <label class="mp-pb-5">
                        {l s='By continuing, you agree to our ' mod='mercadopago'}
                        <u><a class="mp-link-checkout-custom" href={$terms_url|escape:"html":"UTF-8"} target="_blank">
                            {l s='Terms and Conditions' mod='mercadopago'}
                        </a></u>
                    </label>
                </div>
            </div>

        </div>
    </div>
</form>
