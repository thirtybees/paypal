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
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * @param PayPal $module
 * @return bool
 */
function upgrade_module_5_0_0($module)
{
    try {
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD COLUMN `id_payer` VARCHAR(255)');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'paypal_order` ADD COLUMN `id_payment` VARCHAR(255)');
    } catch (PrestaShopException $ignored) {}
    return true;
}
