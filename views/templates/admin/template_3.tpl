{*
 * 2007-2021 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Logging' mod='mercadopago'}
    </div>

    <div class="mercadopago-content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="mp-title-checkout-body">{l s='Here you can see Mercado Pago\'s log.' mod='mercadopago'}</h4>
            </div>
        </div>

        <div class="row mp-pt-15">
            <div class="col-md-12">
                <p class="mp-text-credenciais">
                    {l s='Only for support reasons. Does not share this with unauthorized people!' mod='mercadopago'}
                </p>
            </div>
        </div>

        <div class="row mp-pt-25">
            <div class="col-xs-12">
                <a href="{$log|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">
                    {l s='See log' mod='mercadopago'}
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Evaluation modal -->
<hr class="hr-mp-modal">
<div class="row">
    <div class="col-md-8">
        {l s='Something`s wrong?' mod='mercadopago'}

        {if $country_link == 'mlb'}
            <a href="https://www.mercadopago.com.br/developers/pt/support" target="_blank">
                {l s='Get in touch with our support.' mod='mercadopago'}
            </a>
        {else}
            <a href="https://www.mercadopago.com.br/developers/es/support" target="_blank">
                {l s='Get in touch with our support.' mod='mercadopago'}
            </a>
        {/if}
    </div>

    <div class="col-md-4 text-right">
        <a class="mp-link-modal-trigger lists-how-configure" data-toggle="modal" data-target="#mp-rating-modal">
            {l s='Your opinion helps us improving' mod='mercadopago'}
        </a>

        <!-- Modal -->
        <div class="modal mp-rating-modal fade" id="mp-rating-modal" tabindex="-1" role="dialog"
            aria-labelledby="mp-rating-modal">
            <div class="modal-dialog mp-rating-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header mp-rating-modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title" id="myModalLabel">
                            {l s='Your opinion helps us improving.' mod='mercadopago'}</h3>
                    </div>

                    <form action="" method="post">
                        <div class="modal-body mp-rating-modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="label-mp-rating-input">
                                        {l s='From 1 to 10, how likely are you to recommend our module to a friend?' mod='mercadopago'}
                                    </p>
                                    <div class="mp-rating-box mp-pb-10">
                                        {for $i=1 to 10 step 1}
                                            <div class="mp-rating-input">
                                                <input type="radio" value="{$i|escape:'html':'UTF-8'}"
                                                    name="mercadopago-rating" id="rating{$i|escape:'html':'UTF-8'}"
                                                    class="mp-pointer" /><br>
                                                <label for="rating{$i|escape:'html':'UTF-8'}"
                                                    class="label-rating mp-pointer">{$i|escape:'html':'UTF-8'}</label>
                                            </div>
                                        {/for}
                                    </div>

                                    <div class="col-md-4">
                                        <p>1 - {l s='Nothing likely' mod='mercadopago'}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mp-text-center">5 - {l s='Unlikely' mod='mercadopago'}</p>
                                    </div>

                                    <div class="col-md-4">
                                        <p class="mp-fl-right">10 - {l s='Very likely' mod='mercadopago'}</p>
                                    </div>
                                </div>

                                <div class="col-md-12 mp-pt-30">
                                    <p class="label-mp-rating-input">
                                        <b>{l s='Comments or suggestions? This is the ideal space:' mod='mercadopago'}</b>
                                    </p>
                                    <textarea name="mercadopago-comments" class="mp-textarea-rating-module"
                                        placeholder="{l s='Write your comment' mod='mercadopago'}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer rating-modal-footer">
                            <button type="button" class="btn btn-default"
                                data-dismiss="modal">{l s='Close' mod='mercadopago'}</button>
                            <input type="submit" class="btn btn-primary mp-btn-rating-submit"
                                name="submitMercadopagoRating" value="{l s='Send' mod='mercadopago'}" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
