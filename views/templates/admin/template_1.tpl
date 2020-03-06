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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel mp-panel-mercadopago">
	<div class="row header-mercadopago">
        <div class="mp-left-details">
            <h2 class="mp-title-checkout-header">{l s='Design the best payment experience for your customers' mod='mercadopago'}</h2>
        </div>
        <div class="mp-right-details">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/mpinfo_logo.png" class="img-fluid header-mp-logo" id="payment-logo" />
        </div>
	</div>

	<hr />
	
	<div class="mercadopago-content">
		<div class="row">
            <div class="col-md-12">
                <h4 class="mp-title-checkout-body">{l s='Follow these steps and maximize your conversion:' mod='mercadopago'}</h4>
            </div>
		</div>
        
        <div class="row mp-pt-15">
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $access_token != '' && $sandbox_access_token != ''}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">1</p>
                {/if}
                
                <p class="mp-text-checkout-body">
                    {l s='Get your' mod='mercadopago'} 
                    {l s='credentials' mod='mercadopago'}
                    {l s='in your Mercado Pago account.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $seller_homolog == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">2</p>
                {/if}
                
                <p class="mp-text-checkout-body">
                    {l s='Approve your account to securely charge your customers.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $standard_test == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">3</p>
                {/if}
                
                <p class="mp-text-checkout-body">
                    {l s='Choose the' mod='mercadopago'} 
                    {l s='payment methods' mod='mercadopago'}
                    {l s='available in your store.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $count_test != 0}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">4</p>
                {/if}
                
                <p class="mp-text-checkout-body">
                    {l s='Activate the' mod='mercadopago'} 
                    {l s='Sandbox' mod='mercadopago'}
                    {l s='test environment to test your store.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center mp-w-25 mp-px-10">
                {if $sandbox_status == true}
                    <p class="mp-number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="mp-number-checkout-body">5</p>
                {/if}
                <p class="mp-text-checkout-body">{l s='Deactivate it if you’re ready to receive payments.' mod='mercadopago'}</p>
            </div>
		</div>
		
		<div class="row mp-pt-30">
            <div class="col-md-6">
                <p class="text-branded lists-how-configure">
                    {l s='Credentials are the keys we provide you to integrate quickly and securely.' mod='mercadopago'}
                    {l s='You must have an approved account in Mercado Pago to collect on your website.' mod='mercadopago'}
                    {l s='You don`t need to know how to design or program to activate us in your store. ' mod='mercadopago'}
                </p>
            </div>
		</div>        
	</div>
</div>

<!-- forms rendered via class from mercadopago.php -->
{html_entity_decode($country_form|escape:'html':'UTF-8')}
{html_entity_decode($credentials|escape:'html':'UTF-8')}

{if $access_token != '' && $public_key != ''  && $sandbox_access_token != '' && $sandbox_public_key != ''}
  
    {if $sandbox_status != true || $seller_homolog == true}
        <div style="display: none">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {else}
        <div style="display: block">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {/if}
    
    {html_entity_decode($store_form|escape:'html':'UTF-8')}

    <!-- Nav tabs checkouts -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#standard_checkout" role="tab" data-toggle="tab">{l s='Mercado Pago Checkout' mod='mercadopago'}</a></li>
        <li><a href="#custom_checkout" role="tab" data-toggle="tab">{l s='Custom Checkout' mod='mercadopago'}</a></li>
        <li><a href="#ticket_checkout" role="tab" data-toggle="tab">{l s='Ticket checkout' mod='mercadopago'}</a></li>
    </ul>
    
    <!-- Tab panes checkouts -->
    <div class="tab-content">
        <div class="tab-pane active" id="standard_checkout">{html_entity_decode($standard_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="custom_checkout">{html_entity_decode($custom_form|escape:'html':'UTF-8')}</div>
        <div class="tab-pane" id="ticket_checkout">{html_entity_decode($ticket_form|escape:'html':'UTF-8')}</div>
    </div>
    
    {if $sandbox_status != true}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Test yor store' mod='mercadopago'}
        </div>        

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mp-title-checkout-body">{l s='Everything set up? Test your store' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row mp-pt-15">
                <div class="col-md-12">
                    <p class="mp-text-credenciais">{l s='Visit your store and simulate payments to check that everything is fine.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">{l s='I want to test my sales' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {else}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Comienza a vender' mod='mercadopago'}
        </div>        

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="mp-title-checkout-body">{l s='You have already gone to production!' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row mp-pt-15">
                <div class="col-md-12">
                    <p class="mp-text-credenciais">{l s='All ready for the takeoff of your sales. Now bring your' mod='mercadopago'}</p>
                    <p class="mp-text-credenciais">{l s='customers to offer them the best online shopping experience with Mercado Pago.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row mp-pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default mp-btn-credenciais">{l s='Visit my store' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {/if}
{/if}

<!-- Evaluation modal -->
<hr class="hr-mp-modal">
<div class="row">
    <div class="col-md-8">
        {l s='Something`s wrong?' mod='mercadopago'}
        
        {if $country_link == 'mlb'}
          <a href="https://www.mercadopago.com.br/developers/pt/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {else}
          <a href="https://www.mercadopago.com.br/developers/es/support" target="_blank">{l s='Get in touch with our support.' mod='mercadopago'}</a>
        {/if}
    </div>
    
    <div class="col-md-4 text-right">
        <a class="mp-link-modal-trigger lists-how-configure" data-toggle="modal" data-target="#mp-rating-modal">
            {l s='Your opinion helps us improving' mod='mercadopago'}
        </a>

        <!-- Modal -->
        <div class="modal mp-rating-modal fade" id="mp-rating-modal" tabindex="-1" role="dialog" aria-labelledby="mp-rating-modal">
            <div class="modal-dialog mp-rating-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header mp-rating-modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title" id="myModalLabel">{l s='Your opinion helps us improving.' mod='mercadopago'}</h3>
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
                                                <input type="radio" value="{$i|escape:'html':'UTF-8'}" name="mercadopago-rating" id="rating{$i|escape:'html':'UTF-8'}" class="mp-pointer" /><br>
                                                <label for="rating{$i|escape:'html':'UTF-8'}" class="label-rating mp-pointer">{$i|escape:'html':'UTF-8'}</label>
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
                                    <p class="label-mp-rating-input"><b>{l s='Comments or suggestions? This is the ideal space:' mod='mercadopago'}</b></p>
                                    <textarea name="mercadopago-comments" class="mp-textarea-rating-module" placeholder="{l s='Write your comment' mod='mercadopago'}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer rating-modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='mercadopago'}</button>
                            <input type="submit" class="btn btn-primary mp-btn-rating-submit" name="submitMercadopagoRating" value="{l s='Send' mod='mercadopago'}" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>