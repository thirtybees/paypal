{*
 * Copyright (C) 2017 thirty bees
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
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{capture name=path}<a href="order.php">{l s='Your shopping cart' mod='paypal'}</a><span
        class="navigation-pipe"> {$navigationPipe|escape:'htmlall':'UTF-8'} </span>{l s='PayPal' mod='paypal'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<h2>{$message|escape:'htmlall':'UTF-8'}</h2>
{if isset($logs) && $logs}
  <div class="error">
    <strong>{l s='Please try to contact the merchant:' mod='paypal'}</strong>

    <ol>
      {foreach from=$logs key=key item=log}
        <li>{$log|escape:'htmlall':'UTF-8'}</li>
      {/foreach}
    </ol>

    <br>

    {if isset($order)}
      <p>
        {l s='Total of the transaction (taxes incl.) :' mod='paypal'} <span
                class="paypal-bold">{$price|escape:'htmlall':'UTF-8'}</span><br>
        {l s='Your order ID is :' mod='paypal'} <span class="paypal-bold">{$order.id_order|intval}</span><br>
      </p>
    {/if}

    <p><a href="{$base_dir|escape:'htmlall':'UTF-8'}" class="button_small"
          title="{l s='Back' mod='paypal'}">&laquo; {l s='Back' mod='paypal'}</a></p>
  </div>
{/if}
