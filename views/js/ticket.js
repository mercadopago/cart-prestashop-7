/**
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/* eslint no-return-assign: 0 */

(function () {
  var mercadoPagoDocnumber = 'CPF';

  var sellerTicket = {
    site_id: '',
    ps_version: '',
  };

  /**
   * Validate site_id
   */
  window.mpValidateSellerInfo = function (siteId, psVersion) {
    sellerTicket.site_id = siteId;
    sellerTicket.ps_version = psVersion;
  };

  /**
   * Validate input depending on document type
   */
  window.validateDocumentInputs = function () {
    if (sellerTicket.site_id === 'MLB') {
      var mpBoxLastname = document.getElementById('mp_box_lastname');
      var mpBoxFirstname = document.getElementById('mp_box_firstname');
      var mpFirstnameLabel = document.getElementById('mp_firstname_label');
      var mpSocialnameLabel = document.getElementById('mp_socialname_label');
      var mpCpfLabel = document.getElementById('mp_cpf_label');
      var mpCnpjLabel = document.getElementById('mp_cnpj_label');
      var mpDocNumber = document.getElementById('mp_doc_number');
      var mpDocType = document.querySelectorAll('input[type=radio][name="mercadopago_ticket[docType]"]');

      mpCnpjLabel.style.display = 'none';
      mpSocialnameLabel.style.display = 'none';

      var onChangeValidateDocumentInput = function () {
        if (this.value === 'CPF') {
          mpCpfLabel.style.display = 'table-cell';
          mpBoxLastname.style.display = 'block';
          mpFirstnameLabel.style.display = 'table-cell';
          mpCnpjLabel.style.display = 'none';
          mpSocialnameLabel.style.display = 'none';
          mpBoxFirstname.classList.add('col-md-4');
          mpBoxFirstname.classList.remove('col-md-8');
          mpDocNumber.setAttribute('maxlength', '14');
          mpDocNumber.setAttribute('onkeyup', 'maskInput(this, mcpf)');
          mercadoPagoDocnumber = 'CPF';
        } else {
          mpCpfLabel.style.display = 'none';
          mpBoxLastname.style.display = 'none';
          mpFirstnameLabel.style.display = 'none';
          mpCnpjLabel.style.display = 'table-cell';
          mpSocialnameLabel.style.display = 'table-cell';
          mpBoxFirstname.classList.add('col-md-8');
          mpBoxFirstname.classList.remove('col-md-4');
          mpDocNumber.setAttribute('maxlength', '18');
          mpDocNumber.setAttribute('onkeyup', 'maskInput(this, mcnpj)');
          mercadoPagoDocnumber = 'CNPJ';
        }
      };

      for (var i = 0; i < mpDocType.length; i++) {
        mpDocType[i].addEventListener('change', onChangeValidateDocumentInput);
      }
    }
  };

  /**
   * Handler form submit
   * @return {bool}
   */
  window.mercadoPagoFormHandlerTicket = function () {
    var ticketForm = getFormTicket();

    if (ticketForm !== undefined) {
      ticketForm.onsubmit = function () {
        if (sellerTicket.site_id === 'MLB') {
          if (!validateInputs() || !validateDocumentNumber()) {
            return false;
          }
        }

        if (sellerTicket.site_id === 'MLU') {
          if (!validateDocumentNumber()) {
            return false;
          }
        }

        disableFinishOrderButton();
        ticketForm.submit();
      };
    }
  };

  /**
   * Get form
   */
  function getFormTicket() {
    return document.querySelector('#mp_ticket_checkout');
  }

  /**
   * Get condition terms input on PS17
   */
  function getConditionTerms() {
    var terms = document.getElementById('conditions_to_approve[terms-and-conditions]');
    if (typeof terms === 'object' && terms !== null) {
      terms.checked = false;
      return false;
    }
  }

  /**
   * Disable finish order button
   * @param {string} psVersion
   */
  function disableFinishOrderButton() {
    if (sellerTicket.ps_version === 'six') {
      var sixButton = document.getElementById('mp-ticket-finish-order');
      sixButton.setAttribute('disabled', 'disabled');
    } else if (sellerTicket.ps_version === 'seven') {
      var sevenButton = document.querySelector('#payment-confirmation button');
      sevenButton.setAttribute('disabled', 'disabled');
    }
  }

  /**
   * Validate if all inputs are valid
   */
  function validateInputs() {
    var form = getFormTicket();
    var formInputs = form.querySelectorAll('[data-checkout]');
    var small = form.querySelectorAll('.mp-erro-febraban');

    // Show or hide error message and border
    for (var i = 0; i < formInputs.length; i++) {
      var element = formInputs[i];
      var input = form.querySelector(small[i].getAttribute('data-main'));

      if (element.parentNode.style.display !== 'none' && (element.value === -1 || element.value === '')) {
        small[i].style.display = 'inline-block';
        input.classList.add('mp-form-control-error');
      } else {
        small[i].style.display = 'none';
        input.classList.remove('mp-form-control-error');
      }
    }

    // Focus on the element with error
    for (var j = 0; j < formInputs.length; j++) {
      var focusElement = formInputs[j];
      if (
        focusElement.parentNode.style.display !== 'none' &&
        (focusElement.value === -1 || focusElement.value === '')
      ) {
        focusElement.focus();
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
  function validateDocumentNumber() {
    var docnumberInput = document.getElementById('mp_doc_number');
    var docnumberError = document.getElementById('mp_error_docnumber');
    var docnumberValidate = false;

    if (sellerTicket.site_id === 'MLB') {
      docnumberValidate = validateDocTypeMLB(docnumberInput.value);
    }

    if (sellerTicket.site_id === 'MLU') {
      docnumberValidate = validateDocTypeMLU(docnumberInput.value);
    }

    if (!docnumberValidate) {
      docnumberError.style.display = 'block';
      docnumberInput.classList.add('mp-form-control-error');
      docnumberInput.focus();
      getConditionTerms();
    } else {
      docnumberError.style.display = 'none';
      docnumberInput.classList.remove('mp-form-control-error');
      docnumberValidate = true;
    }

    return docnumberValidate;
  }

  /**
   * Validate Document number for MLB
   * @param {string} docnumber
   * @return {bool}
   */
  function validateDocTypeMLB(docnumber) {
    if (mercadoPagoDocnumber === 'CPF') {
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
  function validateDocTypeMLU(docnumber) {
    if (docnumber !== '') {
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
  function validateCPF(strCPF) {
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
    if (Resto === 10 || Resto === 11) {
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
    if (Resto === 10 || Resto === 11) {
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
  function validateCNPJ(strCNPJ) {
    strCNPJ = strCNPJ.replace(/[^\d]+/g, '');

    if (strCNPJ === '') {
      return false;
    }

    if (strCNPJ.length !== 14) {
      return false;
    }

    if (
      strCNPJ === '00000000000000' ||
      strCNPJ === '11111111111111' ||
      strCNPJ === '22222222222222' ||
      strCNPJ === '33333333333333' ||
      strCNPJ === '44444444444444' ||
      strCNPJ === '55555555555555' ||
      strCNPJ === '66666666666666' ||
      strCNPJ === '77777777777777' ||
      strCNPJ === '88888888888888' ||
      strCNPJ === '99999999999999'
    ) {
      return false;
    }

    var tamanho = strCNPJ.length - 2;
    var numeros = strCNPJ.substring(0, tamanho);
    var digitos = strCNPJ.substring(tamanho);
    var soma = 0;
    var pos = tamanho - 7;

    for (var i = tamanho; i >= 1; i--) {
      soma += numeros.charAt(tamanho - i) * pos--;
      if (pos < 2) {
        pos = 9;
      }
    }

    var resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);

    if (resultado.toString() !== digitos.charAt(0)) {
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

    resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);

    if (resultado.toString() !== digitos.charAt(1)) {
      return false;
    }

    return true;
  }

  /**
   * Validate CI MLU
   * @param {string} docNumber
   * @return {bool}
   */
  function validateCI(ci) {
    var x = 0;
    var digitValidation = null;

    ci = ci.replace(/\D/g, '');
    var digit = Number(ci[ci.length - 1]);

    ci = ci.replace(/[0-9]$/, '');

    if (ci.length <= 6) {
      for (var i = ci.length; i < 7; i++) {
        ci = '0' + ci;
      }
    }

    for (var j = 0; j < 7; j++) {
      x += (parseInt('2987634'[j]) * parseInt(ci[j])) % 10;
    }

    if (x % 10 === 0) {
      digitValidation = 0;
    } else {
      digitValidation = 10 - (x % 10);
    }

    return digit === digitValidation;
  }
})();
