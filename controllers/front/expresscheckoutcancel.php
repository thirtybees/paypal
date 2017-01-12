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

use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalRestApi;

require_once dirname(__FILE__).'/../../paypal.php';

/**
 * Class paypalexpresscheckoutcancelModuleFrontController
 */
class paypalexpresscheckoutcancelModuleFrontController extends \ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        return $this->cancelExpressCheckout();
    }

    /**
     * Cancel Express Checkout
     */
    public function cancelExpressCheckout()
    {
        unset($this->context->cookie->express_checkout);

        \Tools::redirectLink($this->context->link->getPageLink('order', true));
    }
}