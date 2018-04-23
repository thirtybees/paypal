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
  {if $input.type == 'hr'}
    <hr>
  {elseif $input.type == 'description'}
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
      <div class="radio {if isset($input.class)}{$input.class|escape:'htmlall'}{/if}">
        {strip}
          <label>
            <input type="radio"
                   name="{$input.name|escape:'htmlall'}"
                   id="{$value.id|intval}"
                   value="{$value.value|escape:'htmlall'}"
                   {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
                   {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
                   style="{if isset($input.margin)}margin-top: {$input.margin|intval}px;{/if}box-sizing: border-box;"
            >
            <span style="display:inline-block;min-width: {if !isset($input.distance)}50{else}{$input.distance|intval}{/if}px">{$value.label|escape:'htmlall'}</span><img class="radio-image" src="{$value.image|escape:'htmlall'}">
          </label>
        {/strip}
      </div>
      {if isset($value.p) && $value.p}<p class="help-block">{$value.p|escape:'htmlall'}</p>{/if}
    {/foreach}
  {elseif $input.type == 'switch'}
    <span class="switch prestashop-switch fixed-width-lg">
      {foreach [['value'=>1],['value'=>0]] as $value}
        <input type="radio"
               name="{$input.name}"
                {if $value.value == 1} id="{$input.name}_on"{else} id="{$input.name}_off"{/if}
               value="{$value.value}"
                {if $fields_value[$input.name] == $value.value} checked="checked"{/if}
                {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
        />
        <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>{if $value.value == 1}{l s='Yes'}{else}{l s='No'}{/if}</label>
      {/foreach}
      <a class="slide-button btn"></a>
    </span>
  {elseif $input.type == 'imageswitch'}
    <span class="switch prestashop-switch fixed-width-lg">
      {foreach [['value'=>1],['value'=>0]] as $value}
        <input type="radio"
               name="{$input.name}"
                {if $value.value == 1} id="{$input.name}_on"{else} id="{$input.name}_off"{/if}
               value="{$value.value}"
                {if $fields_value[$input.name] == $value.value} checked="checked"{/if}
                {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
        />
        <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>{if $value.value == 1}{l s='Yes'}{else}{l s='No'}{/if}</label>
      {/foreach}
      <a class="slide-button btn"></a>
    </span>
    <label style="margin-top: 20px">
      <img src="{$input.image.src|escape:'htmlall'}"
           height="{$input.image.height|intval}"
           width="{$input.image.width|intval}"
           style="width: auto; height: 40px"
           class="switch-image"
      ></label>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
