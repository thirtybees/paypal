/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else if (document.addEventListener) {
      window.addEventListener('DOMContentLoaded', fn);
    } else {
      document.attachEvent('onreadystatechange', function () {
        if (document.readyState !== 'loading')
          fn();
      });
    }
  }

  function inputChanged() {
    var express = document.querySelector('[name=PAYPAL_EC_ENABLED]');
    if (express == null) {
      return;
    }
    express = express.checked;

    var selectVisibility = {
      PAYPAL_EC_ENABLED: express,
      PAYPAL_EC_CREDIT: express && document.querySelector('[name=PAYPAL_EC_CREDIT]').checked,
      PAYPAL_EC_CARDS: express && document.querySelector('[name=PAYPAL_EC_CARDS]').checked,
      PAYPAL_EC_SEPA: express && document.querySelector('[name=PAYPAL_EC_SEPA]').checked,
      PAYPAL_LOGIN_TPL: document.querySelector('[name=PAYPAL_LOGIN_ENABLED]').checked,
      PAYPAL_EC_SHAPE: express,
      PAYPAL_EC_COLOR: express,
    };

    Object.keys(selectVisibility).forEach(function (targetSelector) {
      try {
        var elem = document.querySelector('[name=' + targetSelector + ']');
        if (targetSelector !== 'PAYPAL_EC_ENABLED') {
          elem.disabled = !selectVisibility['PAYPAL_EC_ENABLED'];
        }
        if (['PAYPAL_LOGIN_TPL', 'PAYPAL_EC_SHAPE', 'PAYPAL_EC_COLOR'].indexOf(targetSelector) < 0) {
          var image = elem.parentNode.parentNode.querySelector('label > img');
          image.style.filter = 'grayscale(' + (selectVisibility[targetSelector] ? '0' : '100') + '%)';
          image.style.WebkitFilter = 'grayscale(' + (selectVisibility[targetSelector] ? '0' : '100') + '%)';
          image.style.opacity = selectVisibility[targetSelector] ? 1 : 0.6;
        }
      } catch (e) {
      }
    });
    [].slice.call(document.querySelectorAll('input[name=PAYPAL_LOGIN_TPL], input[name=PAYPAL_EC_SHAPE], input[name=PAYPAL_EC_COLOR]')).forEach(function (elem) {
      var enabled = selectVisibility[elem.name];
      elem.disabled = !enabled;
      elem.parentNode.querySelector('img').style.filter = 'grayscale(' + (enabled ? '0' : '100') + '%)';
      elem.parentNode.querySelector('img').style.WebkitFilter = 'grayscale(' + (enabled ? '0' : '100') + '%)';
      elem.parentNode.querySelector('img').style.opacity = enabled ? 1 : 0.6;
    });
  }
  
  function bindInputs() {
    [
      'PAYPAL_EC_ENABLED',
      'PAYPAL_EC_CREDIT',
      'PAYPAL_EC_CARDS',
      'PAYPAL_EC_SEPA',
      'PAYPAL_LOGIN_ENABLED',
    ].forEach(function (item) {
      [].slice.call(document.querySelectorAll('[name=' + item + ']')).forEach(function (elem) {
        elem.addEventListener('change', inputChanged);
      });
    });
  }

  ready(function () {
    bindInputs();
    inputChanged();
  });
}());
