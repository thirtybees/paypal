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
{capture name=path}
  {l s='Home' mod='paypal'}
  <span class="navigation-pipe"> {$navigationPipe|escape:'htmlall':'UTF-8'} </span>
  {l s='PayPal' mod='paypal'}
{/capture}
<p>{l s='Your order on' mod='paypal'} <span class="paypal-bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='is complete.' mod='paypal'}
  <br/><br/>
  {l s='You have chosen the PayPal method.' mod='paypal'}
  <br/><br/><span class="paypal-bold">{l s='Your order will be sent very soon.' mod='paypal'}</span>
  <br/><br/>{l s='For any questions or for further information, please contact our' mod='paypal'}
  <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}" data-ajax="false" target="_blank">{l s='customer support' mod='paypal'}</a>.
</p>
