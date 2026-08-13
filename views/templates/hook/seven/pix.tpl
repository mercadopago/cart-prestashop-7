{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}

<form id="mp_pix_checkout" class="mp-checkout-form" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">
    <div class="row mp-frame-checkout-custom-seven">
        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">

            <div class="form-group">
                <div class="col-xs-12 col-md-12 col-12 mp-m-col">
                    <div class="mp-pix-container mp-pix-container-column mp-pt-25">
                        <img class="mp-pix-logo" src="{$module_dir|escape:'html':'UTF-8'}views/img/logo_pix.png"/>
                        <label class="mp-pix-text-label mp-pt-20">
                            <strong>{l s='When you confirm the purchase, ' mod='mercadopago'}</strong> <br> {l s='you will be able to see the code to make the instant payment.' mod='mercadopago'}
                        </label>
                    </div>
                    <div class="mp-pix-container mp-pt-25">
                        <img class="mp-badge-info" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/badge_info_gray.png"/>
                        <label class="mp-pix-text-info">
                            {l s='Pix has a daily transfer limit.' mod='mercadopago'} <br> {l s='Please contact your bank for more information.' mod='mercadopago'}
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-xs-12 col-md-12 col-12 mp-m-col mp-pt-25">
                    <p class="mp-label-checkout-custom">
                        {l s='By continuing, you agree to our ' mod='mercadopago'}
                        <u>
                            <a class="mp-link-checkout-custom" href={$terms_url|escape:"html":"UTF-8"} target="_blank">
                                {l s='Terms and Conditions' mod='mercadopago'}
                            </a>
                        </u>
                    </p>
                </div>
            </div>

        </div>
    </div>
</form>
