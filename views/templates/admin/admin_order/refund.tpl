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

<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><img src="{$base_url|escape:'htmlall':'UTF-8'}modules/{$module_name|escape:'htmlall':'UTF-8'}/logo.gif" alt="" /> {l s='PayPal Refund' mod='paypal'}</div>
			<table class="table" width="100%" cellspacing="0" cellpadding="0">
			  <tr>
			    <th>{l s='Capture date' mod='paypal'}</th>
			    <th>{l s='Capture Amount' mod='paypal'}</th> 
			    <th>{l s='Result Capture' mod='paypal'}</th>
			  </tr>
			{foreach from=$list_captures item=list}
			  <tr>
			    <td>{Tools::displayDate($list.date_add, $smarty.const.null,true)|escape:'htmlall':'UTF-8'}</td>
			    <td>{$list.capture_amount|escape:'htmlall':'UTF-8'}</td> 
			    <td>{$list.result|escape:'htmlall':'UTF-8'}</td>
			  </tr>
			{/foreach}
			</table>
			{*<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">*}
				{*<input type="hidden" name="id_order" value="{$params.id_order|intval}" />*}
				{*<p><b>{l s='Information:' mod='paypal'}</b> {l s='Payment accepted' mod='paypal'}</p>*}
				{*<p><b>{l s='Information:' mod='paypal'}</b> {l s='When you refund a product, a partial refund is made unless you select "Generate a voucher".' mod='paypal'}</p>*}
				{*<p class="center">*}
					{*<button type="submit" class="btn btn-default" name="submitPayPalRefund" onclick="if (!confirm('{l s='Are you sure?' mod='paypal'}'))return false;">*}
						{*<i class="icon-undo"></i>*}
						{*{l s='Refund total transaction' mod='paypal'}*}
					{*</button>*}
				{*</p>*}
			{*</form>*}
		</div>
	</div>
</div>
