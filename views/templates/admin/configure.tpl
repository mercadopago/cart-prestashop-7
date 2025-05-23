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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<!-- Alert -->
{if $message != ''}
    <div class='alert {$alert|escape:'html':'UTF-8'} alert-dismissible'>
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
        {$message|escape:'html':'UTF-8'}
    </div>
{/if}

<div class="mp-wallet-button-notice mp-mb-15">
    <div class="mp-left-wallet-button">
        <div>
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/mp_saved_cards.png" alt="Saved Cards"/>
        </div>
        <div class="mp-wallet-button-notice-text">
            <p class='mp-wallet-button-notice-title'>
                {l s='Mercado Pago clients can now pay with saved cards' mod='mercadopago'}
            </p>
            <p class='mp-wallet-button-notice-subtitle'>
                <i>{l s='Payment with saved card or Balance in Mercado Pago features are enabled.' mod='mercadopago'}</i> {l s=' You can manage this option in the settings.' mod='mercadopago'}
            </p>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="getCheckoutAnchor('tab-custom', 'a_template_1','custom_checkout')" >{l s='Go to settings' mod='mercadopago'}</button>
</div>

{if (version_compare(_PS_VERSION_, '1.7', '<'))}
    <div class="mp-card-info">
	    <div class="mp-card-color-alert"></div>
	    <div class="mp-card-body">
		    <div class="mp-card-badge-warning"></div>
	    <div>
		    <span class="mp-card-title"><b>{l s='Attention! Mercado Pago checkout for PrestaShop version 1.6 will be disabled.' mod='mercadopago'}</b></span>
		    <span class="mp-card-subtitle">{l s='As of the next update (4.11+), there will be no Mercado Pago checkout for PrestaShop version 1.6.' mod='mercadopago'}</span>
            <span class="mp-card-subtitle">{l s='Follow the instructions and upgrade so you don\'t stop selling.' mod='mercadopago'}</span>
		    <a class="mp-card-button-a" target="_blank" href="https://devdocs.prestashop.com/1.7/basics/keeping-up-to-date/"><button type="button" class="mp-card-button"><b>{l s='SEE HOW TO UPGRADE' mod='mercadopago'}</b></button></a>
	    </div>
	    </div>
    </div>
{/if}

<!-- Nav tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#template_1" role="tab" data-toggle="tab" id="a_template_1">{l s='Set Up Mercado Pago' mod='mercadopago'}</a></li>
    <li><a href="#template_2" role="tab" data-toggle="tab">{l s='About Mercado Pago' mod='mercadopago'}</a></li>
    <li><a href="#template_3" role="tab" data-toggle="tab">{l s='Plugin Log' mod='mercadopago'}</a></li>
    <li class="mp-plugin-version"><a>{l s='Current version:' mod='mercadopago'} <span>v{$mp_version|escape:'html':'UTF-8'}</span></a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane active" id="template_1">{include file='./template_1.tpl'}</div>
    <div class="tab-pane" id="template_2">{include file='./template_2.tpl'}</div>
    <div class="tab-pane" id="template_3">{include file='./template_3.tpl'}</div>
</div>

<!-- JavaScript -->
<script type="text/javascript">
    window.onload = function() {
        var element = document.querySelectorAll("#module_form");
        for (var i=0; i < element.length; i++) {
            element[i].id = "module_form_" + i;
        }


        // ----- country form ----- //
        var form_country_prepend = document.createElement("div");
        var form_country = document.querySelector("#module_form_0 .panel .form-wrapper");

        form_country_prepend.innerHTML = "<div class='row'>\
            <div class='col-md-12 mp-pb-25'>\
                <h4 class='mp-title-checkout-body'>{l s='In which country does your Mercado Pago account operate?' mod='mercadopago'}</h4>\
            </div>\
        </div>";
        form_country.insertBefore(form_country_prepend, form_country.firstChild);


        // ----- credentials form ----- //
        var form_credentials_prepend = document.createElement("div");
        var form_credentials = document.querySelector("#module_form_1 .panel .form-wrapper");
        var form_credentials_inputs = document.querySelectorAll("#module_form_1 .panel .form-wrapper .form-group");

        form_credentials_prepend.innerHTML = "<div class='row'>\
            <div class='col-md-12'>\
                <h4 class='mp-title-checkout-body'>{l s='Enter your credentials and choose how to operate' mod='mercadopago'}</h4>\
            </div>\
        </div>\
        <div class='row mp-pt-5 mp-pb-30'>\
            <div class='col-md-12'>\
                <p class='mp-text-credenciais'><b>{l s='Test Mode' mod='mercadopago'}</b></p>\
                <p class='mp-text-credenciais'>{l s='By default, we leave the test environment (Sandbox) active for you to test before you start selling.' mod='mercadopago'}</p>\
                <p class='mp-text-credenciais mp-pt-15'><b>{l s='Production Mode' mod='mercadopago'}</b></p>\
                <p class='mp-text-credenciais'>{l s='When you see that everything is going well, disable Sandbox to go to Production and make way for your online sales.' mod='mercadopago'}</p>\
            </div>\
        </div>";
        form_credentials.insertBefore(form_credentials_prepend, form_credentials.firstChild);

        var form_credentials_pruebas_append = "<div class='row mp-pt-20 mp-mb-15'>\
            <div class='col-md-12'>\
                <p class='mp-title-credenciais'>{l s='Test Credentials' mod='mercadopago'}</p>\
                <p class='mp-text-credenciais mp-pt-5 mp-pb-10'>{l s='With these keys you can do the tests you want' mod='mercadopago'}</p>\
            </div>\
        </div>";

        var form_credentials_produccion_append = "<div class='row mp-pt-20 mp-mb-15'>\
            <div class='col-md-12'>\
                <p class='mp-title-credenciais'>{l s='Production Credentials' mod='mercadopago'}</p>\
                <p class='mp-text-credenciais mp-pt-5 mp-pb-10'>{l s='With these keys you can receive real payments from your customers.' mod='mercadopago'}</p>\
            </div>\
        </div>";

        for (var i=0; i < form_credentials_inputs.length; i++) {
            if(i == 1){
                form_credentials_inputs[i].insertAdjacentHTML('afterend', form_credentials_produccion_append);
            }
            else if(i == 3){
                form_credentials_inputs[i].insertAdjacentHTML('afterend', form_credentials_pruebas_append);
            }
        }


        // ----- homolog form ----- //
        var form_homolog = document.querySelector("#module_form_2 .panel .form-wrapper");

        form_homolog.innerHTML = "<div class='row'>\
            <div class='col-md-12 mp-pb-10'>\
                <h4 class='mp-title-checkout-body'>{l s='Approve your account, it will only take a few minutes' mod='mercadopago'}</h4>\
            </div>\
            <div class='col-md-6'>\
                <p class='text-branded lists-how-configure mp-pb-10'>\
                    {l s='Complete this process to ensure the data' mod='mercadopago'} \
                    {l s='of your customers and the adaptation to the regulations or legal ' mod='mercadopago'} \
                    {l s='provisions of each country.' mod='mercadopago'} \
                </p>\
                <a href='https://www.mercadopago.com/{$country_link|escape:'javascript':'UTF-8'}/account/credentials/appliance?application_id={$application|escape:'javascript':'UTF-8'}' class='btn btn-default mp-btn-credenciais mp-mb-10' target='_blank'>{l s='Approve my account' mod='mercadopago'}</a> \
            </div>\
        </div>";


        // ----- store information form ------ //
        var form_store_prepend = document.createElement("div");
        var form_store = document.querySelector("#module_form_3 .panel .form-wrapper");
        var form_store_group = document.querySelectorAll("#module_form_3 .panel .form-wrapper .form-group");

        form_store_prepend.innerHTML = "<div class='row mp-pb-25'>\
            <div class='col-md-12'>\
                <h4 class='mp-title-checkout-body'>{l s='Store Information' mod='mercadopago'}</h4>\
                <p class='mp-text-credenciais mp-pb-10'>{l s='Enter your business details in the module:' mod='mercadopago'}</p>\
            </div>\
        </div>";
        form_store.insertBefore(form_store_prepend, form_store.firstChild);

        var form_store_append = "<hr class='mp-mt-15'>\
        <div class='row mp-mb-15'>\
            <div class='col-md-12'>\
                <h4 class='mp-title-checkout-body'>{l s='Are you a Mercado Pago partner?' mod='mercadopago'}</h4>\
            </div>\
        </div>";

        for (i=0; i < form_store_group.length; i++) {
            if(i == 1){
                form_store_group[i].insertAdjacentHTML('afterend', form_store_append);
            }
            else if(i == 2){
                form_store_group[i].querySelector("p").style.width = "400px";
            }
        }

        // ----- standard configuration form ------ //
        {include file='./checkouts/standard_configuration.tpl'}

        // ----- custom configuration form ------ //
        {include file='./checkouts/custom_configuration.tpl'}

        // ----- ticket configuration form ------ //
        {include file='./checkouts/ticket_configuration.tpl'}

        // ----- pix configuration form ------ //
        {include file='./checkouts/pix_configuration.tpl'}

        // ----- pse configuration form ------ //
        {include file='./checkouts/pse_configuration.tpl'}
    }

    //Online payments
    function completeOnlineCheckbox(){
        var onlineCheck = document.getElementById("checkmeon").checked;
        var onlineInputs = document.querySelectorAll(".payment-online-checkbox");
        for (var i=0; i < onlineInputs.length; i++) {
            if(onlineCheck == true){
                onlineInputs[i].checked = true;
            }
            else{
                onlineInputs[i].checked = false;
            }
        }
    }

    //Offline payments
    function completeOfflineCheckbox(){
        var offlineCheck = document.getElementById("checkmeoff").checked;
        var offlineInputs = document.querySelectorAll(".payment-offline-checkbox");
        for (var i=0; i < offlineInputs.length; i++) {
            if(offlineCheck == true){
                offlineInputs[i].checked = true;
            }
            else{
                offlineInputs[i].checked = false;
            }
        }
    }

    //Ticket payments
    function completeTicketCheckbox(){
        var ticketCheck = document.getElementById("checkmeticket").checked;
        var ticketInputs = document.querySelectorAll(".payment-ticket-checkbox");
        for (var i=0; i < ticketInputs.length; i++) {
            if(ticketCheck == true){
                ticketInputs[i].checked = true;
            }
            else{
                ticketInputs[i].checked = false;
            }
        }
    }

    //PSJ button
    function getPsjButton(){
        const country_link = '{$country_link|escape:'javascript':'UTF-8'}';

        const textHeader = (country_link.toLowerCase() == 'mco') ? "{l s='Set up your interest payments' mod='mercadopago'}" : "{l s='Set up your installment and interest payments' mod='mercadopago'}";
        const textBody = (country_link.toLowerCase() == 'mco') ? "{l s='At Mercado Pago you can choose the fee you pay for each purchase.' mod='mercadopago'}" : "{l s='At Mercado Pago you can choose the fee you pay for each purchase and also offer interest-free installments to your customer.' mod='mercadopago'}";
        const textButton = (country_link.toLowerCase() == 'mco') ? "{l s='Set up interest payments' mod='mercadopago'}" : "{l s='Set up installment and interest' mod='mercadopago'}";

        return "<hr class='mp-mt-15'>\
            <div class='row'>\
                <div class='col-md-12 mp-pb-10'>\
                    <h4 class='mp-title-checkout-body'>" + textHeader + "</h4>\
                </div>\
            </div>\
            <div class='row mp-pt-5 mp-pb-25'>\
                <div class='col-md-12'>\
                    <p class='mp-text-credenciais mp-pb-30'>" + textBody + "</p>\
                    <a  href='{$psjLink|escape:'javascript':'UTF-8'}' class='btn btn-default mp-btn-credenciais mp-mb-10 mp-w-300' target='_blank'>" + textButton + "</a>\
                </div>\
            </div>";
    }

    //Banner button
    function getCheckoutAnchor(tab, template, checkout) {
        var containerTab = document.getElementById(tab);
        var templateTab = document.getElementById(template);
        templateTab.click();

        if (containerTab) {
            containerTab.click();
            document.getElementById(checkout).scrollIntoView();
        }
    }
</script>
