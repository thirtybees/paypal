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
{*Displaying a button or the iframe*}
<div id="ppplus"></div>

{literal}
<script type="application/javascript">
  (function () {
    function initPayPalPlus() {
      if (typeof PAYPAL === 'undefined' || typeof PAYPAL.apps === 'undefined' || typeof PAYPAL.apps.PPP === 'undefined') {
        setTimeout(initPayPalPlus, 100);
        return;
      }

      var ppp = PAYPAL.apps.PPP({
        "approvalUrl": "{/literal}{$approval_url}{literal}",
        "placeholder": "ppplus",
        "mode": "{/literal}{$mode|escape:'htmlall':'UTF-8'}{literal}",
        "language": "{/literal}{$language|escape:'htmlall':'UTF-8'}{literal}",
        "country": "{/literal}{$country|escape:'htmlall':'UTF-8'}{literal}",
      });
    }

    initPayPalPlus();
  })();
</script>
{/literal}

