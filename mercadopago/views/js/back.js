/**
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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

window.onload = function () {
    var element = document.querySelectorAll("#module_form");
    for (var i=0; i < element.length; i++) {
        element[i].id = "module_form_" + i;
    }
    
    //credentials form
    var form_credentials = document.querySelector("#module_form_0 .panel .form-wrapper");
    var form_prepend = document.createElement("div");
    
    form_prepend.innerHTML = "<div class='row'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>Activa tus credenciales según lo que quieras hacer</h4>\
        </div>\
    </div>\
    <div class='row mp-pt-15 mp-pb-25'>\
        <div class='col-md-12'>\
            <p class='mp-text-credenciais'>Realiza pruebas antes de salir al mundo. <b>Opera de dos formas</b>:</p>\
            <p class='mp-text-credenciais'>Por defecto te dejamos <b>el modo Sandbox activo</b> para que hagas testeos antes de empezar a vender.</p>\
            <p class='mp-text-credenciais'>¿Todo va bien? <b>Desactiva Sandbox</b> al final de la configuración y abre paso a tus ventas online.</p>\
        </div>\
    </div>";
    
    form_credentials.insertBefore(form_prepend, form_credentials.firstChild);
    
    form_credentials.innerHTML += "<div class='row'>\
        <div class='col-md-6'>\
            <p class='text-branded lists-how-configure'>\
                <b>Atención:</b> Crea una cuenta en Mercado Pago para obtener tus credenciales. \
                <a href='#' target='_blank'>Homologa tu cuenta</a> en Mercado Pago para ir a Producción y cobrar en tu tienda.\
            </p>\
        </div>\
    </div>";
    
    //basic configuration form
    var checkbox = document.querySelectorAll(".checkbox");
    for (var ii=0; ii < checkbox.length; ii++) {
        checkbox[ii].id = "checkbox_"+ii;
        checkbox[ii].style.border = "1px solid #ccc";
        checkbox[ii].style.padding = "10px";
    }
    
    var form_basic = document.querySelector("#module_form_1 .panel .form-wrapper");
    var form_basic_prepend = document.createElement("div");
    
    form_basic_prepend.innerHTML = "<div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>Hagamos que tu cliente termine su compra de forma rápida, fácil y segura.</h4>\
        </div>\
    </div>";
    
    form_basic.insertBefore(form_basic_prepend, form_basic.firstChild);
    
    //online payments configuration form
    var onlineChecked = "";
    var countOnlineChecked = 0;
    var onlineInputs = document.querySelectorAll(".payment-online-checkbox");
    for (var ion=0; ion < onlineInputs.length; ion++) {
        if (onlineInputs[ion].checked == true) {
            countOnlineChecked += 1;
        }
    }
    if (countOnlineChecked == onlineInputs.length) {
        onlineChecked = "checked";
    }
    
    var checkbox_online = document.querySelector("#checkbox_0");
    var checkbox_online_prepend = "<div class='mp-all_checkbox'>\
            <input type='checkbox' name='checkmeon' id='checkmeon' "+onlineChecked+" onclick='completeOnlineCheckbox()'> \
            <label for='checkmeon'><b class='mp-pointer mp-pl-5'>Payment methods</b></label>\
    </div>";
    checkbox_online.insertAdjacentHTML('beforebegin', checkbox_online_prepend);
    
    //offline payments configuration form
    var offlineChecked = "";
    var countOfflineChecked = 0;
    var offlineInputs = document.querySelectorAll(".payment-offline-checkbox");
    for (var ioff=0; ioff < offlineInputs.length; ioff++) {
        if (offlineInputs[ioff].checked == true) {
            countOfflineChecked += 1;
        }
    }
    if (countOfflineChecked == offlineInputs.length) {
        offlineChecked = "checked";
    }
    
    var countOnlineInputs = document.querySelectorAll(".payment-online-checkbox").length;
    var checkbox_offline = document.querySelector("#checkbox_"+countOnlineInputs);
    var checkbox_offline_prepend = "<div class='mp-all_checkbox'>\
            <input type='checkbox' name='checkmeoff' id='checkmeoff' "+offlineChecked+" onclick='completeOfflineCheckbox()'> \
            <label for='checkmeoff'><b class='mp-pointer mp-pl-5'>Select face payments</b></label>\
    </div>";
    checkbox_offline.insertAdjacentHTML('beforebegin', checkbox_offline_prepend);
    
    //advanced configuration form
    var form_module = document.querySelector("#module_form_2 .panel .panel-heading");
    var collapse_body = document.querySelector("#module_form_2 .panel .form-wrapper");
    var collapse_body_prepend = document.createElement("div");
    var collapse_footer = document.querySelector("#module_form_2 .panel .panel-footer");
    var collapse_panel = document.querySelector("#module_form_2 .panel");
    var form_group = document.querySelectorAll("#module_form_2 .panel .form-wrapper .form-group");
    
    for (i=0; i < form_group.length; i++) {
        if (i == 2) {
            form_group[i].innerHTML += "<hr class='mp-mt-50'>\
            <div class='row'>\
                <div class='col-md-12'>\
                    <h4 class='mp-title-checkout-body'>¿Eres un partner de Mercado Pago?</h4>\
                </div>\
            </div>";
        }
    }
    
    form_module.style.cursor = "mp-pointer";
    form_module.innerHTML += "<span class='mp-btn-collapsible' id='header_plus' style='display:block'>+</span>\
        <span class='mp-btn-collapsible' id='header_less' style='display:none'>-</span>";
    
    collapse_body_prepend.innerHTML = "<div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>Activate other tools in our module ready to use.</h4>\
        </div>\
    </div>";
    
    collapse_body.insertBefore(collapse_body_prepend, collapse_body.firstChild);
    
    var header_plus = document.querySelector("#header_plus");
    var header_less = document.querySelector("#header_less");
    
    collapse_body.style.display = "none";
    collapse_footer.style.display = "none";
    form_module.style.borderBottom = "0";
    form_module.style.marginBottom = "0";
    collapse_panel.style.paddingBottom = "0";
    
    form_module.onclick = function () {
        if (collapse_body.style.display == "none") {
            collapse_body.style.display = "block";
            collapse_footer.style.display = "block";
            header_less.style.display = "block";
            header_plus.style.display = "none";
            
            form_module.style.borderBottom = "1px solid #eee";
            form_module.style.marginBottom = "15px";
            collapse_panel.style.paddingBottom = "20px";
        } else {
            collapse_body.style.display = "none";
            collapse_footer.style.display = "none";
            header_less.style.display = "none";
            header_plus.style.display = "block";
            
            form_module.style.borderBottom = "0";
            form_module.style.marginBottom = "0";
            collapse_panel.style.paddingBottom = "0";
        }
        
    }
}

//Online payments
function completeOnlineCheckbox()
{
    var onlineCheck = document.getElementById("checkmeon").checked;
    var onlineInputs = document.querySelectorAll(".payment-online-checkbox");
    for (var i=0; i < onlineInputs.length; i++) {
        if (onlineCheck == true) {
            onlineInputs[i].checked = true;
        } else {
            onlineInputs[i].checked = false;
        }
    }
}

//Offline payments
function completeOfflineCheckbox()
{
    var offlineCheck = document.getElementById("checkmeoff").checked;
    var offlineInputs = document.querySelectorAll(".payment-offline-checkbox");
    for (var i=0; i < offlineInputs.length; i++) {
        if (offlineCheck == true) {
            offlineInputs[i].checked = true;
        } else {
            offlineInputs[i].checked = false;
        }
    }
}