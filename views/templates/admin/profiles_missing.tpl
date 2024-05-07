{*
 * 2017 Thirty Bees
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
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{l s='In order to provide the best checkout experience, all profiles have to be loaded and available.' mod='paypal'}<br />
<br />
{l s='Unfortunately, some profiles could not be created:' mod='paypal'}
<ul>
	{if $standardProfileNeeded}<li>Website Payments Standard: {if empty($standardProfile)}<em>{l s='missing' mod='paypal'}</em>{else}{$standardProfile|escape:'htmlall':'UTF-8'}{/if}</li>{/if}
	{if $plusProfileNeeded}<li>Website Payments Plus: {if empty($plusProfile)}<em>{l s='missing' mod='paypal'}</em>{else}{$plusProfile|escape:'htmlall':'UTF-8'}{/if}</li>{/if}
	{if $expressCheckoutProfileNeeded}<li>Express Checkout: {if empty($expressCheckoutProfile)}<em>{l s='missing' mod='paypal'}</em>{else}{$expressCheckoutProfile|escape:'htmlall':'UTF-8'}{/if}</li>{/if}
</ul>
{l s='This is most likely caused by existing profiles with same name, likely from another Thirty Bees instance. Try creating a new, empty REST API app in the PayPal developer portal.' mod='paypal'}