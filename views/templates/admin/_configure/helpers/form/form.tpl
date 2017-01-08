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
	{else}
		{$smarty.block.parent}
	{/if}
{/block}
