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
        <img src="{$base_url|escape:'htmlall'}modules/{$module_name|escape:'htmlall'}/logo.gif"
             alt=""/>
        {l s='PayPal Capture' mod='paypal'}
      </div>
      {if  $list_captures|@count gt 0}
        <table class="table" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <th>{l s='Capture date' mod='paypal'}</th>
            <th>{l s='Capture Amount' mod='paypal'}</th>
            <th>{l s='Result Capture' mod='paypal'}</th>
          </tr>
          {foreach from=$list_captures item=list}
            <tr>
              <td>{Tools::displayDate($list.date_add, $smarty.const.null,true)|escape:'htmlall'}</td>
              <td>{$list.capture_amount|escape:'htmlall'}</td>
              <td>{$list.result|escape:'htmlall'}</td>
            </tr>
          {/foreach}
        </table>
      {/if}
      <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall'}">
        <p>{l s='There is still' mod='paypal'} {$rest_to_capture|escape:'htmlall'} {$id_currency|escape:'htmlall'} {l s='to capture.' mod='paypal'} {l s='How many do you want to capture :' mod='paypal'}</p>
        <input type="text"
               onchange="captureEdit();"
               name="totalCaptureMoney"
               style="width80%;"
               placeholder="{l s='Enter the money you want to capture (ex: 200.00)' mod='paypal'}"
        >
        <input type="hidden" name="id_order" value="{$params.id_order|escape:'htmlall'}">
        <p><strong>{l s='Information:' mod='paypal'}</strong> {l s='Funds ready to be captured before shipping' mod='paypal'}</p>
        <p class="center">
          <button type="submit"
                  class="btn btn-default"
                  name="submitPayPalCapture"
                  onclick="if (!confirm('{l s='Are you sure you want to capture?' mod='paypal' js=1}')){ldelim}return false; {rdelim}"
          >
            {l s='Get the money' mod='paypal'}
          </button>
        </p>
      </form>
    </div>
  </div>
</div>
