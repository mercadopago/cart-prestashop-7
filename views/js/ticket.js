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
    },
    terms: "conditions_to_approve[terms-and-conditions]"
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

function validateBrazilDocuments(){
    if(MPv1Ticket.params.site_id == "MLB"){
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
    var terms = document.getElementById(MPv1Ticket.terms);
    var doc_error = document.getElementById('mp_docNumber_error');
    strCPF = strCPF.value;

    Soma = 0;
    strCPF = strCPF.replace(/[.-\s]/g, "");
    if (strCPF == "00000000000") {
        doc_error.style.display = 'block';
        terms.checked = false;
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
        terms.checked = false;
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
        terms.checked = false;
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
    var terms = document.getElementById(MPv1Ticket.terms);
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
        terms.checked = false;
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
            terms.checked = false;
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
            terms.checked = false;
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
        terms.checked = false;
        element.focus();
        element.classList.add("mp-erro-input");
        return false;
    }
}

//inputs validation
function inputsValidate(array_inputs) {
    var count = 0;
    var terms = document.getElementById(MPv1Ticket.terms);

    array_inputs.forEach(element => {
        var input = document.getElementById(element);
        if (input.value == "") {
            input.focus();
            input.classList.add("mp-erro-input");
            terms.checked = false;
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