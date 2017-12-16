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
        <i class="icon icon-paypal"></i> {l s='PayPal Refund' mod='paypal'}
      </div>
      <table class="table" width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <th>{l s='Capture date' mod='paypal'}</th>
          <th>{l s='Capture Amount' mod='paypal'}</th>
          <th>{l s='Result Capture' mod='paypal'}</th>
        </tr>
        {foreach from=$list_captures item=list}
          <tr>
            <td>{Tools::displayDate($list.date_add, $smarty.const.null,true)|escape:'htmlall':'UTF-8'}</td>
            <td>{$list.capture_amount|escape:'htmlall':'UTF-8'}</td>
            <td>{$list.result|escape:'htmlall':'UTF-8'}</td>
          </tr>
        {/foreach}
      </table>
    </div>
  </div>
</div>
