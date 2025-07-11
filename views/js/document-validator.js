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
 */

// CPF document validation

/**
 * Validates a CPF number by checking its format and calculating check digits
 * @param {string} cpfNumber - CPF number to validate
 * @returns {boolean} - Returns true if CPF is valid, false otherwise
 */
function validateCPF(cpfNumber) {
    // Clean the CPF number by removing special characters
    const cleanCPF = cpfNumber.replace(/[.-\s]/g, '');

    // Validate if CPF has exactly 11 digits
    if (cleanCPF.length !== 11) {
        return false;
    }

    // Validate if CPF has all repeated numbers
    if (/^(\d)\1{10}$/.test(cleanCPF)) {
        return false;
    }

    let sum = 0;
    let firstDigit;

    // First check digit validation
    for (let i = 1; i <= 9; i++) {
        sum += parseInt(cleanCPF.substring(i - 1, i)) * (11 - i);
    }

    firstDigit = (sum * 10) % 11;
    firstDigit = (firstDigit === 10 || firstDigit === 11) ? 0 : firstDigit;

    if (firstDigit !== parseInt(cleanCPF.substring(9, 10))) {
        return false;
    }

    // Second check digit validation
    sum = 0;
    for (let i = 1; i <= 10; i++) {
        sum += parseInt(cleanCPF.substring(i - 1, i)) * (12 - i);
    }

    let secondDigit = (sum * 10) % 11;
    secondDigit = (secondDigit === 10 || secondDigit === 11) ? 0 : secondDigit;

    if (secondDigit !== parseInt(cleanCPF.substring(10, 11))) {
        return false;
    }

    return true;
}


/**
 * Validates if CNPJ is valid checking its format and verifying digits
 * @param {string} strCNPJ - CNPJ number to validate
 * @returns {boolean} - Returns true if CNPJ is valid, false otherwise
 */
function validateCNPJ(strCNPJ) {
    // Remove non-numeric characters
    strCNPJ = strCNPJ.replace(/[^\d]+/g, '');


    if (strCNPJ.length !== 14) {
        return false;
    }

    // Validation for repeated numbers using regex
    if (/^(\d)\1{13}$/.test(strCNPJ)) {
        return false;
    }

    let length = strCNPJ.length - 2;
    let numbers = strCNPJ.substring(0, length);
    let digits = strCNPJ.substring(length);
    let sum = 0;
    let multiplier = length - 7;

    for (let i = length; i >= 1; i--) {
        sum += numbers.charAt(length - i) * multiplier--;
        if (multiplier < 2) {
            multiplier = 9;
        }
    }

    let firstDigit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (firstDigit !== parseInt(digits.charAt(0), 10)) {
        return false;
    }

    length = length + 1;
    numbers = strCNPJ.substring(0, length);
    sum = 0;
    multiplier = length - 7;

    for (let i = length; i >= 1; i--) {
        sum += numbers.charAt(length - i) * multiplier--;
        if (multiplier < 2) {
            multiplier = 9;
        }
    }

    let secondDigit = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (secondDigit.toString() !== digits.charAt(1)) {
        return false;
    }

    return true;
}

/**
 * Validate Document number for MLB
 * @param {string} docnumber 
 * @param {string} docType - 'CPF' or 'CNPJ'
 * @return {bool} 
 */
function validateDocument(docnumber, docType) {
    if (docType === 'CPF') {
        return validateCPF(docnumber);
    } else if (docType === 'CNPJ') {
        return validateCNPJ(docnumber);
    }
    return false;
} 