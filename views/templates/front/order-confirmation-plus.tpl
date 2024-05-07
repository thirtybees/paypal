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


{capture name=path}{l s='Order confirmation' mod='paypal'}{/capture}
<h1>{l s='Order confirmation' mod='paypal'}</h1>
{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{include file="$tpl_dir./errors.tpl"}

{$paypal_cart_summary}
<div class="inforeturn"></div>
<div class="confirm_PPP">
	{if $state == 'approved' || $state == 'created'}
		<h2>{l s='Order Confirmation ?' mod='paypal'}</h2>
		<p>{l s='Do you want to confirm your order for total amount of ' mod='paypal'}{$totalAmount|escape:'htmlall':'UTF-8'}</p>
		<form method="POST" action="" id="formConfirm">
			<input type="hidden" name="payerID" value="{$PayerID|escape:'htmlall':'UTF-8'}"/>
			<input type="hidden" name="paymentId" value="{$paymentId|escape:'htmlall':'UTF-8'}"/>
			<input type="hidden" name="id_cart" value="{$id_cart|escape:'htmlall':'UTF-8'}"/>

			<input id="cancel" class="button btn btn-large" type="submit" name="confirmCancel" value="{l s='Cancel your order' mod='paypal'}"/>
			<input id="confirm" class="button btn btn-large" type="submit" name="confirmPayment" value="{l s='Confirm your payment' mod='paypal'}"/>
		</form>
		<script type="text/javascript" data-cookieconsent="necessary">

			$(document).ready(function () {


				$("#formConfirm input[type=submit]").click(function () {
					$("input[type=submit]", $(this).parents("form")).removeAttr("clicked");
					$(this).attr("clicked", "true");
				});

				$('#formConfirm').submit(function () {

					var form = $('#formConfirm');
					var nameSubmit = $("input[type=submit][clicked=true]").attr('name');

					$('#cancel').attr('disabled', 'disabled');
					$('#confirm').attr('disabled', 'disabled');

					$.ajax({
						url: '{$linkSubmitPlus|escape:'htmlall':'UTF-8'}',
						type: 'POST',
						data: form.serialize() + '&ajax=true&submit=' + nameSubmit,
						success: function (data) {
							var json = JSON.parse(data);

							$('.paypal-error').remove();

							if (typeof json.success !== 'undefined') {
								$('.inforeturn').html('<p class="alert alert-success">' + json.success + '</p>');
								if (typeof window.ajaxCart !== 'undefined' && typeof window.ajaxCart.refresh === 'function') {
									window.ajaxCart.refresh();
								}
							}

							if (typeof json.error !== 'undefined') {
								$('.inforeturn').html('<p class="alert alert-warning paypal-error">' + json.error + '</p>');
							}
						}
					});

					return false;
				});

			});

		</script>
		<div style="margin-top:15px;">
			{if isset($is_guest) && $is_guest}
				<a href="{$link->getPageLink('guest-tracking.php', true)|escape:'htmlall':'UTF-8'}?id_order={$order_reference|escape:'htmlall':'UTF-8'}" title="{l s='Follow my order' mod='paypal'}" data-ajax="false">
					<i class="icon-chevron-left"></i>
				</a>
				<a href="{$link->getPageLink('guest-tracking.php', true)|escape:'htmlall':'UTF-8'}?id_order={$order_reference|escape:'htmlall':'UTF-8'}" title="{l s='Follow my order' mod='paypal'}" data-ajax="false">{l s='Follow my order' mod='paypal'}</a>
			{else}
				<a href="{$link->getPageLink('history.php', true)|escape:'htmlall':'UTF-8'}" title="{l s='Back to orders' mod='paypal'}" data-ajax="false">
					<i class="icon-chevron-left"></i>
				</a>
				<a href="{$link->getPageLink('history.php', true)|escape:'htmlall':'UTF-8'}" title="{l s='Back to orders' mod='paypal'}" data-ajax="false">{l s='Back to orders' mod='paypal'}</a>
			{/if}
		</div>
	{elseif $state == 'failed' || $state == 'expired'}
		<p class="alert alert-warning paypal-error">{l s='An error occurred during the payment' mod='paypal'}</p>
	{elseif $state == 'canceled'}
		<p class="alert alert-warning paypal-error">{l s='Your order has been canceled' mod='paypal'}</p>
	{/if}
</div>

