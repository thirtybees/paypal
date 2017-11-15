{*
 * 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{capture name=path}
  <a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}">
    {l s='Order' mod='paypal'}
  </a>
  <span class="navigation-pipe">
		{$navigationPipe|escape:'html':'UTF-8'}
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
        mode: '{$mode|escape:'htmlall':'UTF-8'}',
        language: '{$language|escape:'htmlall':'UTF-8'}',
        country: '{$country|escape:'htmlall':'UTF-8'}',
      });
    }

    initPayPalPlus();
  })();

</script>
