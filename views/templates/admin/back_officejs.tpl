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
		function initBackOfficeJs() {
			if (typeof $ === 'undefined') {
				setTimeout(initBackOfficeJs(), 100);
				return;
			}

			$(document).ready(function () {
				var identificationButtonClicked = false;

				/* Display correct block according to different choices. */
				function displayConfiguration() {
					identificationButtonClicked = false;

					var paypal_business = $('input[name="{PayPal::BUSINESS}"]:checked').val();
					var paypal_payment_method = $('input[name="{PayPal::PAYMENT_METHOD}"]:checked').val();
					var integral_evolution_solution = $('input[name="{PayPal::HSS_SOLUTION}"]:checked').val();
					$('#signup span.paypal-signup-content').hide();
					$('#signup .paypal-signup-button').hide();

					if (paypal_business) {
						$('#signup').slideDown();
						$('#account').removeClass('paypal-disabled');
						$('#credentials').addClass('paypal-disabled');
						$('input[type="submit"]').attr('disabled', 'disabled');

						switch (parseInt(paypal_payment_method, 10)) {
							case {PayPal::WPS|intval}:
								$('.toolbox').slideUp();
								$('#paypalplus-credentials').slideUp();
								$('#integral-credentials').slideUp();
								$('#standard-credentials').slideDown();
								$('#paypal-signup-button-u1').show();
								$('#paypal-signup-content-u1').show();
								$('#{PayPal::HSS_SOLUTION}').slideUp();
								$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
								$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideDown();
								break;
							case {PayPal::WPRH|intval}:
								$('#signup').slideDown();
								$('#paypalplus-credentials').slideUp();
								$('#paypal-signup-button-u2').show();
								$('#paypal-signup-content-u2').show();
								$('#standard-credentials').slideUp();
								$('#account').removeClass('paypal-disabled');
								$('#standard-credentials').slideUp();
								$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
								$('#integral-credentials').slideDown();
								$('#{PayPal::HSS_SOLUTION}').slideDown();
								$('label[for="paypal_payment_wpp"] .toolbox').slideDown();
								$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideUp();
								switch (integral_evolution_solution) {
									case "1": //Iframe
										$('#{PayPal::HSS_TEMPLATE}').slideUp();
										break;
									case "0": //Redirection
										$('#{PayPal::HSS_TEMPLATE}').slideDown();
										break;
								}
								break;
							case {PayPal::EC|intval}:
								$('.toolbox').slideUp();
								$('#paypalplus-credentials').slideUp();
								$('#integral-credentials').slideUp();
								$('#standard-credentials').slideDown();
								$('#paypal-signup-button-u3').show();
								$('#paypal-signup-content-u3').show();
								$('#{PayPal::HSS_SOLUTION}').slideUp();
								$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
								$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideDown();
								break;
							case {PayPal::WPP|intval}:
								$('#standard-credentials').slideUp();
								$('#integral-credentials').slideUp();
								$('#{PayPal::HSS_SOLUTION}').slideUp();
								$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
								$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideUp();
								$('#paypal-signup-button-u1').hide();
								$('#paypal-signup-content-u1').hide();
								$('#paypalplus-credentials').slideDown();
								break;
						}
					} else {
							$('#configuration').slideDown();
							$('#account').addClass('paypal-disabled');
							$('#credentials').removeClass('paypal-disabled');
							$('input[type="submit"]').removeAttr('disabled');

							switch (paypal_payment_method) {
								case {PayPal::WPS|intval}:
									$('#signup').slideUp();
									$('#paypalplus-credentials').slideUp();
									$('#integral-credentials').slideUp();
									$('#standard-credentials').slideDown();
									$('#paypal-signup-button-u4').show();
									$('#{PayPal::HSS_SOLUTION}').slideUp();
									$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
									$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideDown();
									break;
								case {PayPal::WPRH|intval}:
									$('#signup').slideDown();
									$('#paypalplus-credentials').slideUp();
									$('#paypal-signup-button-u5').show();
									$('#paypal-signup-content-u5').show();
									$('#account').removeClass('paypal-disabled');
									$('#standard-credentials').slideUp();
									$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
									$('#integral-credentials').slideDown();
									$('#{PayPal::HSS_SOLUTION}').slideDown();
									$('label[for="paypal_payment_wpp"] .toolbox').slideDown();
									$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideUp();
									switch (integral_evolution_solution) {
										case "1": //Iframe
											$('#{PayPal::HSS_TEMPLATE}').slideUp();
											break;
										case "0": //Redirection
											$('#{PayPal::HSS_TEMPLATE}').slideDown();
											break;
									}
									break;
								case {PayPal::EC|intval}:
									$('#signup').slideUp();
									$('#paypalplus-credentials').slideUp();
									$('#integral-credentials').slideUp();
									$('#standard-credentials').slideDown();
									$('#paypal-signup-button-u6').show();
									$('#{PayPal::HSS_SOLUTION}').slideUp();
									$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
									$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideDown();
									break;

								case {PayPal::WPP|intval}:
									$('#standard-credentials').slideUp();
									$('#integral-credentials').slideUp();
									$('#{PayPal::HSS_SOLUTION}').slideUp();
									$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
									$('#{PayPal::IN_CONTEXT_CHECKOUT}').slideUp();
									$('#paypal-signup-button-u1').hide();
									$('#paypal-signup-content-u1').hide();
									$('#paypalplus-credentials').slideDown();
									break
							}
					}

					displayCredentials();
					return;
				}

				if ($('#paypal-wrapper').length != 0) {
					$('.hide').hide();
					displayConfiguration();
				}

				if ($('input[name="{PayPal::PAYMENT_METHOD}"]').length == 1) {
					$('input[name="{PayPal::PAYMENT_METHOD}"]').attr('checked', 'checked');
				}

				function displayCredentials() {
					var paypal_business = $('input[name="{PayPal::BUSINESS}"]:checked').val();
					var paypal_payment_method = $('input[name="{PayPal::PAYMENT_METHOD}"]:checked').val();

					if (paypal_payment_method != PayPal_HSS &&
						($('input[name="{PayPal::API_USER}"]').val().length > 0 ||
						$('input[name="{PayPal::API_PASSWORD}"]').val().length > 0 ||
						$('input[name="{PayPal::API_SIGNATURE}"]').val().length > 0)) {

						if (paypal_payment_method == PayPal_PPP) {
							$('#paypalplus-credentials').slideDown();
						} else {
							$('#paypalplus-credentials').slideUp();
							$('#credentials').removeClass('paypal-disabled');
							$('#configuration').slideDown();
							$('input[type="submit"]').removeAttr('disabled');
							$('#standard-credentials').slideDown();
							$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
							$('#integral-credentials').slideUp();
						}
					}
					else if (paypal_payment_method == PayPal_HSS &&
						($('input[name="{PayPal::BUSINESS_ACCOUNT}"]').val().length > 0)) {
						$('#credentials').removeClass('paypal-disabled');
						$('#configuration').slideDown();
						$('input[type="submit"]').removeAttr('disabled');
						$('#standard-credentials').slideUp();
						$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
						$('#integral-credentials').slideDown();
					}
					else if (paypal_business != 1) {
						$('#configuration').slideUp();
					}
				}

				$('input[name="{PayPal::BUSINESS}"], input[name="{PayPal::PAYMENT_METHOD}"], input[name="{PayPal::HSS_SOLUTION}"]').on('change', function () {
					displayConfiguration();
				});

				$('label, a').live('mouseover', function () {
					$(this).children('.toolbox').show();
				}).live('mouseout', function () {
					var id = $(this).attr('for');
					var input = $('input#' + id);

					if ((!input.is(':checked')) || (($(this).attr('id') == 'paypal-get-identification') &&
						(identificationButtonClicked == false)))
						$(this).children('.toolbox').hide();
				});

				$('a.paypal-signup-button, a#step3').live('click', function () {
					var paypal_business = $('input[name="business"]:checked').val();
					var paypal_payment_method = $('input[name="{PayPal::PAYMENT_METHOD}"]:checked').val();

					$('#credentials').removeClass('paypal-disabled');
					if ($(this).attr('id') != 'paypal-signup-button-u3')
						$('#account').addClass('paypal-disabled');

					$('#configuration').slideDown();
					if (paypal_payment_method == PayPal_HSS) {
						$('#standard-credentials').slideUp();
						$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideUp();
						$('#integral-credentials').slideDown();
					} else {
						$('#standard-credentials').slideDown();
						$('#{PayPal::EXPRESS_CHECKOUT_SHORTCUT}').slideDown();
						$('#integral-credentials').slideUp();
					}
					$('input[type="submit"]').removeAttr('disabled');

					if ($(this).is('#step3')) {
						return false;
					}
					return true;
				});


				if ($("#paypal-wrapper").length > 0) {
					$('input[type="submit"]').live('click', function () {
						var paypal_business = $('input[name="{PayPal::BUSINESS}"]:checked').val();
						var paypal_payment_method = $('input[name="{PayPal::PAYMENT_METHOD}"]:checked').val();

						if (((paypal_payment_method == PayPal_WPS || paypal_payment_method == PayPal_ECS) &&
							(($('input[name="{PayPal::API_USER}"]').val().length <= 0) ||
							($('input[name="{PayPal::API_PASSWORD}"]').val().length <= 0) ||
							($('input[name="{PayPal::API_SIGNATURE}"]').val().length <= 0))) ||
							((paypal_payment_method == PayPal_HSS &&
							($('input[name="{PayPal::BUSINESS_ACCOUNT}"]').val().length <= 0))) ||
							(paypal_payment_method == PayPal_PPP &&
							(($('input[name="{PayPal::CLIENT_ID}"]').val().length <= 0) ||
							($('input[name="{PayPal::SECRET}"]').val().length <= 0)))) {
							$.fancybox({
								'content': $('<div id="js-paypal-save-failure">').append($('#js-paypal-save-failure').clone().html())
							});
							return false;
						}
						return true;
					});

					$('input[name="{PayPal::SANDBOX}"]').live('change', function () {
						if ($('input[name="{PayPal::SANDBOX}"]:checked').val() == '1') {
							$('input[name="{PayPal::SANDBOX}"]').filter('[value="0"]').attr('checked', true);
							var div = $('<div id="paypal-test-mode-confirmation">');
							var inner = $('#paypal-test-mode-confirmation').clone().html();
							$.fancybox({
								'hideOnOverlayClick': true,
								'content': div.append(inner)
							});
							return false;
						}
						return true;
					});

					$('button.fancy_confirm').live('click', function () {
						jQuery.fancybox.close();
						if ($(this).val() == '1') {
							$('input[name="{PayPal::SANDBOX}"]').filter('[value="1"]').attr('checked', true);
						} else {
							$('input[name="{PayPal::SANDBOX}"]').filter('[value="0"]').attr('checked', true);
						}
					});

					if ($('#paypal-save-success').length > 0)
						$.fancybox({
							'hideOnOverlayClick': true,
							'content': $('<div id="paypal-save-success">').append($('#paypal-save-success').clone().html())
						});
					else if ($('#paypal-save-failure').length > 0)
						$.fancybox({
							'hideOnOverlayClick': true,
							'content': $('<div id="paypal-save-failure">').append($('#paypal-save-failure').clone().html())
						});

					$('#paypal-get-identification').live('click', function () {

						identificationButtonClicked = true;
						sandbox_prefix = $('#paypal_payment_test_mode').is(':checked') ? 'sandbox.' : '';
						var url = 'https://www.' + sandbox_prefix + 'paypal.com/us/cgi-bin/webscr?cmd=_get-api-signature&generic-flow=true';
						var title = 'PayPal identification informations';
						window.open(url, title, config = 'height=500, width=360, toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, directories=no, status=no');
						return false;
					});

					$('a#paypal_country_change').on('click', function () {
						var div = $('<div id="paypal-country-form">');
						var inner = $('#paypal-country-form-content').clone().html();
						$.fancybox({
							'content': div.append(inner)
						});
						return false;
					});

					$('#paypal_country_default').on('change', function () {
						var form = $('#paypal_configuration');
						form.append('<input type="hidden" name="paypal_country_only" value="' + $(this).val() + '" />');
						form.submit();
					});


					$("#{PayPal::LOGIN}_yes_or_no input[name='{PayPal::LOGIN}']").change(function () {
						var val = parseInt($(this).val());
						if (val === 1) {
							$("#{PayPal::LOGIN}_configuration").slideDown();
						}
						else {
							$("#{PayPal::LOGIN}_configuration").slideUp();
						}

					});
				}

			});
		}

		initBackOfficeJs();
	})();
</script>
