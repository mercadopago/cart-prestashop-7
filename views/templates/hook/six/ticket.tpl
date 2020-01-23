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
                            <input class="form-check-input mp-checkbox" type="radio" value="CPF" id="mp_cpf" name="mercadopago_ticket[docType]" checked onchange="selectDocumentType()">
                            <label class="form-check-label pointer" for="mp_cpf">{l s='Persona Física' mod='mercadopago'}</label>
                        </div>
                    </div>
                    <div class="form-check mp-form-check">
                        <div class="col-md-4 col-4 col-xs-6 m-pr-0">
                            <input class="form-check-input mp-checkbox" type="radio" value="CNPJ" id="mp_cnpj" name="mercadopago_ticket[docType]" onchange="selectDocumentType()">
                            <label class="form-check-label pointer" for="mp_cnpj">{l s='Persona Jurídica' mod='mercadopago'}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="mp-firstname" class="col-md-4 col-4 col-xs-6 pt-20">
                    <label for="" id="mp-name-label" class="pb-5">{l s='Nombre' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="" id="mp-social-label" class="pb-5">{l s='Razón social' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_firstname" name="mercadopago_ticket[firstname]" class="form-control mp-form-control" value="{$customer['firstname']}" autocomplete="off" />
                </div>

                <div id="mp-lastname" class="col-md-4 col-4 col-xs-6 pt-20 m-pr-0">
                    <label for="" class="pb-5">{l s='Apellido' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_lastname" name="mercadopago_ticket[lastname]" class="form-control mp-form-control" value="{$customer['lastname']}" autocomplete="off" />
                </div>

                <div class="col-md-4 col-4 col-xs-12 pt-20 mp-m-col">
                    <label for="docNumberError" id="mp-cpf-label" class="pb-5">{l s='CPF' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="docNumberError" id="mp-cnpj-label" class="pb-5">{l s='CNPJ' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_docNumber" name="mercadopago_ticket[docNumber]" class="form-control mp-form-control" onkeyup="maskInput(this, mcpf);" maxlength="14" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_docNumber_error">{l s='The document must be valid' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 col-8 col-xs-8 pt-10">
                    <label for="" class="pb-5">{l s='Dirección' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_address" name="mercadopago_ticket[address]" class="form-control mp-form-control" value="{$address->address1}" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_address_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-4 pt-10">
                    <label for="" class="pb-5">{l s='Número' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_number" name="mercadopago_ticket[number]" class="form-control mp-form-control" onkeyup="maskInput(this, minteger);" autocomplete="off" />
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-4 col-xs-6 pt-10">
                    <label for="" class="pb-5">{l s='Ciudad' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_city" name="mercadopago_ticket[city]" class="form-control mp-form-control" value="{$address->city}" autocomplete="off" />
                </div>

                <div class="col-md-4 col-4 col-xs-6 pt-10 m-pr-0">
                    <label for="" class="pb-5">{l s='Estado' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <select id="mp_state" name="mercadopago_ticket[state]" class="form-control mp-form-control mp-select pointer">
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
                </div>

                <div class="col-md-4 col-4 col-xs-12 pt-10 mp-m-col">
                    <label for="" class="pb-5">{l s='Código postal' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_zipcode" name="mercadopago_ticket[zipcode]" class="form-control mp-form-control" value="{$address->postcode}" autocomplete="off" />
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
        mpValidateParams(site_id);
        validateBrazilDocuments();
        mpTicketSubmitForm();
    }

    var MPv1Ticket = {
        params: {
            site_id: "",
        },
        inputs: [
            "mp_zipcode",
            "mp_state",
            "mp_city",
            "mp_number",
            "mp_address",
            "mp_docNumber",
            "mp_lastname",
            "mp_firstname",
        ],
        docs: {
            cpf_label: "mp-cpf-label",
            cpf_number: "mp_cpf",
            doc_number: "mp_docNumber",
            name_label: "mp-name-label",
            cnpj_label: "mp-cnpj-label",
            mp_lastname: "mp-lastname",
            mp_firstname: "mp-firstname",
            social_label: "mp-social-label",
        }
    }

    //validate params
    function mpValidateParams(site_id) {
        MPv1Ticket.params.site_id = site_id;
    }

    //select cpf or cnpj
    var cpf_label = document.getElementById(MPv1Ticket.docs.cpf_label);
    var name_label = document.getElementById(MPv1Ticket.docs.name_label);
    var cnpj_label = document.getElementById(MPv1Ticket.docs.cnpj_label);
    var cpf_number = document.getElementById(MPv1Ticket.docs.cpf_number);
    var doc_number = document.getElementById(MPv1Ticket.docs.doc_number);
    var mp_lastname = document.getElementById(MPv1Ticket.docs.mp_lastname);
    var mp_firstname = document.getElementById(MPv1Ticket.docs.mp_firstname);
    var social_label = document.getElementById(MPv1Ticket.docs.social_label);

    function validateBrazilDocuments() {
        if (MPv1Ticket.params.site_id == "MLB") {
            cnpj_label.style.display = 'none';
            social_label.style.display = 'none';
        }
    }

    function selectDocumentType() {
        if (cpf_number.checked == true) {
            cpf_label.style.display = 'table-cell';
            cnpj_label.style.display = 'none';
            mp_lastname.style.display = 'inline-block';
            mp_firstname.classList.add("col-md-4");
            mp_firstname.classList.remove("col-md-8");
            name_label.style.display = 'table-cell';
            social_label.style.display = 'none';
            doc_number.setAttribute("maxlength", "14");
            doc_number.setAttribute("onkeyup", "maskInput(this, mcpf)");
        }
        else {
            cpf_label.style.display = 'none';
            cnpj_label.style.display = 'table-cell';
            mp_lastname.style.display = 'none';
            mp_firstname.classList.add("col-md-8");
            mp_firstname.classList.remove("col-md-4");
            name_label.style.display = 'none';
            social_label.style.display = 'table-cell';
            doc_number.setAttribute("maxlength", "18");
            doc_number.setAttribute("onkeyup", "maskInput(this, mcnpj)");
        }
    }

    //cpf validate
    function cpfValidate(strCPF) {
        var Soma;
        var Resto;
        var element = strCPF;
        var doc_error = document.getElementById('mp_docNumber_error');
        strCPF = strCPF.value;

        Soma = 0;
        strCPF = strCPF.replace(/[.-\s]/g, "");
        if (strCPF == "00000000000") {
            doc_error.style.display = 'block';
            element.focus();
            element.classList.add("mp-erro-input");
            return false;
        }

        for (i = 1; i <= 9; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
        }

        Resto = (Soma * 10) % 11;
        if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
        if (Resto != parseInt(strCPF.substring(9, 10))) {
            doc_error.style.display = 'block';
            element.focus();
            element.classList.add("mp-erro-input");
            return false;
        }

        Soma = 0;
        for (i = 1; i <= 10; i++) { Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i); }

        Resto = (Soma * 10) % 11;
        if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
        if (Resto != parseInt(strCPF.substring(10, 11))) {
            doc_error.style.display = 'block';
            element.focus();
            element.classList.add("mp-erro-input");
            return false;
        }

        doc_error.style.display = 'none';
        element.classList.remove("mp-erro-input");
        return true;
    }

    //cnpj validate
    function cnpjValidate(strCNPJ) {
        var element = strCNPJ;
        var doc_error = document.getElementById('mp_docNumber_error');
        var numeros, digitos, soma, i, resultado, pos, tamanho, digitos_iguais;

        strCNPJ = strCNPJ.value;
        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace(".", "");
        strCNPJ = strCNPJ.replace("-", "");
        strCNPJ = strCNPJ.replace("/", "");
        digitos_iguais = 1;
        if (strCNPJ.length < 14 && strCNPJ.length < 15) {
            doc_error.style.display = 'block';
            element.focus();
            element.classList.add("mp-erro-input");
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
                doc_error.style.display = 'block';
                element.focus();
                element.classList.add("mp-erro-input");
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
                doc_error.style.display = 'block';
                element.focus();
                element.classList.add("mp-erro-input");
                return false;
            }

            doc_error.style.display = 'none';
            element.classList.remove("mp-erro-input");
            return true;
        }
        else {
            doc_error.style.display = 'block';
            element.focus();
            element.classList.add("mp-erro-input");
            return false;
        }
    }

    //inputs validation
    function inputsValidate(array_inputs) {
        var count = 0;

        array_inputs.forEach(element => {
            var input = document.getElementById(element);
            if (input.value == "") {
                input.focus();
                input.classList.add("mp-erro-input");
            } else {
                count++;
                input.classList.remove("mp-erro-input");
            }
        });

        return count;
    }

    //ticket form submit
    function mpTicketSubmitForm() {
        document.forms['mp_ticket_checkout'].onsubmit = function () {
            if (MPv1Ticket.params.site_id == 'MLB') {
                var submit = false;
                var doc_validate = false;
                var input_validate = false;
                var array_inputs = MPv1Ticket.inputs;

                //inputs validation
                count_inputs = inputsValidate(array_inputs);
                if (array_inputs.length == count_inputs) {
                    input_validate = true;
                } else {
                    input_validate = false;
                }

                // docNumber validation
                if (cpf_number.checked == true) {
                    doc_validate = cpfValidate(document.getElementById(MPv1Ticket.inputs[5]));
                } else {
                    doc_validate = cnpjValidate(document.getElementById(MPv1Ticket.inputs[5]));
                }

                //verify submit
                if (doc_validate == true && input_validate == true) {
                    submit = true;
                }

                return submit;
            }
        }
    }
</script>