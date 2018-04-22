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
{l s='In order to provide the best checkout experience, all profiles have to be loaded and available.' mod='paypal'}<br />
<br />
{l s='Unfortunately, some profiles are missing:' mod='paypal'}
<ul>
	<li>Website Payments Standard: {$standardProfile|escape:'htmlall'}</li>
	<li>Website Payments Plus: {$plusProfile|escape:'htmlall'}</li>
	<li>Express Checkout: {$expressCheckoutProfile|escape:'htmlall'}</li>
</ul>
