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
{capture name=path}
	<a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}">
		{l s='Your shopping cart' mod='paypal'}
	</a>
	<span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>
	{l s='PayPal' mod='paypal'}
{/capture}

<h1>{l s='Order summary' mod='paypal'}</h1>

<h3>{l s='PayPal payment' mod='paypal'}</h3>
<form action="{$confirm_form_action|escape:'htmlall':'UTF-8'}" method="post" data-ajax="false">
	{$paypal_cart_summary}
	<p>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='paypal'}.</b>
	</p>
	<p class="cart_navigation">
		<input type="submit" name="confirmation" value="{l s='I confirm my order' mod='paypal'}" class="exclusive_large"/>
	</p>
</form>

