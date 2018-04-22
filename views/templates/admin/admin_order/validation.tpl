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
  <div class="col-lg-12">
    <div class="panel">
      <div class="panel-heading">
        <i class="icon icon-paypal"></i> {l s='PayPal Validation' mod='paypal'}
      </div>
      <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall'}">
        <input type="hidden" name="id_order" value="{$params.id_order|intval}"/>
        <p>
          <b>{l s='Information:' mod='paypal'}</b> {if $order_state == $authorization}{l s='Pending Capture - No shipping' mod='paypal'}{else}{l s='Pending Payment - No shipping' mod='paypal'}{/if}
        </p>
        <p class="center">
          <button type="submit" class="btn btn-default" name="submitPayPalValidation">
            {l s='Get payment status' mod='paypal'}
          </button>
        </p>
      </form>
    </div>
  </div>
</div>
