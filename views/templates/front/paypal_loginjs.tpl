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
		function initPayPalLoginJs() {
			if (typeof $ === 'undefined' || typeof window.paypal === 'undefined' || typeof window.paypal.loginUtils === 'undefined' || typeof window.paypal.login === 'undefined') {
				setTimeout(initPayPalLoginJs, 100);
				return;
			}

			if ($("#create-account_form").length > 0) {
				$("#create-account_form").parent().before('<div id="buttonPaypalLogin1"></div>');
			} else {
				$("#login_form").parent().before('<div id="buttonPaypalLogin1"></div>');
				$("#buttonPaypalLogin1").css({
					"clear": "both",
					"margin-bottom": "13px"
				});
			}
			$("#buttonPaypalLogin1").css({
				"clear": "both",
				'margin-bottom': '10px',
				'margin-left': '20px',
				'width': '100%'
			});

			window.paypal.loginUtils.applyStyles();
			window.paypal.login.render({
				appid: '{$client_id|escape:'javascript':'UTF-8'}',
				authend: {if !$live}'sandbox'{else}''{/if},
				scopes: 'openid profile email address phone https://uri.paypal.com/services/paypalattributes https://uri.paypal.com/services/expresscheckout',
				containerid: 'buttonPaypalLogin1',
				theme: {if $login_theme}'blue'{else}'neutral'{/if},
				returnurl: '{$return_link|escape:'javascript':'UTF-8'}?{$page_name|escape:'javascript':'UTF-8'}',
				locale: '{$paypal_locale|escape:'javascript':'UTF-8'}',
			});
		}

		initPayPalLoginJs();
	})();
	;
</script>


