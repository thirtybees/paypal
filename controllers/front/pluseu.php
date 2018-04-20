<?php
/**
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
 */

use PayPalModule\PayPalRestApi;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalPlusEUModuleFrontController
 */
class PayPalPlusEUModuleFrontController extends \ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;
    // @codingStandardsIgnoreEnd
    /** @var \PayPal $module */
    public $module;
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * @return void
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $rest = new PayPalRestApi();
        $payment = $rest->createPayment(
            $this->context->link->getModuleLink($this->module->name, 'expresscheckoutconfirm', [], true),
            $this->context->link->getModuleLink($this->module->name, 'pluscancel', [], true),
            PayPalRestApi::PLUS_PROFILE
        );

        $approvalUrl = '';
        if ($payment->id) {
            foreach ($payment->links as $link) {
                if ($link->rel === 'approval_url') {
                    $approvalUrl = $link->href;
                    break;
                }
            }
        }

        $this->context->smarty->assign([
            'approval_url' => $approvalUrl,
            'mode'         => \Configuration::get(\PayPal::LIVE) ? 'live' : 'sandbox',
            'language'     => $this->module->getLocalePayPalPlus(),
            'country'      => $this->module->getCountryCode(),
        ]);

        if ($approvalUrl) {
            $this->setTemplate('paypal_plus_payment_eu.tpl');
        } else {
            Tools::redirectLink($this->context->link->getPageLink('order', Tools::usingSecureMode(), null, ['step' => 3]));
        }
    }
}
