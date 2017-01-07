{*
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<script type="text/javascript">
	(function () {
		function initPayPalJs() {
			if (typeof $ === 'undefined' || !$('#payment_paypal_express_checkout').length{if $use_paypal_in_context} || typeof window.paypal  === 'undefined' || typeof window.paypal.checkout === 'undefined'{/if}) {
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

			{if $use_paypal_in_context}
				$('#container_express_checkout').empty();
				paypal.Button.render({
					env: {if $PAYPAL_SANDBOX}'sandbox'{else}'production'{/if}, // Optional: specify 'sandbox' environment
					payment: function(resolve, reject) {
						var CREATE_PAYMENT_URL = '{Context::getContext()->link->getModuleLink('paypal', 'incontextcheckoutajax', [], Tools::usingSecureMode())|escape:'javascript':'UTF-8'}';
						paypal.request.post(CREATE_PAYMENT_URL, {
							requestForInContext: true,
						})
						.then(function(data) { resolve(data.paymentID); })
						.catch(function(err) { reject(err); });
					},
					onAuthorize: function(data) {
						// Note: you can display a confirmation page before executing

						var EXECUTE_PAYMENT_URL = '{Context::getContext()->link->getModuleLink('paypal', 'incontextsubmit', [], Tools::usingSecureMode())|escape:'javascript':'UTF-8'}';
						paypal.request.post(EXECUTE_PAYMENT_URL, {
							paymentID: data.paymentID,
							payerID: data.payerID
						})
						.then(function(data) {
							if (data.success) {
								window.location.replace('{Context::getContext()->link->getModuleLink('paypal', 'confirm', [], Tools::usingSecureMode())|escape:'javascript':'UTF-8'}');
								return;
							} else {
								alert('fail');
							}
						})
						.catch(function(err) {
							alert('Payment failure');
						});
					}

				}, '#container_express_checkout');
			{/if}

			{if !$use_paypal_in_context}
			$('#payment_paypal_express_checkout').click(function () {
				$('#paypal_payment_form').submit();
				return false;
			});
			{/if}

			function displayExpressCheckoutShortcut() {
				var id_product = $('input[name="id_product"]').val();
				var id_product_attribute = $('input[name="id_product_attribute"]').val();
				$.ajax({
					type: 'GET',
					url: '{Context::getContext()->link->getModuleLink('paypal', 'expresscheckoutajax', [], Tools::usingSecureMode())|escape:'javascript':'UTF-8'}',
					data: {
						get_qty: '1',
						id_product: id_product,
						id_product_attribute: id_product_attribute,
					},
					cache: false,
					success: function (result) {
						if (result == '1') {
							$('#container_express_checkout').slideDown();
						} else {
							$('#container_express_checkout').slideUp();
						}

						return true;
					}
				});
			}

			$('select[name^="group_"]').change(function () {
				setTimeout(function () {
					displayExpressCheckoutShortcut()
				}, 500);
			});

			$('.color_pick').click(function () {
				setTimeout(function () {
					displayExpressCheckoutShortcut()
				}, 500);
			});

			if ($('body#product').length > 0)
				setTimeout(function () {
					displayExpressCheckoutShortcut()
				}, 500);


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
				if ($('select[name^="group_"]').length > 0) {
					displayExpressCheckoutShortcut();
				}

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
					'{Context::getContext()->link->getModuleLink('paypal', 'confirm')|escape:'javascript':'UTF-8'}',
					{
						id_cart: '{$id_cart|intval}'
					},
					function (data) {
						if ((typeof(data) != 'undefined') && (data > 0)) {
							clearInterval(confirmTimer);
							window.location.replace('{Context::getContext()->link->getModuleLink('paypal', 'submit', [], Tools::usingSecureMode())|escape:'javascript':'UTF-8'}?id_cart={$id_cart|intval}');
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
