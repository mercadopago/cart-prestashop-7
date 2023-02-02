/**
 * 2007-2023 PrestaShop
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
 *  @copyright 2007-2023 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/* global Mercadopago, Option, jQuery, $ */
/* eslint no-return-assign: 0 */

(function () {
  var additionalInfoNeeded = {};

  var seller = {
    site_id: '',
    public_key: '',
  };

  var psVersion = '';

  var mp = null;
  var mpCardForm = null;
  var cvvLength = null;
  var submitted = false;
  var mpRemountCardForm = false;

  /**
   * Initialise vars to use on JS custom-card.js
   *
   * @param {object} mpCustom
   */
  window.initializeCustom = function (mpCustom) {
    seller.site_id = mpCustom.site_id;
    seller.public_key = mpCustom.public_key;
    psVersion = mpCustom.ps_version;

    loadCardForm();

    setChangeEventOnCardNumber();
  };

  /**
   * Create instance of Mercado Pago sdk v2 and mount form
   */
  function loadCardForm() {
    mp = new MercadoPago(seller.public_key);

    mpCardForm = mp.cardForm({
      amount: getAmount(),
      autoMount: true,
      processingMode: 'aggregator',
      iframe: true,
      form: {
        id: 'mp_custom_checkout',
        cardNumber: {
          id: 'id-card-number',
          placeholder: '0000 0000 0000 0000',
          style: {
            "font-size": "1rem",
            "font-family": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            "color": "#7a7a7a"
          }
        },
        cardholderName: { id: 'id-card-holder-name'},
        cardExpirationDate: {
          id: 'id-card-expiration-date',
          placeholder: 'MM/YYYY',
          mode: "undefined",
          style: {
            "font-size": "1rem",
            "font-family": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            "color": "#7a7a7a"
          }
        },
        securityCode: {
          id: 'id-security-code',
          placeholder: 'CVV',
          style: {
            "font-size": "1rem",
            "font-family": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            "color": "#7a7a7a"
          }
        },
        installments: { id: 'id-installments' },
        identificationType: { id: 'id-docType' },
        identificationNumber: { id: 'id-doc-number' },
        issuer: { id: 'id-issuers-options' },
      },
      callbacks: {
        onFormMounted: function (error) {
          if (error) {
            return console.warn('Form Mounted handling error: ', error);
          }
          additionalInfoHandler();
        },
        onFormUnmounted: function (error) {
          clearInputs();

          if (error) {
            return console.warn('Form Unmounted handling error: ', error);
          }

          if (mpRemountCardForm) {
            loadCardForm();
            mpRemountCardForm = false;
          } else {
            setTimeout(() => { loadCardForm(); }, 5000);
          }
        },
        onIdentificationTypesReceived: function (error, identificationTypes) {
          if (error) {
            return console.warn('identificationTypes handling error: ', error);
          }
        },
        onPaymentMethodsReceived: function (error, paymentMethods) {
          hideErrors();
          if (error) {
            return console.warn('paymentMethods handling error: ', error);
          }

          var paymentTypeId = paymentMethods[0].payment_type_id;
          setImageCard(paymentMethods[0].thumbnail);
          setPaymentTypeId(paymentTypeId);
          handleInstallments(paymentTypeId);
          loadAdditionalInfo(paymentMethods[0].additional_info_needed);
          additionalInfoHandler();
        },
        onIssuersReceived: function (error, issuers) {
          if (error) {
            return console.warn('issuers handling error: ', error);
          }
        },
        onInstallmentsReceived: function (error, installments) {
          if (error) {
            return console.warn('installments handling error: ', error);
          }

          setChangeEventOnInstallments(seller.site_id, installments.payer_costs);
        },
        onCardTokenReceived: function (error, token) {
          if (error) {
            showErrors(error);
            return console.warn('Token handling error: ', error);
          }

          sdkResponseHandler(error);
        },
        onValidityChange: function (error, field) {
          if (error) {
            if (field === "cardNumber") {
              if (error[0].code !== "invalid_length") {
                cardClean();
              }
            }
          }
        }
      },
    });
  }

  /**
   *
   */
  function cardClean() {
    document.querySelector("#id-card-number", "no-repeat #fff");
    var issuerField = document.getElementById("mpIssuer");
    if (issuerField) {
      document.getElementById("mpIssuer").innerHTML = " ";
    }

    additionalInfoHandler();
    clearInputs();
    hideErrors();
    remountCardForm();
  }

  /**
   *
   */
  function remountCardForm() {
    mpRemountCardForm = true;
    mpCardForm.unmount();
  }

  /**
   * Set payment type id
   *
   * @param {string} paymentTypeId
   */
  function setPaymentTypeId(paymentTypeId) {
    document.querySelector('#payment_type_id').value = paymentTypeId;
  }

  /**
   * Get Amount end calculate discount for hide inputs
   */
  function getAmount() {
    return document.getElementById('amount').value;
  }

  /**
   * Set if the form has been submitted
   */
  function setFormSubmit() {
    submitted = true;
  }

  /**
   * Set Imagem card on element
   *
   * @param string secureThumbnail
   */
  function setImageCard(secureThumbnail) {
    var mpCardNumber = document.getElementById('id-card-number');
    mpCardNumber.style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
    mpCardNumber.style.backgroundSize = 'auto 24px';
  }

  /**
   *
   * Load Additional Info to use for build payment form
   *
   * @param array sdkAdditionalInfoNeeded
   */
  function loadAdditionalInfo(sdkAdditionalInfoNeeded) {
    additionalInfoNeeded = {
      issuer: false,
      cardholder_name: false,
      cardholder_identification_type: false,
      cardholder_identification_number: false,
    };

    for (var i = 0; i < sdkAdditionalInfoNeeded.length; i++) {
      if (sdkAdditionalInfoNeeded[i] === 'issuer_id') {
        additionalInfoNeeded.issuer = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_name') {
        additionalInfoNeeded.cardholder_name = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_type') {
        additionalInfoNeeded.cardholder_identification_type = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_number') {
        additionalInfoNeeded.cardholder_identification_number = true;
      }
    }
  }

  /**
   * Check what information is necessary to pay and show inputs
   */
  function additionalInfoHandler() {
    if (additionalInfoNeeded.cardholder_name) {
      document.getElementById('mp-card-holder-div').style.display = 'block';
    } else {
      document.getElementById('mp-card-holder-div').style.display = 'none';
    }

    if (additionalInfoNeeded.issuer) {
      document.getElementById('container-issuers').style.display = 'block';
      document.getElementById('container-installments').classList.remove('col-md-12');
      document.getElementById('container-installments').classList.add('col-md-8');
    } else {
      clearIssuer();
      clearTax();
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      document.getElementById('mp-doc-div-title').style.display = 'block';
      document.getElementById('mp-doc-div').style.display = 'block';
      document.getElementById('mp-doc-type-div').style.display = 'block';
      mp.getIdentificationTypes();
    } else {
      document.getElementById('mp-doc-type-div').style.display = 'none';
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      document.getElementById('mp-doc-div-title').style.display = 'block';
      document.getElementById('mp-doc-div').style.display = 'block';
      document.getElementById('mp-doc-number-div').style.display = 'block';
    } else {
      document.getElementById('mp-doc-number-div').style.display = 'none';
    }

    if (
      !additionalInfoNeeded.cardholder_identification_type &&
      !additionalInfoNeeded.cardholder_identification_number
    ) {
      document.getElementById('mp-doc-div-title').style.display = 'none';
      document.getElementById('mp-doc-div').style.display = 'none';
    }
  }

  /**
   * Clear input select and change to default layout
   */
  function clearIssuer() {
    document.getElementById('container-issuers').style.display = 'none';
    document.getElementById('container-installments').classList.remove('col-md-8');
    document.getElementById('container-installments').classList.add('mp-md-12');
    document.getElementById('id-issuers-options').innerHTML = '';
  }

  /**
   * Clear Tax
   */
  function clearTax() {
    document.querySelector('.mp-text-cft').innerHTML = '';
    document.querySelector('.mp-text-tea').innerHTML = '';
  }

  /**
   * Clear Inputs
   */
  function clearInputs() {
    hideErrors();
    clearTax();
    document.getElementById('id-card-number').style.background = 'no-repeat #fff';
    document.getElementById('id-card-expiration-date').value = '';
    document.getElementById('id-doc-number').value = '';
    document.getElementById('id-security-code').value = '';
    document.getElementById('id-card-holder-name').value = '';
  }

  /**
   * Disable select for debit cards
   *
   * @param string payment_type_id
   */
  function handleInstallments(payment_type_id) {
    if (payment_type_id === 'debit_card') {
      document.getElementById('id-installments').setAttribute('disabled', 'disabled');
    } else {
      document.getElementById('id-installments').removeAttribute('disabled');
    }
  }

  /**
   * Show governmental taxes in MLA
   *
   * @params any payer_costs
   */
  function setChangeEventOnInstallments(siteId, payer_costs) {
    if (siteId === 'MLA') {
      clearTax();
      document.querySelector('#id-installments').addEventListener('change', function (e) {
        showTaxes(payer_costs);
      });
    }
  }

  /**
   * Show governmental taxes in MLA
   *
   * @params any payer_costs
   */
  function showTaxes(payer_costs) {
    var installmentsSelect = document.querySelector('#id-installments');

    for (var i = 0; i < payer_costs.length; i++) {
      if (payer_costs[i].installments === installmentsSelect.value) {
        var taxes_split = payer_costs[i].labels[0].split('|');
        var cft = taxes_split[0].replace('_', ' ');
        var tea = taxes_split[1].replace('_', ' ');

        if (cft === 'CFT 0,00%' && tea === 'TEA 0,00%') {
          cft = '';
          tea = '';
        }

        document.querySelector('#mp-tax-cft-text').innerHTML = cft;
        document.querySelector('#mp-tax-tea-text').innerHTML = tea;
      }
    }
  }

  /**
   * Clears card number input on keyup when installments are not available
   *
   */
  function setChangeEventOnCardNumber() {
    document.getElementById('id-installments').addEventListener('keyup', function (e) {
      if (e.target.value.length <= 1) {
        clearInputs();
      }
    });
  }

  /**
   * Show errors
   *
   * @param  {object}  error
   */
  function showErrors(errors) {
    var form = getFormCustom();
    var sdkErrors = trackedSDKErrors();

    let errorMessage = errors.message || errors[0].message;
    let errorField = errors.field || (errors[0].field ? errors[0].field : undefined);
    var previousField;

    if (errorMessage && !errorField) {
      otherMessages(errors);
    }

    if (errors.length >= 1) {
      showIframeErrors(errors);

      errors.forEach(error => {
        if (error.field && previousField !== error.field) {
          errorField = error.field;

          if (error.field === "expirationDate") {
            errorField = expirationDateHandler(error);
          }

          let formattedError = `${error.cause}_${errorField}`;
          var span;

          sdkErrors.forEach((sdkError) => {
            if (error.message === sdkError.message) {
              span = form.querySelector("#" + formattedError + "_" + sdkError.code);

              if (span !== undefined) {
                span.style.display = "block";
              }
            }

            previousField = error.field;
          });
        }

      });
    }

    focusInputError();
    getConditionTerms();
  }

  /**
   *
   * @param {*} error
   * @returns
   */
  function expirationDateHandler(error) {
    expiration = error.message.includes("expirationMonth") ?
      error.field + "_expirationMonth" :
      error.field + "_expirationYear";
    return expiration;
  }

  /**
   *
   * @param {*} errors
   */
  function showIframeErrors(errors) {
    errors.forEach((error) => {
      let field;
      if (error.field === "cardNumber") {
        field = document.getElementById("id-card-number");
        field.classList.add("mp-form-control-error");
      }
      if (error.field === "expirationDate") {
        field = document.getElementById("id-card-expiration-date");
        field.classList.add("mp-form-control-error");
      }
      if (error.field === "securityCode") {
        field = document.getElementById("id-security-code");
        field.classList.add("mp-form-control-error");
      }
    });
  }

  /**
   *
   * @returns
   */
  function trackedSDKErrors() {
    var date = new Date();
    var currentYear = date.getFullYear();
    var currentMonth = date.getMonth() + 1;
    var sdkErrors = [
      {
        code: "mp001",
        message: "cardnumber should be a number.",
      },
      {
        code: "mp002",
        message: "cardNumber should be of length between '8' and '19'.",
      },
      {
        code: "mp003",
        message: "expirationMonth should be a number.",
      },
      {
        code: "mp004",
        message: "expirationYear should be of length '2' or '4'.",
      },
      {
        code: "mp005",
        message: "expirationYear should be a number.",
      },
      {
        code: "mp006",
        message: "securityCode should be a number.",
      },
      {
        code: "mp007",
        message: "securityCode should be of length '3' or '4'.",
      },
      {
        code: "mp008",
        message: "expirationMonth should be a value from 1 to 12.",
      },
      {
        code: "mp009",
        message: `expirationYear value should be greater or equal than ${currentYear}.`,
      },
      {
        code: "mp010",
        message: "securityCode should be of length '4'.",
      },
      {
        code: "mp011",
        message: "cardNumber should be of length '15'.",
      },
      {
        code: "mp012",
        message: `expirationMonth value should be greater than '${currentMonth}' or expiration
                  year value should be greater than '${currentYear}.`,
      },
      {
        code: "mp013",
        message: "securityCode should be of length '3'.",
      },
      {
        code: "mp014",
        message: "cardNumber should be of length '16'.",
      },
    ];
    return sdkErrors;
  }

  /**
   * Focus input with error
   *
   * @return bool
   */
  function focusInputError() {
    if (document.querySelectorAll('.mp-form-control-error') !== undefined) {
      var formInputs = document.querySelectorAll('.mp-form-control-error');
      formInputs[0].focus();
    }
  }

  /**
   * Disable error spans
   */
  function hideErrors() {
    for (var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
      var field = document.querySelectorAll('[data-checkout]')[x];
      field.classList.remove('mp-form-control-error');
    }

    for (var y = 0; y < document.querySelectorAll('.mp-erro-form').length; y++) {
      var small = document.querySelectorAll('.mp-erro-form')[y];
      small.style.display = 'none';
    }
  }

  /**
   * Get condition terms input on PS17
   */
  function getConditionTerms() {
    var terms = document.getElementById('conditions_to_approve[terms-and-conditions]');
    if (typeof terms === 'object' && terms !== null) {
      terms.checked = false;
      return terms.checked;
    }
  }

  /**
   * Get form
   */
  function getFormCustom() {
    return document.querySelector('#mp_custom_checkout');
  }

  /**
   * Validate inputs
   */
  function validateInputs() {
    hideErrors();

    var fixedInputs = validateFixedInputs();
    var additionalInputs = validateAdditionalInputs();

    if (fixedInputs || additionalInputs) {
      focusInputError();
      return false;
    }

    return true;
  }

  /**
   * Validate fixed Inputs is empty
   *
   * @return bool
   */
  function validateFixedInputs() {
    var emptyInputs = false;
    var form = getFormCustom();
    var formInputs = form.querySelectorAll('[data-checkout]');
    var fixedInputs = ['cardNumber', 'installments'];
    var mpInstallments = document.getElementById("id-installments").value;

    for (var x = 0; x < formInputs.length; x++) {
      var element = formInputs[x];

      // Check is a input to create token.
      var attribute = element.getAttribute('data-checkout');

      if (fixedInputs.indexOf(attribute) > -1) {
        if (element.value === -1 || element.value === '' || element.value === undefined) {
          var span = form.querySelectorAll('small[data-main="#' + element.id + '"]');

          if (
            (attribute === "cardNumber" && mpInstallments === "") ||
            mpInstallments === undefined ||
            attribute !== "cardNumber"
          ) {
            span[0].style.display = 'block';
            element.classList.add("mp-form-control-error");
            emptyInputs = true;
          }
        }
      }
    }

    return emptyInputs;
  }

  /**
   * Validate Additional Inputs
   *
   * @return bool
   */
  function validateAdditionalInputs() {
    var emptyInputs = false;

    if (additionalInfoNeeded.issuer) {
      var inputMpIssuer = document.getElementById('id-issuers-options');
      if (inputMpIssuer.value === -1 || inputMpIssuer.value === '') {
        inputMpIssuer.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_name) {
      var inputCardholderName = document.getElementById('id-card-holder-name');
      if (
        inputCardholderName.value === -1 ||
        inputCardholderName.value === "" ||
        !/^[a-zA-ZãÃáÁàÀâÂäÄẽẼéÉèÈêÊëËĩĨíÍìÌîÎïÏõÕóÓòÒôÔöÖũŨúÚùÙûÛüÜçÇ’ñÑ .']+$/
          .test(inputCardholderName.value)
      ) {
        document.getElementById(inputCardholderName.id).style.display = "block";
        var small = document.querySelectorAll('small[data-main="#' + inputCardholderName.id + '"]');
        for (let index = 0; index < small.length; index++) {
          small[index].style.display = "block";
        }
        inputCardholderName.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      let inputDocType = document.getElementById('id-docType');
      if (inputDocType.value === -1 || inputDocType.value === '') {
        inputDocType.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      let inputDocType = document.getElementById('id-docType');
      var docNumber = document.getElementById('id-doc-number');
      if (docNumber.value === -1 || docNumber.value === '') {
        docNumber.classList.add('mp-form-control-error');
        docNumber.style.display = 'inline-block';
        document.querySelector('small[data-main="#' + docNumber.id + '"]').style.display = "block";
        emptyInputs = true;
      } else if ( inputDocType.value.toLowerCase() === ('cpf') ) {
        if (!validateDocNumber(docNumber.value)) {
          docNumber.classList.add('mp-form-control-error');
          docNumber.style.display = 'inline-block';
          document.querySelector('small[data-main="#' + docNumber.id + '"]').style.display = "block";
        }
      }
    }

    return emptyInputs;
  }

  /**
   * Validate doc number
   */
  function validateDocNumber(docNumber) {
    if (docNumber.length === 11) {
      return validateCPF(docNumber);
    }
    return false;
  }

  /**
   * CPF validator
   * @param {*} strCPF
   * @returns
   */
  function validateCPF(strCPF) {
    var sum;
    var remainder;
    sum = 0;

    if (strCPF == "00000000000") return false;
    if (strCPF == "11111111111") return false;
    if (strCPF == "22222222222") return false;
    if (strCPF == "33333333333") return false;
    if (strCPF == "44444444444") return false;
    if (strCPF == "55555555555") return false;
    if (strCPF == "66666666666") return false;
    if (strCPF == "77777777777") return false;
    if (strCPF == "88888888888") return false;
    if (strCPF == "99999999999") return false;

    for (i = 1; i <= 9; i++) {
      sum = sum + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
    }

    remainder = (sum * 10) % 11;

    if (remainder == 10 || remainder == 11) remainder = 0;
    if (remainder != parseInt(strCPF.substring(9, 10))) return false;

    sum = 0;
    for (i = 1; i <= 10; i++) {
      sum = sum + parseInt(strCPF.substring(i - 1, i)) * (12 - i);
    }
    remainder = (sum * 10) % 11;

    if (remainder == 10 || remainder == 11) remainder = 0;

    if (remainder != parseInt(strCPF.substring(10, 11))) {
      document.getElementById("mpDocNumber");
      return false;
    }
    return true;
  }

  /**
   * Disable finish order button
   *
   * @param string psVersion
   */
  function disableFinishOrderButton(psVersion) {
    if (psVersion === 'six') {
      var sixButton = document.getElementById('mp-custom-finish-order');
      sixButton.setAttribute('disabled', 'disabled');
    } else if (psVersion === 'seven') {
      var sevenButton = document.querySelector('#payment-confirmation button');
      if (sevenButton) {
        sevenButton.setAttribute('disabled', 'disabled');
      }
    }
  }

  /**
   * Handler Response of mp.createToken
   *
   * @param number error
   * @param object token
   */
  function sdkResponseHandler(error) {
    if (error) {
      showErrors(error);
      return;
    }

    if (submitted) {
      return;
    }

    var formData = mpCardForm.getCardFormData();

    document.querySelector('#card_token_id').value = formData.token;
    document.querySelector('#mp_issuer').value = formData.issuerId;
    document.querySelector('#mp_installments').value = formData.installments;
    document.querySelector('#payment_method_id').value = formData.paymentMethodId;

    setFormSubmit();
    disableFinishOrderButton(psVersion);
    document.forms.mp_custom_checkout.submit();
  }

  /**
   * Handle submit from form
   */
  jQuery(function () {
    if (document.forms.mp_custom_checkout !== undefined) {
      document.forms.mp_custom_checkout.onsubmit = function () {
        if (validateInputs()) {
          mpCardForm.createCardToken();
          return false;
        }

        getConditionTerms();
        return false;
      };
    }
  });
})();
