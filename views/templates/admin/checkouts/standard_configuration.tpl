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

// ----- standard configuration form ------ //
//standard form header
document.querySelector("#module_form_4 .panel").style.borderTopLeftRadius = 0;
var form_standard_header_prepend = document.createElement("div");
var form_standard_header = document.querySelector("#module_form_4 .panel .panel-heading");
form_standard_header.style.height = "auto";

form_standard_header_prepend.innerHTML = "<ul class='mp-checkout-list'>\
    <li><span>{l s='Offers all payment methods.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Payment experience on the Mercado Pago site.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Your customers can pay as guests or by entering their Mercado Pago account.' mod='mercadopago'}</span></li>\
</ul>";
form_standard_header.insertBefore(form_standard_header_prepend, form_standard_header.firstChild);

//standard form body
var checkbox = document.querySelectorAll(".checkbox");
for (var ii=0; ii < checkbox.length; ii++) {
    checkbox[ii].id = "checkbox_"+ii;
    checkbox[ii].style.border = "1px solid #ccc";
    checkbox[ii].style.padding = "10px";
}

var form_standard_prepend = document.createElement("div");
var form_standard = document.querySelector("#module_form_4 .panel .form-wrapper");
var form_standard_group = document.querySelectorAll("#module_form_4 .panel .form-wrapper .form-group");

form_standard_prepend.innerHTML = "<div class='row mp-pb-25'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body'>{l s='Have your customers finish their purchase with these basic settings:' mod='mercadopago'}</h4>\
    </div>\
</div>";
form_standard.insertBefore(form_standard_prepend, form_standard.firstChild);

// online payments configuration form
var onlineChecked = "";
var countOnlineChecked = 0;
var onlineInputs = document.querySelectorAll(".payment-online-checkbox");
for(var ion=0; ion < onlineInputs.length; ion++){
    if(onlineInputs[ion].checked == true){
        countOnlineChecked += 1;
    }
}
if(countOnlineChecked == onlineInputs.length){
    onlineChecked = "checked";
}

var checkbox_online = document.querySelector("#checkbox_0");
var checkbox_online_prepend = "<div class='mp-all_checkbox'>\
    <input type='checkbox' name='checkmeon' id='checkmeon' "+onlineChecked+" onclick='completeOnlineCheckbox()'> \
    <label for='checkmeon'><b class='mp-pointer mp-no-select mp-pl-5'>{l s='Payment methods' mod='mercadopago'}</b></label>\
</div>";
checkbox_online.insertAdjacentHTML('beforebegin', checkbox_online_prepend);

// offline payments configuration form
var offlineChecked = "";
var countOfflineChecked = 0;
var offlineInputs = document.querySelectorAll(".payment-offline-checkbox");
for(var ioff=0; ioff < offlineInputs.length; ioff++){
    if(offlineInputs[ioff].checked == true){
        countOfflineChecked += 1;
    }
}
if(countOfflineChecked == offlineInputs.length){
    offlineChecked = "checked";
}

var countOnlineInputs = document.querySelectorAll(".payment-online-checkbox").length;
var checkbox_offline = document.querySelector("#checkbox_"+countOnlineInputs);
var checkbox_offline_prepend = "<div class='mp-all_checkbox'>\
    <input type='checkbox' name='checkmeoff' id='checkmeoff' "+offlineChecked+" onclick='completeOfflineCheckbox()'> \
    <label for='checkmeoff'><b class='mp-pointer mp-no-select mp-pl-5'>{l s='Select face payments' mod='mercadopago'}</b></label>\
</div>";
checkbox_offline.insertAdjacentHTML('beforebegin', checkbox_offline_prepend);

//standard psj
var form_standard_psj_append = getPsjButton();

//advanced configuration
var form_standard_append = "<div class='panel-heading mp-panel-advanced-config'>\
    <i class='icon-cogs'></i> {l s='Advanced Configuration' mod='mercadopago'}\
    <span class='mp-btn-collapsible' id='header_plus_standard' style='display:block'>+</span>\
    <span class='mp-btn-collapsible' id='header_less_standard' style='display:none'>-</span>\
</div>\
<div class='row text-standard-advanced'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body mp-input-collapsible'>{l s='Activate other tools in our module ready to use.' mod='mercadopago'}</h4>\
    </div>\
</div>";

for (i=0; i < form_standard_group.length; i++) {
    if(i == 3){
        form_standard_group[i].insertAdjacentHTML('afterend', form_standard_append);
        form_standard_group[i].insertAdjacentHTML('afterend', form_standard_psj_append);
    }
    else if(i > 3) {
        form_standard_group[i].classList.add("mp-input-collapsible");
    }

    form_standard_group[i].querySelector("p").style.width = "400px";
}

var style_collapsible = false;
var header_plus_standard = document.querySelector("#header_plus_standard");
var header_less_standard = document.querySelector("#header_less_standard");
var form_standard_collapsible = document.querySelector("#module_form_4 .panel .mp-panel-advanced-config");
var form_standard_collapsible_body = document.querySelectorAll(".mp-input-collapsible");
var form_standard_collapsible_footer = document.querySelector("#module_form_4 .panel .panel-footer");

form_standard_collapsible_footer.style.marginTop = "-2px";

form_standard_collapsible.onclick = function(){
    if(style_collapsible == false){
        style_collapsible = true;
        header_less_standard.style.display = "block";
        header_plus_standard.style.display = "none";
        form_standard_collapsible_footer.style.marginTop = "15px";
        document.querySelector(".text-standard-advanced").style.paddingTop = "20px";
        document.querySelector(".text-standard-advanced").style.paddingBottom = "25px";

        for(i=0; i<form_standard_collapsible_body.length; i++){
            form_standard_collapsible_body[i].style.display = "block";
        }
    }
    else{
        style_collapsible = false;
        header_less_standard.style.display = "none";
        header_plus_standard.style.display = "block";
        form_standard_collapsible_footer.style.marginTop = "-2px";
        document.querySelector(".text-standard-advanced").style.paddingTop = "0px";
        document.querySelector(".text-standard-advanced").style.paddingBottom = "0px";

        for(i=0; i<form_standard_collapsible_body.length; i++){
            form_standard_collapsible_body[i].style.display = "none";
        }
    }
}
