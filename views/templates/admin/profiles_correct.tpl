{*
 * 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{l s='In order to provide the best checkout experience, all profiles have to be loaded and available.' mod='paypal'}<br />
<br />
{l s='All profiles have been loaded correctly:' mod='paypal'}
<ul>
	{if !empty($standardProfile)}<li>Website Payments Standard: {$standardProfile|escape:'htmlall':'UTF-8'}</li>{/if}
	{if !empty($plusProfile)}<li>Website Payments Plus: {$plusProfile|escape:'htmlall':'UTF-8'}</li>{/if}
	{if !empty($expressCheckoutProfile)}<li>Express Checkout: {$expressCheckoutProfile|escape:'htmlall':'UTF-8'}</li>{/if}
</ul>
