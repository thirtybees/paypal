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

<form id="paypal_payment_form" action="{$express_checkout_payment_link|escape:'htmlall':'UTF-8'}" title="{l s='Pay with PayPal' mod='paypal'}" method="post" data-ajax="false">
	{if isset($smarty.get.id_product)}<input type="hidden" name="id_product" value="{$smarty.get.id_product|intval}" />{/if}
	<!-- Change dynamicaly when the form is submitted -->
	{if isset($product_minimal_quantity)}
	<input type="hidden" name="quantity" value="{$product_minimal_quantity|escape:'htmlall':'UTF-8'}" />
	{else}
	<input type="hidden" name="quantity" value="1" />
	{/if}
	{if isset($id_product_attribute_ecs)}
	<input type="hidden" name="id_product_attribute" value="{$id_product_attribute_ecs|escape:'htmlall':'UTF-8'}" />
	{else}
	<input type="hidden" name="id_product_attribute" value="" />
	{/if}
	<input type="hidden" name="express_checkout" value="{$PayPal_payment_type|escape:'htmlall':'UTF-8'}"/>
	<input type="hidden" name="current_shop_url" value="{$PayPal_current_page|escape:'htmlall':'UTF-8'}" />
	<input type="hidden" name="bn" value="{$PayPal_tracking_code|escape:'htmlall':'UTF-8'}" />
</form>

{if $use_paypal_in_context}
	<input type="hidden" id="in_context_checkout_enabled" value="1">
{else}
	<input type="hidden" id="in_context_checkout_enabled" value="0">
{/if}


