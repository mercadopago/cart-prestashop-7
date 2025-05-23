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

// ----- ticket configuration form ------ //
//ticket form header
document.querySelector("#module_form_6 .panel").style.borderTopLeftRadius = 0;
var form_ticket_header_prepend = document.createElement("div");
var form_ticket_header = document.querySelector("#module_form_6 .panel .panel-heading");
form_ticket_header.style.height = "auto";

form_ticket_header_prepend.innerHTML = "<ul class='mp-checkout-list'>\
    <li><span>{l s='Offer cash payments.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Payment experience within your store.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Your customers pay as guests without leaving your store.' mod='mercadopago'}</span></li>\
</ul>";
form_ticket_header.insertBefore(form_ticket_header_prepend, form_ticket_header.firstChild);

var form_ticket_prepend = document.createElement("div");
var form_ticket = document.querySelector("#module_form_6 .panel .form-wrapper");
var form_ticket_group = document.querySelectorAll("#module_form_6 .panel .form-wrapper .form-group");

form_ticket_prepend.innerHTML = "<div class='row mp-pb-25'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body'>{l s='Your customer will make their purchase quickly, easily and safely with these settings:' mod='mercadopago'}</h4>\
    </div>\
</div>";
form_ticket.insertBefore(form_ticket_prepend, form_ticket.firstChild);

// ticket payments
var ticketChecked = "";
var countTicketChecked = 0;
var countOfflineInputs = document.querySelectorAll(".payment-offline-checkbox").length;
var ticketInputs = document.querySelectorAll(".payment-ticket-checkbox");
for(var ion=0; ion < ticketInputs.length; ion++){
    if(ticketInputs[ion].checked == true){
        countTicketChecked += 1;
    }
}
if(countTicketChecked == ticketInputs.length){
    ticketChecked = "checked";
}

var countStandarPayments = countOnlineInputs + countOfflineInputs;
var checkbox_ticket = document.querySelector("#checkbox_"+countStandarPayments);
var checkbox_ticket_prepend = "<div class='mp-all_checkbox'>\
    <input type='checkbox' name='checkmeticket' id='checkmeticket' "+ticketChecked+" onclick='completeTicketCheckbox()'> \
    <label for='checkmeticket'><b class='mp-pointer mp-no-select mp-pl-5'>{l s='Select face payments' mod='mercadopago'}</b></label>\
</div>";
checkbox_ticket.insertAdjacentHTML('beforebegin', checkbox_ticket_prepend);

//advanced configuration
var form_ticket_append = "<div class='panel-heading mp-panel-advanced-config'>\
    <i class='icon-cogs'></i> {l s='Advanced Configuration' mod='mercadopago'}\
    <span class='mp-btn-collapsible' id='header_plus_ticket' style='display:block'>+</span>\
    <span class='mp-btn-collapsible' id='header_less_ticket' style='display:none'>-</span>\
</div>\
<div class='row text-ticket-advanced'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body mp-ticket-input-collapsible'>{l s='Activate other tools in our module ready to use.' mod='mercadopago'}</h4>\
    </div>\
</div>";

for (i=0; i < form_ticket_group.length; i++) {
    if(i == 2){
        form_ticket_group[i].querySelector("p").style.width = "400px";
        form_ticket_group[i].insertAdjacentHTML('afterend', form_ticket_append);
    }
    if(i > 2) {
        form_ticket_group[i].querySelector("p").style.width = "400px";
        form_ticket_group[i].classList.add("mp-ticket-input-collapsible");
    }
}

var style_collapsible_ticket = false;
var header_plus_ticket = document.querySelector("#header_plus_ticket");
var header_less_ticket = document.querySelector("#header_less_ticket");
var form_ticket_collapsible = document.querySelector("#module_form_6 .panel .mp-panel-advanced-config");
var form_ticket_collapsible_body = document.querySelectorAll(".mp-ticket-input-collapsible");
var form_ticket_collapsible_footer = document.querySelector("#module_form_6 .panel .panel-footer");

form_ticket_collapsible_footer.style.marginTop = "-2px";

form_ticket_collapsible.onclick = function(){
    if(style_collapsible_ticket == false){
        style_collapsible_ticket = true;
        header_less_ticket.style.display = "block";
        header_plus_ticket.style.display = "none";
        form_ticket_collapsible_footer.style.marginTop = "15px";
        document.querySelector(".text-ticket-advanced").style.paddingTop = "20px";
        document.querySelector(".text-ticket-advanced").style.paddingBottom = "25px";

        for(i=0; i<form_ticket_collapsible_body.length; i++){
            form_ticket_collapsible_body[i].style.display = "block";
        }
    }
    else{
        style_collapsible_ticket = false;
        header_less_ticket.style.display = "none";
        header_plus_ticket.style.display = "block";
        form_ticket_collapsible_footer.style.marginTop = "-2px";
        document.querySelector(".text-ticket-advanced").style.paddingTop = "0px";
        document.querySelector(".text-ticket-advanced").style.paddingBottom = "0px";

        for(i=0; i<form_ticket_collapsible_body.length; i++){
            form_ticket_collapsible_body[i].style.display = "none";
        }
    }
}
