{*
 *
 * Copyright (C) Mijn Presta - All Rights Reserved
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited
 *
 * @author    Michael Dekker <prestashopaddons@mijnpresta.nl>
 * @copyright 2015-2016 Mijn Presta
 * @license   proprietary
 * Intellectual Property of Mijn Presta
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
			<div class="radio {if isset($input.class)}{$input.class}{/if}">
				{strip}
					<label>
						<input type="radio"	name="{$input.name}" id="{$value.id}" value="{$value.value|escape:'html':'UTF-8'}"{if $fields_value[$input.name] == $value.value} checked="checked"{/if}{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}/>
						{$value.label}&nbsp;&nbsp;&nbsp;<img src="{$value.image}">
					</label>
				{/strip}
			</div>
			{if isset($value.p) && $value.p}<p class="help-block">{$value.p}</p>{/if}
		{/foreach}
	{else}
		{$smarty.block.parent}
	{/if}
{/block}
