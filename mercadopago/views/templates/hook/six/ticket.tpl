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

<form id="mp_ticket_checkout" action="{$redirect|escape:'htmlall':'UTF-8'}" method="post" class="mp-custom-checkout-six">
    <div class="row mp-frame-checkout-six">
        <div class="mp-title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}" />
            <p class="mp-m-pt-10">
                {l s='Pay with payment methods in cash' mod='mercadopago'}
                {if $discount != ""} ({$discount|escape:'htmlall':'UTF-8'}% OFF) {/if}
            </p>
        </div>

        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            {if $site_id == "MLU"}
                <div class="col-md-12 col-12 mp-frame-title">
                    <p class="submp-title-checkout-six">{l s='Enter your document number' mod='mercadopago'}</p>
                </div>

                <div class="row">
                    <div id="mp-doc-tye" class="col-md-4 col-4 mp-pb-20 mp-m-col">
                        <label for="mp-docType" class="mp-pb-5">{l s='Type' mod='mercadopago'}</label>
                        <select
                            id="mp-docType"
                            class="form-control mp-form-control mp-select mp-pointer"
                            name="mercadopago_ticket[docType]"
                        >
                            <option value="CI" selected>{l s='CI' mod='mercadopago'}</option>
                        </select>
                    </div>

                    <!-- Input Doc Number -->
                    <div class="col-md-8 col-8 mp-pb-20 mp-m-col">
                        <label for="mp_doc_number" class="mp-pb-5">{l s='Document number' mod='mercadopago'}</label>
                        <input
                            id="mp_doc_number"
                            class="form-control mp-form-control"
                            name="mercadopago_ticket[docNumber]"
                            type="text"
                            maxlength="8"
                            autocomplete="off"
                            data-checkout="mp_doc_number"
                            onkeyup="maskInput(this, minteger);"
                        />
                        <small class="mp-erro-efetivo-mlu" data-main="#mp_doc_number" id="mp_error_docnumber">
                            {l s='The document must be valid' mod='mercadopago'}
                        </small>
                    </div>
                </div>
            {/if}

            {if $site_id == "MLB"}
                <div class="row mp-pt-25">
                    <div class="col-md-12 col-12 mp-pb-20 mp-px-0">
                        <div class="form-check mp-form-check">
                            <div class="col-md-4 col-4 col-xs-6 mp-m-px-0">
                                <input
                                    id="mp_cpf"
                                    name="mercadopago_ticket[docType]"
                                    class="form-check-input mp-checkbox"
                                    type="radio"
                                    value="CPF"
                                    checked
                                >
                                <label class="form-check-label mp-pointer" for="mp_cpf">
                                    {l s='Individual' mod='mercadopago'}
                                </label>
                            </div>
                        </div>
                        <div class="form-check mp-form-check">
                            <div class="col-md-4 col-4 col-xs-6 mp-m-px-0">
                                <input
                                    id="mp_cnpj"
                                    class="form-check-input mp-checkbox"
                                    name="mercadopago_ticket[docType]"
                                    type="radio"
                                    value="CNPJ"
                                >
                                <label class="form-check-label mp-pointer" for="mp_cnpj">
                                    {l s='Legal Entity' mod='mercadopago'}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-4 col-xs-12 mp-pt-20 mp-m-px-0" id="mp_box_firstname">
                        <label for="" id="mp_firstname_label" class="mp-pb-5">
                            {l s='Name' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <label for="" id="mp_socialname_label" class="mp-pb-5">
                            {l s='Company number' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_firstname"
                            class="form-control mp-form-control"
                            type="text"
                            name="mercadopago_ticket[firstname]"
                            value="{$context->customer->firstname|escape:'html':'UTF-8'}"
                            autocomplete="off"
                            data-checkout="mp_firstname"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_firstname" id="error_firstname">
                            {l s='You must inform your name' mod='mercadopago'}
                        </small>
                    </div>

                    <div class="col-md-4 col-4 col-xs-12 mp-pt-20 mp-m-px-0" id="mp_box_lastname">
                        <label for="" id="mp_lastname_label" class="mp-pb-5">
                            {l s='Last Name' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_lastname"
                            class="form-control mp-form-control"
                            name="mercadopago_ticket[lastname]"
                            type="text"
                            value="{$context->customer->lastname|escape:'html':'UTF-8'}"
                            autocomplete="off"
                            data-checkout="mp_lastname"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_lastname" id="error_lastname">
                            {l s='You must inform last name' mod='mercadopago'}
                        </small>
                    </div>

                    <div class="col-md-4 col-4 col-xs-12 mp-pt-20 mp-m-col">
                        <label for="docNumberError" id="mp_cpf_label" class="mp-pb-5">
                            {l s='CPF' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <label for="docNumberError" id="mp_cnpj_label" class="mp-pb-5">
                            {l s='CNPJ' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_doc_number"
                            name="mercadopago_ticket[docNumber]"
                            class="form-control mp-form-control"
                            type="text"
                            maxlength="14"
                            autocomplete="off"
                            data-checkout="mp_doc_number"
                            onkeyup="maskInput(this, mcpf);"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_doc_number" id="mp_error_docnumber">
                            {l s='The document must be valid' mod='mercadopago'}
                        </small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 col-8 col-xs-8 mp-pt-10 m-mp-pl-0">
                        <label for="" class="mp-pb-5">
                            {l s='Address' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_address"
                            name="mercadopago_ticket[address]"
                            class="form-control mp-form-control"
                            type="text"
                            value="{$address->address1|escape:'html':'UTF-8'}"
                            autocomplete="off"
                            data-checkout="mp_address"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_address" id="mp_error_address">
                            {l s='You must inform address' mod='mercadopago'}
                        </small>
                    </div>

                    <div class="col-md-4 col-4 col-xs-4 mp-pt-10 m-mp-pr-0">
                        <label for="" class="mp-pb-5">
                            {l s='Number' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_number"
                            name="mercadopago_ticket[number]"
                            class="form-control mp-form-control"
                            type="text"
                            autocomplete="off"
                            data-checkout="mp_number"
                            onkeyup="maskInput(this, minteger);"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_number" id="mp_error_number">
                            {l s='You must inform address number' mod='mercadopago'}
                        </small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-4 col-xs-12 mp-pt-10 mp-m-px-0">
                        <label for="" class="mp-pb-5">
                            {l s='City' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_city"
                            name="mercadopago_ticket[city]"
                            class="form-control mp-form-control"
                            type="text"
                            value="{$address->city|escape:'html':'UTF-8'}"
                            autocomplete="off"
                            data-checkout="mp_city"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_city" id="mp_error_city">
                            {l s='You must inform address number' mod='mercadopago'}
                        </small>
                    </div>

                    <div class="col-md-4 col-4 col-xs-12 mp-pt-10 mp-m-px-0">
                        <label for="" class="mp-pb-5">
                            {l s='State' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <select
                            id="mp_state"
                            name="mercadopago_ticket[state]"
                            class="form-control mp-form-control mp-select mp-pointer"
                            data-checkout="mp_state"
                        >
                            <option value="">{l s='Select state' mod='mercadopago'}</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RA">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                        <small class="mp-erro-febraban" data-main="#mp_state" id="mp_error_state">
                            {l s='You must inform state' mod='mercadopago'}
                        </small>
                    </div>

                    <div class="col-md-4 col-4 col-xs-12 mp-pt-10 mp-m-col">
                        <label for="" class="mp-pb-5">
                            {l s='Postal Code' mod='mercadopago'} <em class="mp-required">*</em>
                        </label>
                        <input
                            id="mp_zipcode"
                            name="mercadopago_ticket[zipcode]"
                            class="form-control mp-form-control"
                            type="text"
                            value="{$address->postcode|escape:'html':'UTF-8'}"
                            autocomplete="off"
                            data-checkout="mp_zipcode"
                        />
                        <small class="mp-erro-febraban" data-main="#mp_zipcode" id="mp_error_zipcode">
                            {l s='You must inform zip code' mod='mercadopago'}
                        </small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 col-xs-12 col-12 mp-pt-20 mp-m-col">
                        <p class="mp-all-required">
                            {l s='Complete all fields, they are mandatory!' mod='mercadopago'}
                        </p>
                    </div>
                </div>
            {/if}

            <p class="submp-title-checkout-six">
                {l s='Please, select the issuer of face payments with which you want to make the purchase:' mod='mercadopago'}
            </p>

            <div class="row mp-pt-10 mp-margin-0">
                {if count($ticket) != 0}
                    {foreach $ticket as $key => $value}
                        {if strtolower($value['id']) == 'paycash' && isset($value['payment_places'])}
                            {foreach $value['payment_places'] as $payment => $result }
                                <div class="col-md-6 col-6 col-xs-12 mp-px-0 mp-m-col mp-pt-15">
                                    <div class="form-check mp-form-check mp-form-item">
                                        <input
                                            id="{Tools::strtolower($value['id'])|escape:'html':'UTF-8'}|{$result['payment_option_id']|escape:'html':'UTF-8'}"
                                            name="mercadopago_ticket[paymentMethodId]"
                                            class="form-check-input mp-checkbox"
                                            value="{Tools::strtolower($value['id'])|escape:'html':'UTF-8'}|{$result['payment_option_id']|escape:'html':'UTF-8'}"
                                            type="radio"
                                            {if $key == 0} checked {/if}
                                        >
                                        <label class="mp-ticket-option-label" for="{Tools::strtolower($value['id'])|escape:'html':'UTF-8'}|{$result['payment_option_id']|escape:'html':'UTF-8'}}">
                                            <div class="mp-payment-method-logo-container">
                                                <img class="mp-payment-method-logo-image" src="{$result['thumbnail']|escape:'html':'UTF-8'}" alt="{$result['name']|escape:'html':'UTF-8'}"/>
                                            </div>
                                            <span class="mp-text-ticket-tarjeta">{$result['name']|escape:'html':'UTF-8'}</span>
                                        </label>
                                    </div>
                                </div>
                            {/foreach}
                        {else}
                            <div class="col-md-6 col-6 col-xs-12 mp-px-0 mp-m-col mp-pt-15">
                                <div class="form-check mp-form-check mp-form-item">
                                    <input
                                        id="{$value['id']|escape:'html':'UTF-8'}"
                                        name="mercadopago_ticket[paymentMethodId]"
                                        class="form-check-input mp-checkbox"
                                        value="{Tools::strtolower($value['id']|escape:'html':'UTF-8')}"
                                        type="radio"
                                        {if $key == 0} checked {/if}
                                    >
                                    <label class="mp-ticket-option-label" for="{$value['id']|escape:'html':'UTF-8'}">
                                        <div class="mp-payment-method-logo-container">
                                            <img class="mp-payment-method-logo-image" src="{$value['image']|escape:'html':'UTF-8'}" alt="{$value['name']|escape:'html':'UTF-8'}"/>
                                        </div>
                                        <span class="mp-text-ticket-tarjeta">{$value['name']|escape:'html':'UTF-8'}</span>
                                    </label>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                {/if}
            </div>

            <div class="row">
                <div class="col-xs-12 col-md-12 col-12 mp-pt-25">
                    <label> {l s='By continuing, you agree to our ' mod='mercadopago'}
                        <u>
                            <a class="mp-link-checkout-terms" href="{$terms_url|escape:'html':'UTF-8'}" target="_blank">
                                {l s='Terms and Conditions' mod='mercadopago'}
                            </a>
                        </u>
                    </label>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 mp-pt-25 mp-m-col">
                    <button id="mp-ticket-finish-order" class="btn btn-primary mp-btn-primary">{l s='Check out' mod='mercadopago'}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
    window.onload = loadTicket();
    function loadTicket() {
        var site_id = '{$site_id|escape:"javascript":"UTF-8"}';
        mpValidateSellerInfo(site_id, 'six');
        validateDocumentInputs();
        mercadoPagoFormHandlerTicket();
    }
</script>
