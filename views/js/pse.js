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

function someFieldElementIsEmpty({ personType, documentType, documentNumber, financialInstitution }) {
    return !fieldElementIsValid(personType.field)
        || !fieldElementIsValid(documentType.field)
        || !fieldElementIsValid(documentNumber.field)
        || !fieldElementIsValid(financialInstitution.field);
}

function fieldElementIsValid(fieldElement) {
    return !!fieldElement.value;
}

function toggleErrorAttributesByField(fieldElement, fieldErrorElement, isValid) {
    if (isValid) {
        fieldElement.classList.remove('mp-form-control-error');
        fieldErrorElement.style.display = 'none';
        return;
    }

    fieldElement.classList.add('mp-form-control-error');
    fieldErrorElement.style.display = 'inline-block';
}

function getPseFormFields() {
    return {
        personType: {
            field: document.getElementById('mp_pse_person_type'),
            error: document.getElementById('mp_pse_person_type_error'),
        },
        documentType: {
            field: document.getElementById('mp_pse_document_type'),
            error: document.getElementById('mp_pse_document_type_error'),
        },
        documentNumber: {
            field: document.getElementById('mp_pse_document_number'),
            error: document.getElementById('mp_pse_document_number_error'),
        },
        financialInstitution: {
            field: document.getElementById('mp_pse_bank'),
            error: document.getElementById('mp_pse_bank_error'),
        },
    }
}

function documentIsValid(validationParams, documentNumber) {
    if (!validationParams || !documentNumber) return false;

    if (documentNumber.length < validationParams.minLength) return false;
    if (documentNumber.length > validationParams.maxLength) return false;
    if (isNaN(Number(documentNumber)) && validationParams.type === 'number') return false;

    return true;
}

function getDocumentNumberValidationParams(selectedDocument) {
    if (!selectedDocument) return;

    const validationParams = selectedDocument.dataset;

    return {
        minLength: validationParams.minlength,
        maxLength: validationParams.maxlength,
        type: validationParams.type,
    }
}

function uncheckConditionTerms() {
    const conditionTermsCheckbox = document.getElementById('conditions_to_approve[terms-and-conditions]');

    if (!conditionTermsCheckbox) return;

    conditionTermsCheckbox.checked = false;
}

function disableFinishOrderButton() {
    const finishOrderButton = document.querySelector('#payment-confirmation button');

    finishOrderButton.setAttribute('disabled', 'disabled');
}

function validatePseCheckout() {
    waitForElement('#payment-confirmation').then(() => {
        const pseForm = document.getElementById('mp_pse_checkout');

        pseForm.onsubmit = () => {
            const pseRadioInput = document.getElementById('mp_pse_checkout').parentNode.previousElementSibling.querySelector('input');
            const pseIsSelected = pseRadioInput.checked;

            if (!pseIsSelected) return;

            const formFields = getPseFormFields();
            const documentNumber = formFields.documentNumber.field.value;
            const documentTypeSelected = formFields.documentType.field.value;
            const documentValidationParams = documentTypeSelected ? getDocumentNumberValidationParams(
                formFields.documentType.field.querySelector(`[value=${documentTypeSelected}]`)
            ) : null;

            toggleErrorAttributesByField(
                formFields.personType.field,
                formFields.personType.error,
                fieldElementIsValid(formFields.personType.field)
            );
            toggleErrorAttributesByField(
                formFields.documentType.field,
                formFields.documentType.error,
                fieldElementIsValid(formFields.documentType.field)
            );
            toggleErrorAttributesByField(
                formFields.documentNumber.field,
                formFields.documentNumber.error,
                fieldElementIsValid(formFields.documentNumber.field) && documentIsValid(documentValidationParams, documentNumber)
            );
            toggleErrorAttributesByField(
                formFields.financialInstitution.field,
                formFields.financialInstitution.error,
                fieldElementIsValid(formFields.financialInstitution.field)
            );

            if (someFieldElementIsEmpty(formFields) || !documentIsValid(documentValidationParams, documentNumber)) {
                disableFinishOrderButton();
                uncheckConditionTerms();
                return false;
            }

            pseForm.submit();
        }
    })
}

validatePseCheckout();
