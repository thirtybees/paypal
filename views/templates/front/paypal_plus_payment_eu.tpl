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
{capture name=path}
  <a href="{$link->getPageLink('order', true)|escape:'htmlall'}">
    {l s='Order' mod='paypal'}
  </a>
  <span class="navigation-pipe">
    {$navigationPipe|escape:'html'}
  </span>
  {l s='PayPal checkout' mod='paypal'}
{/capture}
<div id="ppplus"></div>

<script async defer src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js" type="text/javascript"></script>
<script type="application/javascript">
  (function () {
    function initPayPalPlus() {
      if (typeof PAYPAL === 'undefined' || typeof PAYPAL.apps === 'undefined' || typeof PAYPAL.apps.PPP === 'undefined') {
        setTimeout(initPayPalPlus, 100);
        return;
      }

      var ppp = PAYPAL.apps.PPP({
        approvalUrl: '{$approval_url}',
        placeholder: 'ppplus',
        mode: '{$mode|escape:'htmlall'}',
        language: '{$language|escape:'htmlall'}',
        country: '{$country|escape:'htmlall'}',
      });
    }

    initPayPalPlus();
  })();

</script>
