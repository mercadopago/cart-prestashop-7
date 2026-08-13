/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

var vObj, vFun;

// input mask
// eslint-disable-next-line no-unused-vars
function maskInput (o, f) {
  vObj = o;
  vFun = f;
  setTimeout(execmascara(), 1);
}

// eslint-disable-next-line no-unused-vars
function execmascara () {
  vObj.value = vFun(vObj.value);
}

// eslint-disable-next-line no-unused-vars
function mdate (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/(\d{2})(\d)/, '$1/$2');
  v = v.replace(/(\d{2})(\d{2})$/, '$1$2');
  return v;
}

// eslint-disable-next-line no-unused-vars
function minteger (v) {
  return v.replace(/\D/g, '');
}

// eslint-disable-next-line no-unused-vars
function mcc (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/^(\d{4})(\d)/g, '$1 $2');
  v = v.replace(/^(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3');
  v = v.replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3 $4');
  return v;
}

// eslint-disable-next-line no-unused-vars
function mcpf (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/(\d{3})(\d)/, '$1.$2');
  v = v.replace(/(\d{3})(\d)/, '$1.$2');
  v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  return v;
}

// eslint-disable-next-line no-unused-vars
function mcnpj (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/^(\d{2})(\d)/, '$1.$2');
  v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
  v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
  v = v.replace(/(\d{4})(\d)/, '$1-$2');
  return v;
}

function waitForElement(selector) {
  return new Promise(resolve => {
      if (document.querySelector(selector)) {
          return resolve(document.querySelector(selector));
      }

      const observer = new MutationObserver(() => {
          if (document.querySelector(selector)) {
              observer.disconnect();
              resolve(document.querySelector(selector));
          }
      });

      observer.observe(document.body, {
          childList: true,
          subtree: true
      });
  });
}
