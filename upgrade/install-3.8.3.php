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
function upgrade_module_3_8_3($object, $install = false)
{
    $paypal_version = Configuration::get('PAYPAL_VERSION');

    if ((!$paypal_version) || (empty($paypal_version)) || ($paypal_version < $object->version)) {
        if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'paypal_order` LIKE \'id_invoice\'') == false) {
            Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD `id_invoice` varchar(255) DEFAULT NULL');
        }

        if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'paypal_order` LIKE \'currency\'') == false) {
            Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD `currency` varchar(10) DEFAULT NULL');
        }

        if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'paypal_order` LIKE \'total_paid\'') == false) {
            Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD `total_paid` varchar(50) DEFAULT NULL');
        }

        if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'paypal_order` LIKE \'shipping\'') == false) {
            Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD `shipping` varchar(50) DEFAULT NULL');
        }

        if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'paypal_order` LIKE \'payment_date\'') == false) {
            Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD `payment_date` varchar(50) DEFAULT NULL');
        }

        Configuration::updateValue('PAYPAL_VERSION', '3.8.3');
    }

    return true;
}
