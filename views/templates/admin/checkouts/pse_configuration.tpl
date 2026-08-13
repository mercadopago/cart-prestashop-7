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

function insert_custom_header() {
    const checkout_custom_header = document.createElement("div");
    const checkout_config_existent_header = document.querySelector("#module_form_8 .panel .panel-heading");

    checkout_config_existent_header.style.height = "auto";
    checkout_custom_header.innerHTML = "<ul class='mp-checkout-list'>\
        <li><span>{l s='Payment experience within your store.' mod='mercadopago'}</span></li>\
        <li><span>{l s='Your customers pay as guests without leaving your store.' mod='mercadopago'}</span></li>\
    </ul>";


    checkout_config_existent_header.insertBefore(checkout_custom_header, checkout_config_existent_header.firstChild);
}

function insert_subtitle() {
    const checkout_config_subtitle_container = document.createElement("div");
    const checkout_config_existent_form = document.querySelector("#module_form_8 .panel .form-wrapper");

    checkout_config_subtitle_container.innerHTML = "<div class='row mp-pb-25'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>{l s='Your customer will make their purchase quickly, easily and safely with these settings:' mod='mercadopago'}</h4>\
        </div>\
    </div>";

    checkout_config_existent_form.insertBefore(checkout_config_subtitle_container, checkout_config_existent_form.firstChild);
}

function insert_advanced_config() {
    const checkout_config_fields = document.querySelectorAll("#module_form_8 .panel .form-wrapper .form-group");
    const checkout_config_advanced_config = "<div class='panel-heading mp-panel-advanced-config'>\
        <i class='icon-cogs'></i> {l s='Advanced Configuration' mod='mercadopago'}\
        <span class='mp-btn-collapsible' id='header_plus_pse' style='display:block'>+</span>\
        <span class='mp-btn-collapsible' id='header_less_pse' style='display:none'>-</span>\
    </div>\
    <div class='row text-pse-advanced' style='padding-top: 20px; padding-bottom: 25px;'>\
        <div class='col-md-12'>\
            <h4 class='mp-title-checkout-body'>{l s='Activate other tools in our module ready to use.' mod='mercadopago'}</h4>\
        </div>\
    </div>";

    checkout_config_fields.forEach((field) => field.innerHTML.includes('MERCADOPAGO_PSE_DISCOUNT')
        ? field.classList.add("mp-pse-collapsible-field")
        : field.insertAdjacentHTML('afterend', checkout_config_advanced_config)
    )

    const advanced_config_collapsible = document.querySelector("#module_form_8 .panel .panel-heading.mp-panel-advanced-config");
    const advanced_config_collapsible_content = document.querySelector("#module_form_8 .panel .text-pse-advanced");
    const advanced_config_collapsible_field = document.querySelector(".mp-pse-collapsible-field");
    const hide_content = () => {
        advanced_config_collapsible_content.style.display = "none";
        advanced_config_collapsible_field.style.display = "none";
        advanced_config_collapsible.querySelector("#header_plus_pse").style.display = "block";
        advanced_config_collapsible.querySelector("#header_less_pse").style.display = "none";
    };
    const display_content = () => {
        advanced_config_collapsible_content.style.display = "block";
        advanced_config_collapsible_field.style.display = "block";
        advanced_config_collapsible.querySelector("#header_plus_pse").style.display = "none";
        advanced_config_collapsible.querySelector("#header_less_pse").style.display = "block";
    }
    const accordion_is_closed = () => advanced_config_collapsible_content.style.display === "none";

    advanced_config_collapsible.addEventListener("click", () => accordion_is_closed() ? display_content(): hide_content());

    hide_content();
}

document.querySelector("#module_form_8 .panel").style.borderTopLeftRadius = 0;

insert_custom_header();
insert_subtitle();
insert_advanced_config();