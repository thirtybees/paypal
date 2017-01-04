<?php
/**
 * 2007-2016 PrestaShop
 * 2007 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2016 PrestaShop SA
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

header('Content-Type: text/html; charset=utf-8');
include_once dirname(__FILE__).'/../../../config/config.inc.php';
include_once dirname(__FILE__).'/../../../init.php';

include_once _PS_MODULE_DIR_.'paypal/paypal.php';
include_once _PS_MODULE_DIR_.'paypal/paypal_login/paypal_login.php';
include_once _PS_MODULE_DIR_.'paypal/paypal_login/PayPalLoginUser.php';

$login = new PayPalLogin();

$obj = $login->getAuthorizationCode();
if ($obj) {
    $context = Context::getContext();
    $customer = new Customer((int) $obj->id_customer);
    $context->cookie->id_customer = (int) ($customer->id);
    $context->cookie->customer_lastname = $customer->lastname;
    $context->cookie->customer_firstname = $customer->firstname;
    $context->cookie->logged = 1;
    $customer->logged = 1;
    $context->cookie->is_guest = $customer->isGuest();
    $context->cookie->passwd = $customer->passwd;
    $context->cookie->email = $customer->email;
    $context->customer = $customer;
    $context->cookie->write();
}

?>

<script type="text/javascript">
	window.opener.location.reload(false);
	window.close();
</script>
