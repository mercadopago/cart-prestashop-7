var MPv1Ticket = {
    params: {
        site_id: "",
        coupon_url: "",
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
        name_label: "mp-name-label",
        cnpj_label: "mp-cnpj-label",
        mp_lastname: "mp-lastname",
        mp_firstname: "mp-firstname",
        social_label: "mp-social-label",
    },
    coupon: {
        couponCode: "#couponCodeTicket",
        couponError: "#mpCouponErrorTicket",
        couponSending: "#mpSendingCouponTicket",
        couponSuccess: "#mpCouponApplyedTicket",
        responseError: "#mpResponseErrorTicket",
        inputCampaignId: "#campaignIdTicket",
        inputCouponAmount: "#couponAmountTicket",
        inputCouponPercent: "#couponPercentTicket",
        buttonApplyCoupon: "#applyCouponTicket",
    },
    terms: "conditions_to_approve[terms-and-conditions]"
}

//validate params
function mpValidateParams(site_id, coupon_url) {
    MPv1Ticket.params.site_id = site_id;
    MPv1Ticket.params.coupon_url = coupon_url.replace(/&amp;/g, "&");
}

//select cpf or cnpj
var cpf_number = document.getElementById(MPv1Ticket.docs.cpf_number);
var cpf_label = document.getElementById(MPv1Ticket.docs.cpf_label);
var cnpj_label = document.getElementById(MPv1Ticket.docs.cnpj_label);
var mp_firstname = document.getElementById(MPv1Ticket.docs.mp_firstname);
var mp_lastname = document.getElementById(MPv1Ticket.docs.mp_lastname);
var name_label = document.getElementById(MPv1Ticket.docs.name_label);
var social_label = document.getElementById(MPv1Ticket.docs.social_label);

cnpj_label.style.display = 'none';
social_label.style.display = 'none';

function selectDocumentType() {
    if (cpf_number.checked == true) {
        cpf_label.style.display = 'table-cell';
        cnpj_label.style.display = 'none';
        mp_lastname.style.display = 'inline-block';
        mp_firstname.classList.add("col-md-4");
        mp_firstname.classList.remove("col-md-8");
        name_label.style.display = 'table-cell';
        social_label.style.display = 'none';
    }
    else {
        cpf_label.style.display = 'none';
        cnpj_label.style.display = 'table-cell';
        mp_lastname.style.display = 'none';
        mp_firstname.classList.add("col-md-8");
        mp_firstname.classList.remove("col-md-4");
        name_label.style.display = 'none';
        social_label.style.display = 'table-cell';
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
        return false;
    }

    doc_error.style.display = 'none';
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
            return false;
        }

        doc_error.style.display = 'none';
        return true;
    }
    else {
        doc_error.style.display = 'block';
        terms.checked = false;
        element.focus();
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
            terms.checked = false;
        } else {
            count++;
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

//apply coupon
function mpTicketApplyAjax() {
    var couponCode = document.querySelector(MPv1Ticket.coupon.couponCode);
    var couponError = document.querySelector(MPv1Ticket.coupon.couponError);
    var couponSuccess = document.querySelector(MPv1Ticket.coupon.couponSuccess);
    var couponSending = document.querySelector(MPv1Ticket.coupon.couponSending);
    var responseError = document.querySelector(MPv1Ticket.coupon.responseError);
    var inputCampaignId = document.querySelector(MPv1Ticket.coupon.inputCampaignId);
    var inputCouponAmount = document.querySelector(MPv1Ticket.coupon.inputCouponAmount);
    var inputCouponPercent = document.querySelector(MPv1Ticket.coupon.inputCouponPercent);
    var buttonApplyCoupon = document.querySelector(MPv1Ticket.coupon.buttonApplyCoupon);

    $.ajax({
        url: MPv1Ticket.params.coupon_url,
        type: 'POST',
        data: {
            coupon: couponCode.value,
        },
        beforeSend: function () {
            couponError.style.display = "none";
            couponSuccess.style.display = "none";
            couponSending.style.display = "block";
        },
        success: function (success) {
            console.log(success);
            couponSending.style.display = "none";
            responseError.style.display = "none";

            if(success.code > 202){
                couponError.style.display = "block";
                couponSuccess.style.display = "none";
            }
            else{
                couponError.style.display = "none";
                couponSuccess.style.display = "block";
                couponCode.readOnly = true;
                buttonApplyCoupon.disabled = true;
                couponCode.style.cssText = 'background-color:#f8f8f8 !important';
                inputCampaignId.value = success.message.id;
                inputCouponAmount.value = success.message.coupon_amount;
                inputCouponPercent.value = success.message.percent_off;
            }
        },
        error: function (error) {
            console.log(error);
            couponError.style.display = "none";
            ouponSending.style.display = "none";
            couponSuccess.style.display = "none";
            responseError.style.display = "block";
        }
    });
}