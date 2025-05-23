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

// ----- custom configuration form ------ //
//custom form header
document.querySelector("#module_form_5 .panel").style.borderTopLeftRadius = 0;
var form_custom_header_prepend = document.createElement("div");
var form_custom_header = document.querySelector("#module_form_5 .panel .panel-heading");
form_custom_header.style.height = "auto";

form_custom_header_prepend.innerHTML = "<ul class='mp-checkout-list'>\
    <li><span>{l s='Offer payments with credit cards and Mercado Pago balance.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Payment experience in your store.' mod='mercadopago'}</span></li>\
    <li><span>{l s='Your clients pay as visitors without leaving your store.' mod='mercadopago'}</span></li>\
</ul>";
form_custom_header.insertBefore(form_custom_header_prepend, form_custom_header.firstChild);

var form_custom_prepend = document.createElement("div");
var form_custom = document.querySelector("#module_form_5 .panel .form-wrapper");
var form_custom_group = document.querySelectorAll("#module_form_5 .panel .form-wrapper .form-group");

form_custom_prepend.innerHTML = "<div class='row mp-pb-25'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body'>{l s='With these options, your clients pay quickly, easily, and securely:' mod='mercadopago'}</h4>\
    </div>\
</div>";
form_custom.insertBefore(form_custom_prepend, form_custom.firstChild);

//advanced configuration
var form_custom_append = "<div class='panel-heading mp-panel-advanced-config'>\
    <i class='icon-cogs'></i> {l s='Advanced Configuration' mod='mercadopago'}\
    <span class='mp-btn-collapsible' id='header_plus_custom' style='display:block'>+</span>\
    <span class='mp-btn-collapsible' id='header_less_custom' style='display:none'>-</span>\
</div>\
<div class='row text-custom-advanced'>\
    <div class='col-md-12'>\
        <h4 class='mp-title-checkout-body mp-custom-input-collapsible'>{l s='Activate other tools in our module ready to use.' mod='mercadopago'}</h4>\
    </div>\
</div>";

for (i=0; i < form_custom_group.length; i++) {
    if(i == 1){
        form_custom_group[i].insertAdjacentHTML('afterend', form_custom_append);
        form_custom_group[i].insertAdjacentHTML('afterend', form_standard_psj_append);
    }
    if(i > 1) {
        form_custom_group[i].classList.add("mp-custom-input-collapsible");
    }
    form_custom_group[i].querySelector("p").style.width = "400px";
}

var style_collapsible_custom = false;
var header_plus_custom = document.querySelector("#header_plus_custom");
var header_less_custom = document.querySelector("#header_less_custom");
var form_custom_collapsible = document.querySelector("#module_form_5 .panel .mp-panel-advanced-config");
var form_custom_collapsible_body = document.querySelectorAll(".mp-custom-input-collapsible");
var form_custom_collapsible_footer = document.querySelector("#module_form_5 .panel .panel-footer");
var form_custom_group = document.querySelectorAll("#module_form_5 .panel .form-wrapper .form-group");

form_custom_collapsible_footer.style.marginTop = "-2px";

form_custom_collapsible.onclick = function(){
    if(style_collapsible_custom == false){
        style_collapsible_custom = true;
        header_less_custom.style.display = "block";
        header_plus_custom.style.display = "none";
        form_custom_collapsible_footer.style.marginTop = "15px";
        document.querySelector(".text-custom-advanced").style.paddingTop = "20px";
        document.querySelector(".text-custom-advanced").style.paddingBottom = "25px";

        for(i=0; i<form_custom_collapsible_body.length; i++){
            form_custom_collapsible_body[i].style.display = "block";
        }
    }
    else{
        style_collapsible_custom = false;
        header_less_custom.style.display = "none";
        header_plus_custom.style.display = "block";
        form_custom_collapsible_footer.style.marginTop = "-2px";
        document.querySelector(".text-custom-advanced").style.paddingTop = "0px";
        document.querySelector(".text-custom-advanced").style.paddingBottom = "0px";

        for(i=0; i<form_custom_collapsible_body.length; i++){
            form_custom_collapsible_body[i].style.display = "none";
        }
    }
}
