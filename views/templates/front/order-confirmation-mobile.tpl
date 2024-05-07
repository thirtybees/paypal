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

<div data-role="content" id="content" class="cart">
	{include file="$tpl_dir./errors.tpl"}

	<h2>{l s='Order confirmation' mod='paypal'}</h2>

	{assign var='current_step' value='payment'}

	{include file="$tpl_dir./errors.tpl"}

	{$HOOK_ORDER_CONFIRMATION}
	{$HOOK_PAYMENT_RETURN}

	<br/>

	{if $order}
		<p>{l s='Total of the transaction (taxes incl.) :' mod='paypal'} <span class="paypal-bold">{$price|escape:'htmlall':'UTF-8'}</span></p>
		<p>{l s='Your order ID is :' mod='paypal'}
			<span class="paypal-bold">
				{Order::getUniqReferenceOf($order.id_order)|escape:'htmlall':'UTF-8'}
			</span>
		</p>
		<p>{l s='Your PayPal transaction ID is :' mod='paypal'} <span class="paypal-bold">{$order.id_transaction|escape:'htmlall':'UTF-8'}</span></p>
	{/if}

	<br/>

	{if !$is_guest}
		<a href="{$link->getPageLink('index', true)|escape:'htmlall':'UTF-8'}" data-role="button" data-theme="a" data-icon="back" data-ajax="false">{l s='Continue shopping' mod='paypal'}</a>
	{else}
		<ul data-role="listview" data-inset="true" id="list_myaccount">
			<li data-theme="a" data-icon="check">
				<a href="{$link->getPageLink('index', true)|escape:'htmlall':'UTF-8'}" data-ajax="false">{l s='Continue shopping' mod='paypal'}</a>
			</li>
			<li data-theme="b" data-icon="back">
				<a href="{$link->getPageLink('history.php', true, NULL, 'step=1&amp;back={$back|escape:'htmlall':'UTF-8'}')}" data-ajax="false">{l s='Back to orders' mod='paypal'}</a>
			</li>
		</ul>
	{/if}
	<br/>
</div><!-- /content -->
