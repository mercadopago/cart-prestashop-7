var mercado_pago_docnumber = "CPF";
var terms = document.getElementById("conditions_to_approve[terms-and-conditions]");

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
                    mp_cpf_label.style.display = "table-cell";
                    mp_box_lastname.style.display = "block";
                    mp_firstname_label.style.display = "table-cell";
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
                    mp_cnpj_label.style.display = "table-cell";
                    mp_socialname_label.style.display = "table-cell";
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
 * @return {bool}
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
            terms.checked = false;
            return false;
        }
    }

    return true;
}

/**
 * Validate document number
 * @return {bool}
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
        terms.checked = false;
    } else {
        docnumber_error.style.display = "none";
        docnumber_input.classList.remove("mp-form-control-error");
        docnumber_validate = true;
    }

    return docnumber_validate;
}

/**
 * Validate CPF
 * @param {string} strCPF
 * @return {bool}
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
 * @param {string} strCNPJ
 * @return {bool}
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