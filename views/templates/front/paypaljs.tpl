{*
 * Copyright (C) 2017 thirty bees
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
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<script async defer type="text/javascript" src="//www.paypalobjects.com/api/checkout.js"></script>
<script type="text/javascript">
  (function () {
    function addEventListener(el, eventName, handler) {
      if (typeof el !== 'object' || !el) {
        return;
      }

      if (el.addEventListener) {
        el.addEventListener(eventName, handler);
      } else {
        el.attachEvent('on' + eventName, function(){
          handler.call(el);
        });
      }
    }

    function initPayPalJs() {
      if (typeof document.getElementById('payment_paypal_express_checkout') === 'undefined'
        || typeof window.paypal === 'undefined'
        || typeof window.paypal.checkout === 'undefined'
      ) {
        setTimeout(initPayPalJs, 100);
        return;
      }

      function updateFormDatas() {
        var nb = document.getElementById('quantity_wanted').value;
        var id = document.getElementById('idCombination').value;

        document.querySelectorAll('#paypal_payment_form input[name=quantity]').forEach(function (elem) {
          elem.value = nb;
        });
        document.querySelectorAll('#paypal_payment_form input[name=id_product_attribute]').forEach(function (elem) {
          elem.value = id;
        });
      }

      addEventListener(document.getElementById('paypal_payment_form'), 'submit', updateFormDatas);

      // Empty the express checkout container
      var containerExpressCheckout = document.getElementById('container_express_checkout');
      if (containerExpressCheckout) {
        while (containerExpressCheckout.firstChild) {
          containerExpressCheckout.removeChild(containerExpressCheckout.firstChild);
        }
      } else {
        // No PayPal express checkout container found, so exiting
        return;
      }

      paypal.Button.render({
        env: {if $PAYPAL_LIVE}'production'{else}'sandbox'{/if}, // Optional: specify 'sandbox' environment
        locale: '{$paypal_locale|escape:'javascript':'UTF-8'}',
        payment: function (resolve, reject) {
          {if $incontextType == 'product'}
          // Prepare the cart first
          var idProduct = parseInt(document.querySelector('input[name="id_product"]').value, 10);
          var idProductAttribute = parseInt(document.querySelector('input[name="id_product_attribute"]').value, 10);
          if (isNaN(idProductAttribute)) {
            // Not a page with attributes
            idProductAttribute = 0;
          }
          if (isNaN(idProduct)) {
            document.getElementById('container_express_checkout').style.display = 'none';

            reject('Not available');
          }
          $.ajax({
            type: 'GET',
            url: '{$link->getModuleLink('paypal', 'incontextajax', [], true)|escape:'javascript':'UTF-8'}',
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
          {/if}
                // Then create a payment
                paypal.request.post('{$link->getModuleLink('paypal', 'incontextajax', [], true)|escape:'javascript':'UTF-8'}', {
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
          {if $incontextType == 'product'}
              } else {
                reject('Couldn\'t update cart');
              }
            },
            error: function () {
              reject('Couldn\'t update cart');
            }
          });
          {/if}
        },
        onAuthorize: function (data) {
          var EXECUTE_PAYMENT_URL = '{$link->getModuleLink('paypal', 'incontextvalidate', [], true)|escape:'javascript':'UTF-8'}';
          paypal.request.post(EXECUTE_PAYMENT_URL, {
            paymentId: data.paymentID, // paymentID should be spelled like this
            PayerID: data.payerID      // payerID should be spelled like this
          })
            .then(function (data) {
              if (data.success) {
                window.location.replace(data.confirmUrl);
                return;
              }
            })
            .catch(function (err) {
              alert('{l s='Payment failed. Please try again or contact our customer service if the problem persists. Our apologies for the inconvience.' mod='paypal' js=1}');
            });
        }

      }, '#container_express_checkout');
    }

    initPayPalJs();
  })();
</script>
