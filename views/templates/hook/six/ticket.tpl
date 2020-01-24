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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2019 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form id="mp_ticket_checkout" action="{$redirect}" method="post" class="custom-checkout-six">
    <div class="row frame-checkout-six">
        <div class="title-checkout-six">
            <img class="img-fluid" src="{$mp_logo|escape:'html':'UTF-8'}" />
            <p>{l s='Quiero pagar con Checkout Ticket' mod='mercadopago'}</p>
        </div>

        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            {if $site_id == "MLB"}
            <div class="row pt-25">
                <div class="col-md-12 col-12 pb-20 px-0">
                    <div class="form-check mp-form-check">
                        <div class="col-md-4 col-4 col-xs-6">
                            <input class="form-check-input mp-checkbox" type="radio" value="CPF" id="mp_cpf" name="mercadopago_ticket[docType]" checked>
                            <label class="form-check-label pointer" for="mp_cpf">{l s='Persona Física' mod='mercadopago'}</label>
                        </div>
                    </div>
                    <div class="form-check mp-form-check">
                        <div class="col-md-4 col-4 col-xs-6 m-pr-0">
                            <input class="form-check-input mp-checkbox" type="radio" value="CNPJ" id="mp_cnpj" name="mercadopago_ticket[docType]">
                            <label class="form-check-label pointer" for="mp_cnpj">{l s='Persona Jurídica' mod='mercadopago'}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-4 col-xs-6 pt-20" id="mp_box_firstname">
                    <label for="" id="mp_firstname_label" class="pb-5">{l s='Nombre' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="" id="mp_socialname_label" class="pb-5">{l s='Razón social' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_firstname" data-checkout="mp_firstname" name="mercadopago_ticket[firstname]" class="form-control mp-form-control" value="{$customer['firstname']}" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_firstname" id="error_firstname">{l s='You must inform your name' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-6 pt-20 m-pr-0" id="mp_box_lastname">
                    <label for="" class="pb-5">{l s='Apellido' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_lastname" data-checkout="mp_lastname" name="mercadopago_ticket[lastname]" class="form-control mp-form-control" value="{$customer['lastname']}" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_lastname" id="error_lastname">{l s='You must inform last name' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-12 pt-20 mp-m-col">
                    <label for="docNumberError" id="mp_cpf_label" class="pb-5">{l s='CPF' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="docNumberError" id="mp_cnpj_label" class="pb-5">{l s='CNPJ' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_doc_number" data-checkout="mp_doc_number" name="mercadopago_ticket[docNumber]" class="form-control mp-form-control" onkeyup="maskInput(this, mcpf);" maxlength="14" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_doc_number" id="mp_error_docnumber">{l s='The document must be valid' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 col-8 col-xs-8 pt-10">
                    <label for="" class="pb-5">{l s='Dirección' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_address" data-checkout="mp_address" name="mercadopago_ticket[address]" class="form-control mp-form-control" value="{$address->address1}" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_address" id="mp_error_address">{l s='You must inform address' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-4 pt-10">
                    <label for="" class="pb-5">{l s='Número' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_number" data-checkout="mp_number" name="mercadopago_ticket[number]" class="form-control mp-form-control" onkeyup="maskInput(this, minteger);" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_number" id="mp_error_number">{l s='You must inform address number' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-4 col-xs-6 pt-10">
                    <label for="" class="pb-5">{l s='Ciudad' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_city" data-checkout="mp_city" name="mercadopago_ticket[city]" class="form-control mp-form-control" value="{$address->city}" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_city" id="mp_error_city">{l s='You must inform address number' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-6 pt-10 m-pr-0">
                    <label for="" class="pb-5">{l s='Estado' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <select id="mp_state" data-checkout="mp_state" name="mercadopago_ticket[state]" class="form-control mp-form-control mp-select pointer">
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
                    <small class="mp-erro-febraban" data-main="#mp_state" id="mp_error_state">{l s='You must inform state' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-12 pt-10 mp-m-col">
                    <label for="" class="pb-5">{l s='Código postal' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_zipcode" data-checkout="mp_zipcode" name="mercadopago_ticket[zipcode]" class="form-control mp-form-control" value="{$address->postcode}" autocomplete="off" />
                    <small class="mp-erro-febraban" data-main="#mp_zipcode" id="mp_error_zipcode">{l s='You must inform zip code' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 pt-20 mp-m-col">
                    <p class="all-required">{l s='Completa todos los campos, son obligatorios.' mod='mercadopago'}</p>
                </div>
            </div>
            {/if}

            <p class="subtitle-checkout-six">{l s='Por favor, selecciona el emisor de su elección' mod='mercadopago'}</p>

            <div class="row pt-10">
                {if count($ticket) != 0}
                    {foreach $ticket as $key => $value}
                    <div class="col-md-6 col-6 col-xs-6 pt-10 mp-m-col">
                        <div class="form-check mp-form-check">
                            <input name="mercadopago_ticket[paymentMethodId]" id="{$value['id']}" class="form-check-input mp-checkbox" value="{Tools::strtolower($value['id'])}" type="radio" 
                            {if $key==0} checked {/if}> 
                            <label class="form-check-label" for="{$value['id']}">
                                <img src="{$value['image']}" alt="{$value['name']}" />
                                <span class="text-ticket-tarjeta">{$value['name']}</span>
                            </label>
                        </div>
                    </div>
                    {/foreach}
                {/if}
            </div>

            <div class="row">
                <div class="col-md-12 col-xs-12 col-12 pt-25 mp-m-col">
                    <button class="btn btn-primary">{l s='Finalizar pedido' mod='mercadopago'}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
    window.onload = function () {
        var site_id = '{$site_id}';
        mpValidateSiteId(site_id);
        validateDocumentInputs();
        mercadoPagoFormHandlerTicket();
    }

    var mercado_pago_docnumber = "CPF";

    var seller = {
        site_id: ""
    };

    /**
     * Validate site_id
     */
    function mpValidateSiteId(site_id) {
        seller.site_id = site_id;
    }

    /**
     * Validate input depending on document type
     */
    function validateDocumentInputs() {
        if (seller.site_id == "MLB") {
            var mp_box_lastname = document.getElementById("mp_box_lastname");
            var mp_box_firstname = document.getElementById("mp_box_firstname");
            var mp_firstname_label = document.getElementById("mp_firstname_label");
            var mp_socialname_label = document.getElementById("mp_socialname_label");
            var mp_cpf_label = document.getElementById("mp_cpf_label");
            var mp_cnpj_label = document.getElementById("mp_cnpj_label");
            var mp_doc_number = document.getElementById("mp_doc_number");
            var mp_doc_type = document.querySelectorAll('input[type=radio][name="mercadopago_ticket[docType]"]');

            mp_cnpj_label.style.display = 'none';
            mp_socialname_label.style.display = 'none';

            for (var i = 0; i < mp_doc_type.length; i++) {
                mp_doc_type[i].addEventListener('change', function() {
                    if (this.value == "CPF") {
                        mp_cpf_label.style.display = "inline-block";
                        mp_box_lastname.style.display = "block";
                        mp_firstname_label.style.display = "inline-block";
                        mp_cnpj_label.style.display = "none";
                        mp_socialname_label.style.display = "none";
                        mp_box_firstname.classList.add("col-md-4");
                        mp_box_firstname.classList.remove("col-md-8");
                        mp_doc_number.setAttribute("maxlength", "14");
                        mp_doc_number.setAttribute("onkeyup", "maskinput(this, mcpf)");
                        mercado_pago_docnumber = "CPF";
                    } else {
                        mp_cpf_label.style.display = "none";
                        mp_box_lastname.style.display = "none";
                        mp_firstname_label.style.display = "none";
                        mp_cnpj_label.style.display = "inline-block";
                        mp_socialname_label.style.display = "inline-block";
                        mp_box_firstname.classList.add("col-md-8");
                        mp_box_firstname.classList.remove("col-md-4");
                        mp_doc_number.setAttribute("maxlength", "18");
                        mp_doc_number.setAttribute("onkeyup", "maskinput(this, mcnpj)");
                        mercado_pago_docnumber = "CNPJ";
                    }
                });
            }
        }
    }

    /**
     * Handler form submit
     * @return bool
     */
    function mercadoPagoFormHandlerTicket() {
        if (document.forms['mp_ticket_checkout'] != undefined) {
            document.forms['mp_ticket_checkout'].onsubmit = function () {
                if (seller.site_id == "MLB") {
                    if (validateInputs() && validateDocumentNumber()) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }

                return true;
            }
        }
    }

    /**
     * Get form
     */
    function getForm() {
        return document.querySelector('#mp_ticket_checkout');
    }

    /**
     * Validate if all inputs are valid
     */
    function validateInputs() {
        var form = getForm();
        var form_inputs = form.querySelectorAll("[data-checkout]");
        var small = form.querySelectorAll(".mp-erro-febraban");

        //Show or hide error message and border
        for (var i = 0; i < form_inputs.length; i++) {
            var element = form_inputs[i];
            var input = form.querySelector(small[i].getAttribute("data-main"));

            if (element.parentNode.style.display != "none" && (element.value == -1 || element.value == "")) {
                small[i].style.display = "inline-block";
                input.classList.add("mp-form-control-error");
            } else {
                small[i].style.display = "none";
                input.classList.remove("mp-form-control-error");
            }
        }

        //Focus on the element with error
        for (var i = 0; i < form_inputs.length; i++) {
            var element = form_inputs[i];
            if (element.parentNode.style.display != "none" && (element.value == -1 || element.value == "")) {
                element.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * Validate document number
     * @return bool
     */
    function validateDocumentNumber() {
        var docnumber_input = document.getElementById("mp_doc_number");
        var docnumber_error = document.getElementById("mp_error_docnumber");
        var docnumber_validate = false;

        if (mercado_pago_docnumber == "CPF") {
            docnumber_validate = validateCPF(docnumber_input.value);
        } else {
            docnumber_validate = validateCNPJ(docnumber_input.value);
        }

        if (!docnumber_validate) {
            docnumber_error.style.display = "block";
            docnumber_input.classList.add("mp-form-control-error");
            docnumber_input.focus();
        } else {
            docnumber_error.style.display = "none";
            docnumber_input.classList.remove("mp-form-control-error");
            docnumber_validate = true;
        }

        return docnumber_validate;
    }

    /**
     * Validate CPF
     * @param string strCPF
     * @return bool
     */
    function validateCPF(strCPF) {
        var Soma;
        var Resto;

        Soma = 0;
        strCPF = strCPF.replace(/[.-\s]/g, "");

        if (strCPF == "00000000000") {
            return false;
        }

        for (var i = 1; i <= 9; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
        }

        Resto = (Soma * 10) % 11;
        if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
        if (Resto != parseInt(strCPF.substring(9, 10))) {
            return false;
        }

        Soma = 0;
        for (var i = 1; i <= 10; i++) { Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i); }

        Resto = (Soma * 10) % 11;
        if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
        if (Resto != parseInt(strCPF.substring(10, 11))) {
            return false;
        }

        return true;
    }

    /**
     * Validate CNPJ
     * @param string strCNPJ
     * @return bool
     */
    function validateCNPJ(strCNPJ) {
        var numeros, digitos, soma, i, resultado, pos, tamanho, digitos_iguais;

        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace("-", "");
        strCNPJ = strCNPJ.replace("/", "");
        digitos_iguais = 1;

        if (strCNPJ.length < 14 && strCNPJ.length < 15) {
            return false;
        }
        for (i = 0; i < strCNPJ.length - 1; i++) {
            if (strCNPJ.charAt(i) != strCNPJ.charAt(i + 1)) {
                digitos_iguais = 0;
                break;
            }
        }
        if (!digitos_iguais) {
            tamanho = strCNPJ.length - 2
            numeros = strCNPJ.substring(0, tamanho);
            digitos = strCNPJ.substring(tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }

            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(0)) {
                return false;
            }

            tamanho = tamanho + 1;
            numeros = strCNPJ.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) {
                    pos = 9;
                }
            }

            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(1)) {
                return false;
            }

            return true;
        }
        else {
            return false;
        }
    }
</script>