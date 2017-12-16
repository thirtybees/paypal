{*
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div class="panel">
  <h3><i class="icon icon-lock"></i> {l s='TLS v1.2 support' mod='paypal'}</h3>
  <p>
    <strong>{l s='Check if your server supports TLS v1.2' mod='paypal'}</strong><br/>
    {l s='This module cannot process payments if TLS v1.2 is not supported by your server.' mod='paypal'}<br/>
    {l s='With this tool you can check if you need to configure your server in order to use the module.' mod='paypal'}
    <br/>
    {l s='If the module was unable to verify that TLS v1.2 is supported, PayPal will automatically be disabled.' mod='paypal'}
    {l s='Make sure you see a (green) confirmation message underneath and you will be good to go.' mod='paypal'}
  </p>
  {if $tls_ok === PayPal::ENUM_TLS_OK}
    <div class="alert alert-success">
      {l s='TLS v1.2 is supported' mod='paypal'}
    </div>
  {elseif $tls_ok === PayPal::ENUM_TLS_ERROR}
    <div class="alert alert-danger">
      {l s='TLS v1.2 is not supported. Please upgrade your server.' mod='paypal'}
    </div>
  {else}
    <div class="alert alert-warning">
      {l s='Status is unknown. Please check if TLS v1.2 is supported.' mod='paypal'}
    </div>
  {/if}
  <a class="btn btn-default" href="{$module_url|escape:'htmlall':'UTF-8'}&checktls=1">{l s='Check for TLS v1.2 support' mod='paypal'} <i class="icon icon-chevron-right"></i></a>
</div>

