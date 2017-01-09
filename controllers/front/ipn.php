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

use PayPalModule\PayPalIpn;

require_once dirname(__FILE__).'/../../paypal.php';

class paypalipnModuleFrontController extends \ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;

    public function initContent()
    {
        if (\Tools::getValue('receiver_email') == \Configuration::get('PAYPAL_BUSINESS_ACCOUNT')) {
            if (\Tools::getIsset('custom')) {
                $ipn = new PayPalIpn();
                $custom = json_decode(\Tools::getValue('custom'), true);
                $ipn->confirmOrder($custom);
            }
        }
    }
}
