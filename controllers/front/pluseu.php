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

use PayPalModule\PayPalRestApi;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/../../paypal.php';

/**
 * Class paypalpluseuModuleFrontController
 */
class paypalpluseuModuleFrontController extends \ModuleFrontController
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
     * PayPalSubmitplusModuleFrontController constructor.
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct()
    {
        parent::__construct();
        $this->context = \Context::getContext();
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function initContent()
    {
        parent::initContent();

        $rest = new PayPalRestApi();
        $payment = $rest->createPayment(
            $this->context->link->getModuleLink($this->module->name, 'plussubmit', [], true),
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
            'mode' => \Configuration::get(\PayPal::LIVE) ? 'live' : 'sandbox',
            'language' => $this->module->getLocalePayPalPlus(),
            'country' => $this->module->getCountryCode(),
        ]);

        if ($approvalUrl) {
            $this->setTemplate('paypal_plus_payment_eu.tpl');
        } else {
            // FIXME: file is missing
            $this->setTemplate('paypal_plus_payment_eu_failed.tpl');
        }
    }
}
