/**
* 2007-2020 PrestaShop
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

(function () {

    var mercado_pago_docnumber = 'CPF';

    var seller_ticket = {
        site_id: ''
    };

    /**
     * Validate site_id
     */
    window.mpValidateSiteId = function (site_id_ticket) {
        seller_ticket.site_id = site_id_ticket;
    };

    /**
     * Validate input depending on document type
     */
    window.validateDocumentInputs = function () {
        if (seller_ticket.site_id === 'MLB') {
            var mp_box_lastname = document.getElementById('mp_box_lastname');
            var mp_box_firstname = document.getElementById('mp_box_firstname');
            var mp_firstname_label = document.getElementById('mp_firstname_label');
            var mp_socialname_label = document.getElementById('mp_socialname_label');
            var mp_cpf_label = document.getElementById('mp_cpf_label');
            var mp_cnpj_label = document.getElementById('mp_cnpj_label');
            var mp_doc_number = document.getElementById('mp_doc_number');
            var mp_doc_type = document.querySelectorAll('input[type=radio][name="mercadopago_ticket[docType]"]');

            mp_cnpj_label.style.display = 'none';
            mp_socialname_label.style.display = 'none';

            for (var i = 0; i < mp_doc_type.length; i++) {
                mp_doc_type[i].addEventListener('change', function () {
                    if (this.value === 'CPF') {
                        mp_cpf_label.style.display = 'table-cell';
                        mp_box_lastname.style.display = 'block';
                        mp_firstname_label.style.display = 'table-cell';
                        mp_cnpj_label.style.display = 'none';
                        mp_socialname_label.style.display = 'none';
                        mp_box_firstname.classList.add('col-md-4');
                        mp_box_firstname.classList.remove('col-md-8');
                        mp_doc_number.setAttribute('maxlength', '14');
                        mp_doc_number.setAttribute('onkeyup', 'maskInput(this, mcpf)');
                        mercado_pago_docnumber = 'CPF';
                    } else {
                        mp_cpf_label.style.display = 'none';
                        mp_box_lastname.style.display = 'none';
                        mp_firstname_label.style.display = 'none';
                        mp_cnpj_label.style.display = 'table-cell';
                        mp_socialname_label.style.display = 'table-cell';
                        mp_box_firstname.classList.add('col-md-8');
                        mp_box_firstname.classList.remove('col-md-4');
                        mp_doc_number.setAttribute('maxlength', '18');
                        mp_doc_number.setAttribute('onkeyup', 'maskInput(this, mcnpj)');
                        mercado_pago_docnumber = 'CNPJ';
                    }
                });
            }
        }
    };

    /**
     * Handler form submit
     * @return {bool}
     */
    window.mercadoPagoFormHandlerTicket = function () {
        if (document.forms['mp_ticket_checkout'] !== undefined) {
            document.forms['mp_ticket_checkout'].onsubmit = function () {

                if (seller_ticket.site_id === 'MLB') {
                    if (validateInputs() && validateDocumentNumber()) {
                        return true;
                    } else {
                        return false;
                    }
                }

                if (seller_ticket.site_id === 'MLU') {
                    if (validateDocumentNumber()) {
                        return true;
                    } else {
                        return false;
                    }
                }

                return true;
            };
        }
    };

    /**
     * Get form
     */
    function getFormTicket()
    {
        return document.querySelector('#mp_ticket_checkout');
    }

    /**
     * Get condition terms input on PS17
     */
    function getConditionTerms()
    {
        var terms = document.getElementById('conditions_to_approve[terms-and-conditions]');
        if (typeof terms === 'object' && terms !== null) {
            return terms.checked = false;
        }
    }

    /**
     * Validate if all inputs are valid
     */
    function validateInputs()
    {
        var form = getFormTicket();
        var form_inputs = form.querySelectorAll('[data-checkout]');
        var small = form.querySelectorAll('.mp-erro-febraban');

        //Show or hide error message and border
        for (var i = 0; i < form_inputs.length; i++) {
            var element = form_inputs[i];
            var input = form.querySelector(small[i].getAttribute('data-main'));

            if (element.parentNode.style.display !== 'none' && (element.value === -1 || element.value === '')) {
                small[i].style.display = 'inline-block';
                input.classList.add('mp-form-control-error');
            } else {
                small[i].style.display = 'none';
                input.classList.remove('mp-form-control-error');
            }
        }

        //Focus on the element with error
        for (var j = 0; j < form_inputs.length; j++) {
            var element = form_inputs[j];
            if (element.parentNode.style.display !== 'none' && (element.value === -1 || element.value === '')) {
                element.focus();
                getConditionTerms();
                return false;
            }
        }

        return true;
    }

    /**
     * Validate document number
     * @return {bool}
     */
    function validateDocumentNumber()
    {
        var docnumber_input = document.getElementById('mp_doc_number');
        var docnumber_error = document.getElementById('mp_error_docnumber');
        var docnumber_validate = false;

        if (seller_ticket.site_id === 'MLB') {
            docnumber_validate = validateDocTypeMLB(docnumber_input.value);
        }

        if (seller_ticket.site_id === 'MLU') {
            docnumber_validate = validateDocTypeMLU(docnumber_input.value);
        }

        if (!docnumber_validate) {
            docnumber_error.style.display = 'block';
            docnumber_input.classList.add('mp-form-control-error');
            docnumber_input.focus();
            getConditionTerms();
        } else {
            docnumber_error.style.display = 'none';
            docnumber_input.classList.remove('mp-form-control-error');
            docnumber_validate = true;
        }

        return docnumber_validate;
    }

    /**
     * Validate Document number for MLB
     * @param {string} docnumber
     * @return {bool}
     */
    function validateDocTypeMLB(docnumber)
    {
        if (mercado_pago_docnumber === 'CPF') {
            return validateCPF(docnumber);
        } else {
            return validateCNPJ(docnumber);
        }
    }

    /**
     * Validate Document number for MLU
     * @param {string} docnumber
     * @return {bool}
     */
    function validateDocTypeMLU(docnumber)
    {
        if (docnumber != '') {
            return validateCI(docnumber);
        } else {
            return false;
        }
    }

    /**
     * Validate CPF
     * @param {string} strCPF
     * @return {bool}
     */
    function validateCPF(strCPF)
    {
        var Soma;
        var Resto;

        Soma = 0;
        strCPF = strCPF.replace(/[.-\s]/g, '');

        if (strCPF === '00000000000') {
            return false;
        }

        for (var i = 1; i <= 9; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
        }

        Resto = (Soma * 10) % 11;
        if ((Resto === 10) || (Resto === 11)) {
            Resto = 0;
        }
        if (Resto !== parseInt(strCPF.substring(9, 10))) {
            return false;
        }

        Soma = 0;
        for (var k = 1; k <= 10; k++) {
            Soma = Soma + parseInt(strCPF.substring(k - 1, k)) * (12 - k);
        }

        Resto = (Soma * 10) % 11;
        if ((Resto === 10) || (Resto === 11)) {
            Resto = 0;
        }
        if (Resto !== parseInt(strCPF.substring(10, 11))) {
            return false;
        }

        return true;
    }

    /**
     * Validate CNPJ
     * @param {string} strCNPJ
     * @return {bool}
     */
    function validateCNPJ(strCNPJ)
    {
        strCNPJ = strCNPJ.replace(/[^\d]+/g, '');

        if (strCNPJ == '') {
            return false;
        }

        if (strCNPJ.length != 14) {
            return false;
        }

        if (strCNPJ == '00000000000000' ||
            strCNPJ == '11111111111111' ||
            strCNPJ == '22222222222222' ||
            strCNPJ == '33333333333333' ||
            strCNPJ == '44444444444444' ||
            strCNPJ == '55555555555555' ||
            strCNPJ == '66666666666666' ||
            strCNPJ == '77777777777777' ||
            strCNPJ == '88888888888888' ||
            strCNPJ == '99999999999999') {
            return false;
        }

        tamanho = strCNPJ.length - 2;
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

     /**
     * Validate CI MLU
     * @param {string} docNumber
     * @return {bool}
     */
    function validateCI(docNumber)
    {
        var x = 0;
        var y = 0;
        var docCI = 0;
        var dig = docNumber[docNumber.length - 1];

        if (docNumber.length <= 6) {
            for (y = docNumber.length; y < 7; y++) {
                docNumber = '0' + docNumber;
            }
        }
        for (y = 0; y < 7; y++) {
            x += (parseInt("2987634"[y]) * parseInt(docNumber[y])) % 10;
        }
        if (x % 10 === 0) {
            docCI = 0;
        } else {
            docCI = 10 - x % 10;
        }
        return (dig == docCI);
    }

})();