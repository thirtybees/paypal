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
 *
*}
{extends file="helpers/form/form.tpl"}

{block name="input"}
  {if $input.type == 'description'}
    {if $input.text}
      <div class="alert alert-info">{$input.text}</div>
    {/if}
  {elseif $input.type == 'warning'}
    {if $input.text}
      <div class="alert alert-warning">{$input.text}</div>
    {/if}
  {elseif $input.type == 'error'}
    {if $input.text}
      <div class="alert alert-danger">{$input.text}</div>
    {/if}
  {elseif $input.type == 'confirmation'}
    {if $input.text}
      <div class="alert alert-success">{$input.text}</div>
    {/if}
  {elseif $input.type == 'radio'}
    {foreach $input.values as $value}
      <div class="radio {if isset($input.class)}{$input.class|escape:'htmlall':'UTF-8'}{/if}">
        {strip}
          <label>
            <input type="radio"
                   name="{$input.name|escape:'htmlall':'UTF-8'}"
                   id="{$value.id|intval}"
                   value="{$value.value|escape:'htmlall':'UTF-8'}"
                   {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
                   {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
            >
            {$value.label|escape:'htmlall':'UTF-8'}&nbsp;&nbsp;&nbsp;<img src="{$value.image|escape:'htmlall':'UTF-8'}">
          </label>
        {/strip}
      </div>
      {if isset($value.p) && $value.p}<p class="help-block">{$value.p|escape:'htmlall':'UTF-8'}</p>{/if}
    {/foreach}
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
