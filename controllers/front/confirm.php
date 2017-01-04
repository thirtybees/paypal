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

/**
 * @since 1.5.0
 */

class PayPalConfirmModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function initContent()
    {
        if (!$this->context->customer->isLogged(true) || empty($this->context->cart)) {
            Tools::redirect('index.php');
        }

        parent::initContent();

        $this->paypal = new PayPal();
        $this->context = Context::getContext();
        $this->id_module = (int) Tools::getValue('id_module');

        $currency = new Currency((int) $this->context->cart->id_currency);

        $this->module->assignCartSummary();

        $this->context->smarty->assign(array(
            'form_action' => PayPal::getShopDomainSsl(true, true)._MODULE_DIR_.$this->paypal->name.'/express_checkout/payment.php',
        ));

        $this->setTemplate('order-summary.tpl');
    }
}
