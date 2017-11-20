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
<div class="panel">
  <h3><i class="icon icon-server"></i> {l s='Webhooks' mod='paypal'}</h3>
  <p>
    {l s='PayPal requires webhook to process payments asynchronously. I.e. when a payment is first pending, then turns into a captured one after a brief audit check.' mod='paypal'}<br/>
  </p>
  {if $id_webhook}
    <div class="alert alert-success">{l s='A webhook with ID %s has been registered and is currently active.' mod='paypal' sprintf=[$id_webhook]}</div>
  {else}
    <div class="alert alert-danger">{l s='There is currently no active webhook or the Client ID has changed recently. Please run a new check to get this fixed.' mod='paypal'}</div>
  {/if}
  <a class="btn btn-default" href="{$module_url|escape:'htmlall':'UTF-8'}&checkWebhooks=1">{l s='Run a webhooks check' mod='paypal'}</a>
</div>

