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
{capture name=path}
  <a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}">
    {l s='Your shopping cart' mod='paypal'}
  </a>
  <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>
  {l s='PayPal' mod='paypal'}
{/capture}

<h1>{l s='PayPal' mod='paypal'}</h1>
<div class="alert alert-danger">
  <h3>{l s='PayPal error' mod='paypal'}</h3>
  <p>{l s='Unfortunately we cannot ship to the address you selected during the PayPal checkout.'}</p>
  {l s='Click' mod='paypal'} <a href="{$link->getModuleLink('paypal', 'expresscheckout', [], true)|escape:'htmlall':'UTF-8'}">{l s='here' mod='paypal'}</a> {l s='to return to PayPal' mod='paypal'}.
  {l s='Or go back to' mod='paypal'} <a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}">{l s='the checkout' mod='paypal'}</a>.
</div>
