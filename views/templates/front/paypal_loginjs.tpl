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
<script type="text/javascript">
  (function () {
    function initPayPalLoginJs() {
      if (typeof window.paypal === 'undefined'
        || typeof window.paypal.loginUtils === 'undefined'
        || typeof window.paypal.login === 'undefined'
      ) {
        setTimeout(initPayPalLoginJs, 10);

        return;
      }

      var createAccountForm = document.getElementById('create-account_form');

      if (paypalLoginButton) {
        paypalLoginButton.style.clear = 'both';
        paypalLoginButton.style.marginBottom = '10px';
        paypalLoginButton.style.marginLeft = '20px';
        paypalLoginButton.style.width = '100%';
      }

      var paypalLoginButton;
      if (createAccountForm) {
        if (!createAccountForm.parentNode) {
          createAccountForm.parentNode.insertAdjacentHTML('beforebegin', '<div id="buttonPaypalLogin1"></div>');
        } else {
          createAccountForm.parentNode.insertAdjacentHTML('beforebegin', '<div id="buttonPaypalLogin1"></div>');
        }

        paypalLoginButton = document.getElementById('buttonPaypalLogin1');

        paypalLoginButton.style.clear = 'both';
        paypalLoginButton.style.marginBottom = '10px';
        paypalLoginButton.style.marginLeft = '20px';
        paypalLoginButton.style.width = '100%';
      } else {
        var loginForm = document.getElementById('login_form');
        if (!loginForm) {
          return;
        }

        if (!loginForm.parentNode) {
          loginForm.insertAdjacentHTML('beforebegin', '<div id="buttonPaypalLogin1"></div>');
        } else {
          loginForm.parentNode.insertAdjacentHTML('beforebegin', '<div id="buttonPaypalLogin1"></div>');
        }

        paypalLoginButton = document.getElementById('buttonPaypalLogin1');
        paypalLoginButton.style.clear = 'both';
        paypalLoginButton.style.marginBottom = '13px';
        paypalLoginButton.style.marginLeft = '20px';
        paypalLoginButton.style.width = '100%';
      }

      window.paypal.loginUtils.applyStyles();
      window.paypal.login.render({
        appid: '{$client_id|escape:'javascript':'UTF-8'}',
        authend: {if !$live}'sandbox'{else}''{/if},
        scopes: 'openid profile email address phone https://uri.paypal.com/services/paypalattributes',
        containerid: 'buttonPaypalLogin1',
        theme: {if $login_theme}'blue'{else}'neutral'{/if},
        returnurl: '{$return_link|escape:'javascript':'UTF-8'}',
        locale: '{$paypal_locale|escape:'javascript':'UTF-8'}',
      });
    }

    initPayPalLoginJs();
  })();
</script>


