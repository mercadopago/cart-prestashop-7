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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form id="mp_pse_checkout" class="mp-checkout-form mp-pse-checkout-container" method="post" action="{$redirect|escape:'htmlall':'UTF-8'}">
    <h3 class="mp-pse-checkout-subtitle mp-frame-title">{l s='Select where you want to pay' mod='mercadopago'}</h3>

    <div class="form-group">
        <div class="form-group">
            <label for="mp_pse_person_type">{l s='Person Type' mod='mercadopago'}<b class="mp-pse-checkout-required-field">*</b></label>
            <select id="mp_pse_person_type" name="mercadopago_pse[personType]" class="form-control mp-form-control mp-select mp-pointer">
                <option value="">{l s='Select a type of person' mod='mercadopago'}</option>
                {foreach $payment_method_info["person_types"] as $key => $value}
                    <option value="{$value["id"]}">{$value["name"]}</option>
                {/foreach}
            </select>
            <small class="mp-erro-febraban" id="mp_pse_person_type_error">
                {l s='Select a person type' mod='mercadopago'}
            </small>
        </div>

        <div class="form-group">
            <label for="mp_pse_document_type">{l s='Holder document' mod='mercadopago'}<b class="mp-pse-checkout-required-field">*</b></label>
            <select id="mp_pse_document_type" name="mercadopago_pse[documentType]" class="form-control mp-form-control mp-select mp-pointer">
                <option value="">{l s='Select a document type' mod='mercadopago'}</option>
                {foreach $payment_method_info["allowed_identification_types"] as $key => $value}
                    <option
                        data-minlength="{$value["min_length"]}"
                        data-maxlength="{$value["max_length"]}"
                        data-type="{$value["type"]}"
                        value="{$value["id"]}"
                    >{$value["name"]}</option>
                {/foreach}
            </select>
            <small class="mp-erro-febraban" id="mp_pse_document_type_error">
                {l s='Select a document type' mod='mercadopago'}
            </small>
        </div>

        <div class="form-group">
            <label for="mp_pse_document_number">{l s='Holder document number' mod='mercadopago'}<b class="mp-pse-checkout-required-field">*</b></label>
            <input type="text" id="mp_pse_document_number" name="mercadopago_pse[documentNumber]" class="form-control mp-form-control mp-input" placeholder="{l s='Holder document number' mod='mercadopago'}">
            <small class="mp-erro-febraban" id="mp_pse_document_number_error">
                {l s='Enter a valid document number' mod='mercadopago'}
            </small>
        </div>

        <div class="form-group">
            <label for="mp_pse_bank">{l s='Financial Institution' mod='mercadopago'}<b class="mp-pse-checkout-required-field">*</b></label>
            <select id="mp_pse_bank" name="mercadopago_pse[financialInstitution]" class="form-control mp-form-control mp-select mp-pointer">
                <option value="">{l s='Select a financial institution' mod='mercadopago'}</option>
                {foreach $payment_method_info["financial_institutions"] as $key => $value}
                    <option value="{$value["id"]}">{$value["description"]}</option>
                {/foreach}
            </select>
            <small class="mp-erro-febraban" id="mp_pse_bank_error">
                {l s='Select a financial institution' mod='mercadopago'}
            </small>
        </div>

        <div>
            <div>
                <label>
                    {l s='By continuing, you agree to our ' mod='mercadopago'}
                    <u>
                        <a class="mp-link-checkout-custom" href={$terms_url|escape:"html":"UTF-8"} target="_blank">
                            {l s='Terms and Conditions' mod='mercadopago'}
                        </a>
                    </u>
                </label>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/front{$assets_ext_min|escape:'htmlall':'UTF-8'}.js?v={$version|escape:'htmlall':'UTF-8'}"/>
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/pse{$assets_ext_min|escape:'htmlall':'UTF-8'}.js?v={$version|escape:'htmlall':'UTF-8'}"/>
