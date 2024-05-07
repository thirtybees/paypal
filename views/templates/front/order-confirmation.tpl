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

{$HOOK_ORDER_CONFIRMATION}
{$HOOK_PAYMENT_RETURN}
<br/>
{if $order}
    <p>{l s='Total of the transaction (taxes incl.) :' mod='paypal'} <span class="paypal-bold">{$price|escape:'htmlall':'UTF-8'}</span></p>
    <p>{l s='Your order ID is :' mod='paypal'}
        <span class="paypal-bold">
            {if isset($reference_order)}
                {$reference_order|escape:'htmlall':'UTF-8'}
            {else}
                {$order.id_order|intval}
            {/if}
		</span>
    </p>
    <p>{l s='Your PayPal transaction ID is :' mod='paypal'} <span class="paypal-bold">{$order.id_transaction|escape:'htmlall':'UTF-8'}</span></p>
{/if}
<br/>
{if $is_guest}
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

