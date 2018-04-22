{*
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
 *}
<div id="paypal-express-checkout-product-{$idButton|escape:'html'}"
     data-id-product="{$idProduct|escape:'html'}"
     data-id-product-attribute="{$idProductAttribute|escape:'html'}"
     data-id-product-path="{$idProductPath|escape:'html'}"
     data-id-product-attribute-path="{$idProductAttributePath|escape:'html'}"
     data-callback="{$callback|escape:'html'}"
></div>
<script type="text/javascript">
  (function () {
    var checkoutJs, found, target;
    target = document.getElementById('paypal-express-checkout-product-{$idButton|escape:'javascript'}');

    function getProperty(propertyName, object) {
      var parts = propertyName.split('.'),
        length = parts.length,
        i,
        property = object || this;

      for (i = 0; i < length; i++) {
        if (typeof property[parts[i]] !== 'undefined') {
          property = property[parts[i]];
        } else {
          return null;
        }
      }

      return property;
    }

    function initPayPalJs() {
      if (typeof window.paypal === 'undefined'
        || typeof window.paypal.checkout === 'undefined'
      ) {

        setTimeout(initPayPalJs, 100);
        return;
      }

      paypal.Button.render({
        env: {if $PAYPAL_LIVE}'production'{else}'sandbox'{/if}, // Optional: specify 'sandbox' environment
        locale: '{$paypal_locale|escape:'javascript'}',
        payment: function (resolve, reject) {
          // Prepare the cart first
          var idProduct = parseInt(target.getAttribute('data-id-product'), 10);
          var idProductAttribute = parseInt(target.getAttribute('data-id-product-attribute'), 10);
          if (isNaN(idProduct) || isNaN(idProductAttribute)) {
            target.style.display = 'none';

            reject('Not available');
          }

          $.ajax({
            type: 'GET',
            url: '{$link->getModuleLink('paypal', 'incontextajax', [], true)|escape:'javascript'}',
            data: {
              updateCart: true,
              idProduct: idProduct,
              idProductAttribute: idProductAttribute,
            },
            cache: false,
            success: function (result) {
              if (result && result.success) {
                // Update cart display
                if (ajaxCart && typeof ajaxCart.refresh === 'function') {
                  ajaxCart.refresh();
                }
                // Then create a payment
                paypal.request.post('{$link->getModuleLink('paypal', 'incontextajax', [], true)|escape:'javascript'}', {
                  requestForInContext: true,
                })
                  .then(function (data) {
                    if (!data || !data.paymentId) {
                      reject('Could not initialize payment');
                    } else {
                      resolve(data.paymentId);
                    }
                  })
                  .catch(function (err) {
                    reject(err);
                  });
              } else {
                reject('Couldn\'t update cart');
              }
            },
            error: function () {
              reject('Couldn\'t update cart');
            }
          });
        },
        onAuthorize: function (data, actions) {
          var EXECUTE_PAYMENT_URL = '{$link->getModuleLink('paypal', 'incontextvalidate', [], true)|escape:'javascript'}';
          paypal.request.post(EXECUTE_PAYMENT_URL, {
            paymentId: data.paymentID, // paymentID should be spelled like this
            PayerID: data.payerID      // payerID should be spelled like this
          })
            .then(function (data) {
              if (data.success) {
                return actions.redirect();
              }
            })
            .catch(function (err) {
              alert('{l s='Payment failed. Please try again or contact our customer service if the problem persists. Our apologies for the inconvience.' mod='paypal' js=1}');
            });
        }

      }, target);
    }

    checkoutJs = '//www.paypalobjects.com/api/checkout.js';
    found = false;
    document.querySelectorAll('script').forEach(function (script) {
      if (script.src === checkoutJs) {
        return false;
      }
    });
    if (!found) {
      var newSrc = document.createElement('script');
      newSrc.type = 'text/javascript';
      newSrc.src = checkoutJs;
      document.head.appendChild(newSrc);
    }

    initPayPalJs();
  })();
</script>
