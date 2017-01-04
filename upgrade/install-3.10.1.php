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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param      $object
 * @param bool $install
 *
 * @return bool
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
function upgrade_module_3_10_1($object, $install = false)
{
    $paypal_version = Configuration::get('PAYPAL_VERSION');

    if (Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT_MERCHANT_ID') != false) {
        Configuration::updateValue('PAYPAL_IN_CONTEXT_CHECKOUT_M_ID', Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT_MERCHANT_ID'));
        Configuration::deleteByName('PAYPAL_IN_CONTEXT_CHECKOUT_MERCHANT_ID');
    }

    Configuration::updateValue('PAYPAL_VERSION', '3.10.1');
    return true;
}
