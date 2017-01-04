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

include_once dirname(__FILE__).'/../../../config/config.inc.php';
include_once dirname(__FILE__).'/../../../init.php';
include_once dirname(__FILE__).'/../paypal.php';

// Ajax query
$quantity = Tools::getValue('get_qty');

if (Configuration::get('PS_CATALOG_MODE') == 1) {
    die('0');
}

if ($quantity && $quantity > 0) {
    /* Ajax response */
    $id_product = (int) Tools::getValue('id_product');
    $id_product_attribute = (int) Tools::getValue('id_product_attribute');
    $product_quantity = Product::getQuantity($id_product, $id_product_attribute);
    $product = new Product($id_product);

    if (!$product->available_for_order) {
        die('0');
    }

    if ($product_quantity > 0) {
        die('1');
    }

    if ($product_quantity <= 0 && $product->isAvailableWhenOutOfStock((int) $product->out_of_stock)) {
        die('1');
    }

}
die('0');
