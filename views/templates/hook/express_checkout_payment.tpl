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
<div class="row">
  <div class="col-xs-12 col-md-12">
    <p class="payment_module paypal">
      <a href="{$link->getModuleLink('paypal', 'expresscheckout', [], true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with PayPal' mod='paypal'}">
        <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/default_logos/default_horizontal_large.png" alt="{l s='Pay with your card or your PayPal account' mod='paypal'}" width="220px" height="64px"/>
        {l s='Pay with your card or your PayPal account' mod='paypal'}
      </a>
    </p>
  </div>
</div>

<style>
  p.payment_module.paypal a {
    padding: 10px;
    background-color: #FBFBFB;
  }

  p.payment_module.paypal img {
    height: 64px;
  }

  p.payment_module.paypal a:hover {
    background-color: #f6f6f6;
  }

  p.payment_module.paypal a:after {
    display: block;
    content: "\f054";
    position: absolute;
    right: 15px;
    margin-top: -11px;
    top: 50%;
    font-family: "FontAwesome";
    font-size: 25px;
    height: 22px;
    width: 14px;
    color: #777777;
  }
</style>
