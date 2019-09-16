
var MPv1Ticket = {
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
}

document.forms['mp_ticket_checkout'].onsubmit = function () { 
    MPv1Ticket.inputs.forEach(element => {
        if(document.getElementById(element).value == ""){
            document.getElementById(element+'_error').style.display = "block";
            document.getElementById(element).focus();
        }
    });

    return false;
}

//select cpf or cnpj
var cpf_number = document.getElementById('mp_cpf');
var cpf_label = document.getElementById('mp-cpf-label');
var cnpj_label = document.getElementById('mp-cnpj-label');
var mp_firstname = document.getElementById('mp-firstname');
var mp_lastname = document.getElementById('mp-lastname');
var name_label = document.getElementById('mp-name-label');
var social_label = document.getElementById('mp-social-label');

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
    else{
        cpf_label.style.display = 'none';
        cnpj_label.style.display = 'table-cell';
        mp_lastname.style.display = 'none';
        mp_firstname.classList.add("col-md-8");
        mp_firstname.classList.remove("col-md-4");
        name_label.style.display = 'none';
        social_label.style.display = 'table-cell';
    }
}

//input mask
function maskInput(o, f) {
    v_obj = o
    v_fun = f
    setTimeout("execmascara()", 1)
}

function execmascara() {
    v_obj.value = v_fun(v_obj.value)
}

function mdate(v) {
    v = v.replace(/\D/g, "");
    v = v.replace(/(\d{2})(\d)/, "$1/$2");
    v = v.replace(/(\d{2})(\d{2})$/, "$1$2");
    return v;
}

function minteger(v) {
    return v.replace(/\D/g, "")
}

function mcc(v) {
    v = v.replace(/\D/g, "");
    v = v.replace(/^(\d{4})(\d)/g, "$1 $2");
    v = v.replace(/^(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3");
    v = v.replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4");
    return v;
}

//cpf validate
function cpfValidate(strCPF) {
    var Soma;
    var Resto;
    console.log(strCPF);

    Soma = 0;
    strCPF = strCPF.replace(/[.-\s]/g, "");
    if (strCPF == "00000000000") { return false; }
    for (i = 1; i <= 9; i++) { Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i); }

    Resto = (Soma * 10) % 11;
    if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
    if (Resto != parseInt(strCPF.substring(9, 10))) { return false; }

    Soma = 0;
    for (i = 1; i <= 10; i++) { Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i); }

    Resto = (Soma * 10) % 11;
    if ((Resto == 10) || (Resto == 11)) { Resto = 0; }
    if (Resto != parseInt(strCPF.substring(10, 11))) { return false; }

    return true;
}