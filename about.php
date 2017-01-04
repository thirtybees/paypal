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

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/../../init.php';

include_once dirname(__FILE__).'/paypal.php';
include_once dirname(__FILE__).'/backward_compatibility/backward.php';

$paypal = new PayPal();

$context = Context::getContext();

$id_lang = (int) ($context->cookie->id_lang ? $context->cookie->id_lang : Configuration::get('PS_LANG_DEFAULT'));
$iso_lang = Tools::strtolower(Language::getIsoById($id_lang));

$paypal->context->smarty->assign('iso_code', $iso_lang);

$display = new BWDisplay();
$display->setTemplate(_PS_MODULE_DIR_.'paypal/views/templates/front/about.tpl');
$display->run();
