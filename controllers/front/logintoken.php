<?php
/**
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
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
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPalModule\PayPalLogin;

require_once dirname(__FILE__).'/../../paypal.php';

class PayPalLogintokenModuleFrontController extends \ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;

    public function initContent()
    {
        $login = new PayPalLogin();

        $obj = $login->getAuthorizationCode();
        if ($obj) {
            $context = \Context::getContext();
            $customer = new \Customer((int) $obj->id_customer);
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

        header('Content-Type: text/html');
        echo '<!doctype html><html><body><script type="text/javascript">window.opener.location.reload(false);window.close();</script></body></html>';
        die();
    }
}
