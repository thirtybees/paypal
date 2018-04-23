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
     data-minimal-quantities="{$minimalQuantities|escape:'html'}"
     data-layout="{$layout|escape:'html'}"
     data-label="{$label|escape:'html'}"
     data-size="{$size|escape:'html'}"
     data-shape="{$shape|escape:'html'}"
     data-color="{$color|escape:'html'}"
     data-funding-allowed="{$fundingAllowed|json_encode|escape:'html'}"
     data-funding-disallowed="{$fundingDisallowed|json_encode|escape:'html'}"
     style="width: 100%"
></div>
<script type="text/javascript">
  (function () {
    var checkoutJs, found, target;
    target = document.getElementById('paypal-express-checkout-product-{$idButton|escape:'javascript'}');

    function getProperty(propertyName, object) {
      var parts, length, i, property;
      if (typeof propertyName.split !== 'function') {
        return null;
      }
      parts = propertyName.split('.');
      length = parts.length;
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

      var allowed = [];
      // Data attribute should be an array of accessible properties (strings using dot notation)
      if (!!target.getAttribute('data-funding-allowed')) {
        JSON.parse(target.getAttribute('data-funding-allowed')).forEach(function (item) {
          allowed.push(getProperty(item));
        });
      }
      var disallowed = [];
      // Data attribute should be an array of accessible properties (strings using dot notation)
      if (!!target.getAttribute('data-funding-disallowed')) {
        JSON.parse(target.getAttribute('data-funding-disallowed')).forEach(function (item) {
          disallowed.push(getProperty(item));
        });
      }
      console.log([allowed, disallowed]);
      var style = {
        layout: target.getAttribute('data-layout'), // vertical | horizontal
        size:  target.getAttribute('data-size'),    // small | medium | large | responsive
        shape: target.getAttribute('data-shape'),   // pill | rect
        color: target.getAttribute('data-color')    // gold | blue | silver | black
      };

      var label = target.getAttribute('data-label');
      if (label && style.layout !== 'vertical') {
        style.label = label;
      }

      paypal.Button.render({
        env: {if $live}'production'{else}'sandbox'{/if},
        style: style,
        locale: '{$locale|escape:'javascript'}',
        funding: {
          allowed: allowed,
          disallowed: disallowed,
        },
        client: {
          {if $live}production{else}sandbox{/if}: '{$clientId|escape:'javascript'}'
        },
        payment: function (resolve, reject) {
          var idProduct, idProductAttribute;
          // Prepare the cart first
          if (target.getAttribute('data-id-product-path')) {
            idProduct = getProperty(target.getAttribute('data-id-product-path'));
            if (typeof idProduct === 'function') {
              idProduct = idProduct();
            }
            idProduct = parseInt(idProduct, 10);
          } else {
            idProduct = parseInt(target.getAttribute('data-id-product'), 10);
          }
          if (target.getAttribute('data-id-product-path')) {
            idProduct = getProperty(target.getAttribute('data-id-product-path'));
            if (typeof idProduct === 'function') {
              idProduct = idProduct();
            }
            idProduct = parseInt(idProduct, 10);
          } else {
            idProductAttribute = parseInt(target.getAttribute('data-id-product-attribute'), 10);
          }
          if (isNaN(idProduct) || isNaN(idProductAttribute)) {
            target.style.display = 'none';
            reject('Product/combination not available');
            return;
          } else {
            target.style.display = 'block';
          }

          // Figure out the minimal quantities
          var minQuantities = target.getAttribute('data-minimal-quantities');
          try {
            minQuantities = JSON.parse(minQuantities);
          } catch (e) {
            minQuantities = null;
          }
          var minQuantity;
          if (minQuantities.prop && minQuantities.constructor === Array) {
            if (typeof minQuantities[idProductAttribute] !== 'undefined') {
              minQuantity = minQuantities[idProductAttribute];
            } else {
              reject('Min. quantity of selected product attribute is not available');
              return;
            }
          } else {
            minQuantity = minQuantities;
          }

          // Always override the quantity with the quantity_wanted input and check if wanted quantity is available
          var quantityWanted = document.getElementById('quantity_wanted');
          if (quantityWanted != null && quantityWanted.value != null) {
            if (quantityWanted < minQuantity) {
              reject('Selected quantity is no longer available');
            }
            minQuantity = quantityWanted.value;
          }
          if (!minQuantity) {
            reject('Min. quantity not found');
            return;
          }

          var request = new XMLHttpRequest();
          request.open('POST', '{$link->getModuleLink('paypal', 'incontextajax', [], true)|escape:'javascript'}', true);
          request.onreadystatechange = function() {
            if (this.readyState === 4) {
              if (this.status >= 200 && this.status < 400) {
                // Success!
                try {
                  var result = JSON.parse(this.responseText);
                } catch (e) {
                  reject(e);
                  return;
                }
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
                        reject('{l s='Could not initialize payment' mod='paypal' js=1}');
                      } else {
                        resolve(data.paymentId);
                      }
                    })
                    .catch(function (err) {
                      reject(err);
                    });

                } else {
                  reject('{l s='Couldn\'t update cart' mod='paypal' js=1}');
                }
              } else {
                reject('{l s='Couldn\'t update cart' mod='paypal' js=1}');
              }
            }
          };

          request.send(JSON.stringify({
            createCart: true,
            idProduct: parseInt(idProduct, 10),
            idProductAttribute: parseInt(idProductAttribute, 10),
            quantity: parseInt(minQuantity, 10),
          }));
          request = null;
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
            .catch(function () {
              alert('{l s='Payment failed. Please try again or contact our customer service if the problem persists. Our apologies for the inconvenience.' mod='paypal' js=1}');
            });
        }

      }, target);
    }

    checkoutJs = '{Tools::getShopProtocol()|escape:'javascript'}www.paypalobjects.com/api/checkout.js';
    found = false;
    document.querySelectorAll('script').forEach(function (script) {
      if (script.src === checkoutJs) {
        found = true;
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
