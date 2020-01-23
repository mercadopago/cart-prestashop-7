/*
 * 
 * 2007-2019 PrestaShop
 *
 * Payment module for integration with Mercado Pago
 *
 * Payment by Credit Card and Debit
 * Checkout Custom
 *
 */

var objPaymentMethod = {};
var additionalInfoNeeded = {};
var seller = {
  site_id: ""
};

var translate = {
  select_choose: ""
};

$("input[data-checkout='cardNumber'], input[name='card-types']").on('focusout', guessingPaymentMethod);

/*
 * Initialise vars to use on JS custom-card.js
 * 
 * @param object customVars 
 */
function initialiseCustom(custom) {
  seller.site_id = custom.site_id;
  translate.select_choose = custom.select_choose;
}

/*
 * Execute before event focusout on input Card Number
 * 
 * @param object event 
 */
function guessingPaymentMethod(event) {
  clearIssuer();
  clearInstallments();
  clearTax();
  clearDoc();

  var bin = getBin();

  if (bin.length < 6) {
    resetBackgroundCard();
    return;
  }

  if (event.type == "keyup") {
    if (bin.length >= 6) {
      Mercadopago.getPaymentMethod({
        "bin": bin
      }, paymentMethodHandler);
    }
  } else {
    setTimeout(function() {
      if (bin.length >= 6) {
        Mercadopago.getPaymentMethod({
          "bin": bin
        }, paymentMethodHandler);
      }
    }, 100);
  }
}

/**
 * Handle payment Method response
 * 
 * @param number status 
 * @param object response 
 */
function paymentMethodHandler(status, response) {
  if (status == 200) {
    objPaymentMethod = response[0];
    setPaymentMethodId(objPaymentMethod.id);
    setImageCard(objPaymentMethod.secure_thumbnail);
    loadAdditionalInfo(objPaymentMethod.additional_info_needed);
    additionalInfoHandler();
  } else {
    document.getElementById('id-card-number').innerHTML = '';
  }
}

/**
 * Set value on paymentMethodId element
 * 
 * @param string paymentMethodId 
 */
function setPaymentMethodId(paymentMethodId) {
  var paymentMethodElement = document.getElementById('payment_method_id');
  paymentMethodElement.value = paymentMethodId;
}

/**
 * Set Imagem card on element
 * 
 * @param string secureThumbnail 
 */
function setImageCard(secureThumbnail) {
  document.getElementById('id-card-number').style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
}

/**
 * 
 * Load Additional Info to use for build payment form
 * 
 * @param array additional_info_needed 
 */
function loadAdditionalInfo(additional_info_needed) {
  additionalInfoNeeded = {
    'issuer': false,
    'cardholder_name': false,
    'cardholder_identification_type': false,
    'cardholder_identification_number': false
  }

  for (var i = 0; i < additional_info_needed.length; i++) {
    if (additional_info_needed[i] == 'issuer_id') {
      additionalInfoNeeded.issuer = true;
    }
    if (additional_info_needed[i] == 'cardholder_name') {
      additionalInfoNeeded.cardholder_name = true;
    }
    if (additional_info_needed[i] == 'cardholder_identification_type') {
      additionalInfoNeeded.cardholder_identification_type = true;
    }
    if (additional_info_needed[i] == 'cardholder_identification_number') {
      additionalInfoNeeded.cardholder_identification_number = true;
    }
  }
}

/**
 * Check what information is necessary to pay and show inputs 
 */
function additionalInfoHandler() {
  if (additionalInfoNeeded.issuer) {
    document.getElementById('container-issuers').style.display = 'block';
    document.getElementById('container-installments').classList.remove('col-md-12');
    document.getElementById('container-installments').classList.remove('pl-0');
    document.getElementById('container-installments').classList.add('col-md-8');
    Mercadopago.getIssuers(objPaymentMethod.id, issuersHandler);
  } else {
    clearIssuer();
    setInstallments();
  }

  if (additionalInfoNeeded.cardholder_identification_type) {
    document.getElementById('mp-doc-div-title').style.display = 'block';
    document.getElementById('mp-doc-div').style.display = 'block';
    document.getElementById('mp-doc-type-div').style.display = 'block';
    Mercadopago.getIdentificationTypes();
  } else {
    document.getElementById('mp-doc-type-div').style.display = 'none'
  }

  if (additionalInfoNeeded.cardholder_identification_number) {
    document.getElementById('mp-doc-div-title').style.display = 'block';
    document.getElementById('mp-doc-div').style.display = 'block';
    document.getElementById('mp-doc-number-div').style.display = 'block';
  } else {
    document.getElementById('mp-doc-number-div').style.display = 'none';
  }

  if (!additionalInfoNeeded.cardholder_identification_type &&
    !additionalInfoNeeded.cardholder_identification_number) {
    document.getElementById('mp-doc-div-title').style.display = 'none';
    document.getElementById('mp-doc-div').style.display = 'none';
  }
}

/**
 * Handle issuers response and build select
 * 
 * @param status status 
 * @param object response 
 */
function issuersHandler(status, response) {
  if (status == 200) {
    // If the API does not return any bank.
    var issuersSelector = document.getElementById('id-issuers-options');
    var fragment = document.createDocumentFragment();

    issuersSelector.options.length = 0;
    var option = new Option(translate.select_choose, "-1");
    fragment.appendChild(option);

    for (var i = 0; i < response.length; i++) {
      var name = response[i].name == 'default' ? 'Otro' : response[i].name;
      fragment.appendChild(new Option(name, response[i].id));
    }

    issuersSelector.appendChild(fragment);
    issuersSelector.removeAttribute("disabled");
    $('body').on('change', '#id-issuers-options', setInstallments);
  } else {
    clearIssuer();
  }
}

/**
 * Call insttalments with issuer ou not, depends on additionalInfoHandler()
 */
function setInstallments() {
  var params_installments = {};
  var amount = getAmount();
  var issuer = false;
  for (var i = 0; i < objPaymentMethod.additional_info_needed.length; i++) {
    if (objPaymentMethod.additional_info_needed[i] == 'issuer_id') {
      issuer = true;
    }
  }
  if (issuer) {
    var issuerId = document.getElementById('id-issuers-options').value;
    params_installments = {
      "bin": getBin(),
      "amount": amount,
      "issuer_id": issuerId
    }

    if (issuerId === "-1") {
      return;
    }
  } else {
    params_installments = {
      "bin": getBin(),
      "amount": amount
    }
  }
  Mercadopago.getInstallments(params_installments, installmentHandler);
}

/**
 * Get instalments
 * 
 * @param number status 
 * @param object response 
 */
function installmentHandler(status, response) {
  clearInstallments();
  if (status == 200) {
    var selectorInstallments = document.getElementById('id-installments');
    var html_option = new Option(translate.select_choose, "", true, true);
    $("#id-installments").append(html_option);

    var payerCosts = [];
    for (var i = 0; i < response.length; i++) {
      if (response[i].processing_mode == 'aggregator') {
        payerCosts = response[i].payer_costs;
      }
    }

    for (var e = 0; e < payerCosts.length; e++) {
      html_option = new Option((payerCosts[e].recommended_message || payerCosts[e].installments), payerCosts[e].installments);
      html_option.setAttribute('data-tax', argentinaResolution(payerCosts[e].labels));
      $("#id-installments").append(html_option);
    }

    if (seller.site_id == "MLA") {
      clearTax();
      $('body').on('change', '#id-installments', showTaxes);
    }
  } else {
    clearInstallments();
    clearTax();
  }
}

/**
 * Resolution 51/2017
 * 
 * @param * payerCosts 
 * @returns string
 */
function argentinaResolution(payerCosts) {
  var dataInput = '';
  if (seller.site_id == 'MLA') {
    for (var l = 0; l < payerCosts.length; l++) {
      if (payerCosts[l].indexOf('CFT_') !== -1) {
        dataInput = payerCosts[l];
      }
    }
    return dataInput;
  }
  return dataInput;
}

/**
 * Show taxes resolution 51/2017 for MLA
 */
function showTaxes() {
  var selectorInstallments = document.querySelector('#id-installments');
  var tax = selectorInstallments.options[selectorInstallments.selectedIndex].getAttribute("data-tax");
  var cft = "";
  var tea = "";
  if (tax != null) {
    var tax_split = tax.split("|");
    cft = tax_split[0].replace("_", " ");
    tea = tax_split[1].replace("_", " ");
    if (cft == "CFT 0,00%" && tea == "TEA 0,00%") {
      cft = "";
      tea = "";
    }
  }
  document.querySelector('.mp-text-cft').innerHTML = cft;
  document.querySelector('.mp-text-tea').innerHTML = tea;
}

/**
 * Get Amount end calculate discount for hide inputs
 */
function getAmount() {
  return document.getElementById('amount').value;
}

/**
 * Get Bin from Card Number
 */
function getBin() {
  var cardnumber = $("#id-card-number").val().replace(/ /g, '').replace(/-/g, '').replace(/\./g, '');
  return cardnumber.substr(0, 6);
}

/**
 * Remove background image from imput
 */
function resetBackgroundCard() {
  document.getElementById('id-card-number').style.background = 'no-repeat #fff';
}

/**
 * Clear input select
 */
function clearInstallments() {
  document.getElementById('id-installments').innerHTML = '';
}

/**
 * Clear Tax
 */
function clearTax() {
  document.querySelector('.mp-text-cft').innerHTML = '';
  document.querySelector('.mp-text-tea').innerHTML = '';
}

/**
 * Clear input select and change to default layout
 */
function clearIssuer() {
  document.getElementById('container-issuers').style.display = 'none';
  document.getElementById('container-installments').classList.remove('col-md-8');
  document.getElementById('container-installments').classList.add('mp-md-12');
  document.getElementById('container-installments').classList.add('pl-0');
  document.getElementById('id-issuers-options').innerHTML = '';
}

/**
 * Clear input select and change to default layout
 */
function clearDoc() {
  document.getElementById('mp-doc-div-title').style.display = 'none';
  document.getElementById('mp-doc-div').style.display = 'none';
  document.getElementById('mp-doc-type-div').style.display = 'none';
  document.getElementById('mp-doc-number-div').style.display = 'none';
  document.getElementById('id-docType').innerHTML = '';
  document.getElementById('id-doc-number').value = '';
}

if (document.forms['mp_custom_checkout'] != undefined) {
  document.forms['mp_custom_checkout'].onsubmit = function() {

    if (validateInputsCreateToken()) {
      return createToken();
    }

    return false;
  }
}

/** 
 * Validate Inputs to Create Token
 * 
 * @return bool
 */
function validateInputsCreateToken() {
  var form_inputs = getForm().querySelectorAll("[data-checkout]");
  var fixed_inputs = [
    'cardNumber',
    'cardExpiration',
    'securityCode',
    'installments'
  ];

  for (var x = 0; x < form_inputs.length; x++) {
    var element = form_inputs[x];
    // Check is a input to create token.
    if (fixed_inputs.indexOf(element.getAttribute("data-checkout")) > -1) {
      if (element.value == -1 || element.value == "") {
        element.focus();
        return false;
      }
    }
  }

  if (objPaymentMethod.length == 0) {
    document.getElementById('id-card-number').focus();
    return false;
  }

  if (!validateAdditionalInputs()) {
    return false;
  }

  return true;
}

/**
 * Validate Additional Inputs
 * 
 * @return bool
 */
function validateAdditionalInputs() {
  if (additionalInfoNeeded.issuer) {
    var inputMpIssuer = document.getElementById('id-issuers-options');
    if (inputMpIssuer.value == -1 || inputMpIssuer.value == "") {
      inputMpIssuer.focus();
      return false;
    }
  }
  if (additionalInfoNeeded.cardholder_name) {
    var inputCardholderName = document.getElementById('id-card-holder-name');
    if (inputCardholderName.value == -1 || inputCardholderName.value == "") {
      inputCardholderName.focus();
      return false;
    }
  }
  if (additionalInfoNeeded.cardholder_identification_type) {
    var inputDocType = document.getElementById('id-docType');
    if (inputDocType.value == -1 || inputDocType.value == "") {
      docType.focus();
      return false;
    }
  }
  if (additionalInfoNeeded.cardholder_identification_number) {
    var docNumber = document.getElementById('id-doc-number');
    if (docNumber.value == -1 || docNumber.value == "") {
      docNumber.focus();
      return false;
    }
  }
  return true;
}

/**
 *  Create Token call Mercadopago.createToken
 * 
 *  @return bool 
 */
function createToken() {
  hideErrors();

  // Form.
  var form = getForm();

  Mercadopago.createToken(form, sdkResponseHandler);

  return false;
}

/**
 * Handler Response of Mercadopago.createToken
 * 
 * @param number status 
 * @param object response 
 */
function sdkResponseHandler(status, response) {
  if (status != 200 && status != 201) {
    showErrors(response);
  } else {
    var token = document.querySelector('#card_token_id');
    token.value = response.id;
    mercado_pago_submit = true;
    document.forms['mp_custom_checkout'].submit();
  }
}

/**
 * 
 * @param  obje  response
 */
function showErrors(response) {
  var form = getForm();
  for (var x = 0; x < response.cause.length; x++) {
    var error = response.cause[x];
    var small = '';
    if (error.code == 208 || error.code == 209 || error.code == 325 || error.code == 326) {
      small = form.querySelector("#mp-error-208");
    } else {
      small = form.querySelector("#mp-error-" + error.code);
    }

    if (small != undefined) {
      small.style.display = "block";
    }
  }
  return;
}

/**
 * Hide errors when return of cardToken error
 */
function hideErrors() {
  for (var x = 0; x < document.querySelectorAll(".mp-erro-form").length; x++) {
    var small = document.querySelectorAll(".mp-erro-form")[x];
    small.style.display = "none";
  }
}

/**
 * Get form
 */
function getForm() {
  return document.querySelector('#mp_custom_checkout');
}