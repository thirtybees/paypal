{*
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<script type="text/javascript" data-cookieconsent="necessary">
  (function () {
    function initPayPalJs() {
      if (typeof $ === 'undefined'
        || !$('#payment_paypal_express_checkout').length
        || typeof window.paypal === 'undefined'
        || typeof window.paypal.checkout === 'undefined'
      ) {
        setTimeout(initPayPalJs, 100);
        return;
      }

      function updateFormDatas() {
        var nb = $('#quantity_wanted').val();
        var id = $('#idCombination').val();

        $('#paypal_payment_form input[name=quantity]').val(nb);
        $('#paypal_payment_form input[name=id_product_attribute]').val(id);
      }

      $('body').on('submit', "#paypal_payment_form", updateFormDatas);

      $('#container_express_checkout').empty();
      paypal.Button.render({
        env: {if $PAYPAL_LIVE}'production'{else}'sandbox'{/if}, // Optional: specify 'sandbox' environment
        locale: '{$paypal_locale|escape:'javascript':'UTF-8'}',
        payment: function (resolve, reject) {
            {if $incontextType == 'product'}
          // Prepare the cart first
          var idProduct = $('input[name="id_product"]').val();
          var idProductAttribute = $('input[name="id_product_attribute"]').val();
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
                    resolve(data.paymentID);
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
            paymentID: data.paymentID,
            payerID: data.payerID
          })
            .then(function (data) {
              if (data.success) {
                window.location.replace(data.confirmUrl);
                return;
              } else {
                alert('fail');
              }
            })
            .catch(function (err) {
              alert('Payment failure');
            });
        }

      }, '#container_express_checkout');

        {if isset($paypal_authorization)}
        /* 1.5 One page checkout*/
      var qty = $('.qty-field.cart_quantity_input').val();
      $('.qty-field.cart_quantity_input').after(qty);
      $('.qty-field.cart_quantity_input, .cart_total_bar, .cart_quantity_delete, #cart_voucher *').remove();

      var br = $('.cart > a').prev();
      br.prev().remove();
      br.remove();
      $('.cart.ui-content > a').remove();

      var gift_fieldset = $('#gift_div').prev();
      var gift_title = gift_fieldset.prev();
      $('#gift_div, #gift_mobile_div').remove();
      gift_fieldset.remove();
      gift_title.remove();
        {/if}

        {if isset($paypal_confirmation)}
      $('#container_express_checkout').hide();

      $('body').on('click', "#cgv", function () {
        if ($('#cgv:checked').length != 0) {
          $(location).attr('href', '{$paypal_confirmation|escape:'javascript':'UTF-8'}');
        }
      });



        {elseif isset($paypal_order_opc)}
      $('body').on('click', '#cgv', function () {
        if ($('#cgv:checked').length != 0) {
          checkOrder();
        }
      });
        {/if}

      var confirmTimer = false;

      if ($('form[target="hss_iframe"]').length == 0) {
        return false;
      } else {
        checkOrder();
      }

      function checkOrder() {
        if (confirmTimer == false) {
          confirmTimer = setInterval(getOrdersCount, 1000);
        }
      }

        {if isset($id_cart)}
      function getOrdersCount() {
        $.get(
          '{$link->getModuleLink('paypal', 'incontextconfirm')|escape:'javascript':'UTF-8'}',
          {
            id_cart: '{$id_cart|intval}'
          },
          function (data) {
            if ((typeof(data) != 'undefined') && (data > 0)) {
              clearInterval(confirmTimer);
              window.location.replace('{$link->getModuleLink('paypal', 'submit', [], true)|escape:'javascript':'UTF-8'}?id_cart={$id_cart|intval}');
              $('p.payment_module, p.cart_navigation').hide();
            }
          }
        );
      }
        {/if}
    }

    initPayPalJs();
  })();
</script>
