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

    setChangeEventOnExpirationDate();
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
      form: {
        id: 'mp_custom_checkout',
        cardNumber: { id: 'id-card-number' },
        cardholderName: { id: 'id-card-holder-name' },
        cardExpirationMonth: { id: 'id-card-expiration-month' },
        cardExpirationYear: { id: 'id-card-expiration-year' },
        securityCode: { id: 'id-security-code' },
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
        },
        onIdentificationTypesReceived: function (error, identificationTypes) {
          if (error) {
            return console.warn('identificationTypes handling error: ', error);
          }
        },
        onPaymentMethodsReceived: function (error, paymentMethods) {
          if (error) {
            return console.warn('paymentMethods handling error: ', error);
          }

          var paymentTypeId = paymentMethods[0].payment_type_id;

          clearInputs();
          setImageCard(paymentMethods[0].thumbnail);
          setCvvLength(paymentMethods[0].settings[0].security_code.length);
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
      },
    });
  }

  /**
   * Split the date into month and year
   */
  function setChangeEventOnExpirationDate() {
    document.getElementById('id-card-expiration').addEventListener('change', function (event) {
      var cardExpirationDate = document.getElementById('id-card-expiration').value;
      var cardExpirationMonth = cardExpirationDate.split('/')[0] | ' ';
      var cardExpirationYear = cardExpirationDate.split('/')[1] | ' ';
      document.getElementById('id-card-expiration-month').value = ('0' + cardExpirationMonth).slice(-2);
      document.getElementById('id-card-expiration-year').value = cardExpirationYear;
    });
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
   * Set cvv length
   *
   * @param {number} length
   */
  function setCvvLength(length) {
    cvvLength = length;
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
    document.getElementById('id-card-expiration').value = '';
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
   * Clears card number input on keyup when there's less than 4 digits
   *
   */
  function setChangeEventOnCardNumber() {
    document.getElementById('id-card-number').addEventListener('keyup', function (e) {
      if (e.target.value.length <= 4) {
        clearInputs();
      }
    });
  }

  /**
   * Show errors
   *
   * @param  {object}  error
   */
  function showErrors(error) {
    var form = getFormCustom();
    var serializedError = error.cause || error;

    for (var x = 0; x < serializedError.length; x++) {
      var code = serializedError[x].code;
      var span = undefined;

      if (code === '208' || code === '209' || code === '325' || code === '326') {
        span = form.querySelector('#mp-error-208');
      } else {
        span = form.querySelector('#mp-error-' + code);
      }

      if (span !== undefined) {
        span.style.display = 'block';
        form.querySelector(span.getAttribute('data-main')).classList.add('mp-form-control-error');
      }
    }

    focusInputError();
    getConditionTerms();
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
   * Validate CVV length
   *
   * @returns {boolean}
   */
  function validateCvv() {
    var span = getFormCustom().querySelectorAll('small[data-main="#id-security-code"]');
    var cvvInput = document.getElementById('id-security-code');
    var cvvValidation = cvvLength === cvvInput.value.length;

    if (!cvvValidation) {
      span[0].style.display = 'block';
      cvvInput.classList.add('mp-form-control-error');
      cvvInput.focus();
      getConditionTerms();
    }

    return cvvValidation;
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
    var fixedInputs = ['cardNumber', 'cardholderName', 'cardExpiration', 'securityCode', 'installments'];

    for (var x = 0; x < formInputs.length; x++) {
      var element = formInputs[x];

      // Check is a input to create token.
      if (fixedInputs.indexOf(element.getAttribute('data-checkout')) > -1) {
        if (element.value === -1 || element.value === '') {
          var span = form.querySelectorAll('small[data-main="#' + element.id + '"]');

          if (span.length > 0) {
            span[0].style.display = 'block';
          }

          element.classList.add('mp-form-control-error');
          emptyInputs = true;
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
      if (inputCardholderName.value === -1 || inputCardholderName.value === '') {
        inputCardholderName.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      var inputDocType = document.getElementById('id-docType');
      if (inputDocType.value === -1 || inputDocType.value === '') {
        inputDocType.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      var docNumber = document.getElementById('id-doc-number');
      if (docNumber.value === -1 || docNumber.value === '') {
        docNumber.classList.add('mp-form-control-error');
        document.getElementById('mp-error-324').style.display = 'inline-block';
        emptyInputs = true;
      }
    }

    return emptyInputs;
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
    if (!validateCvv()) {
      return;
    }

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
