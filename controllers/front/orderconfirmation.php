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

use PayPalModule\PayPalOrder;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class paypalsubmitModuleFrontController
 */
class paypalorderconfirmationModuleFrontController extends ModuleFrontController
{
    /** @var PayPal $module */
    public $module;

    /**
     * Initialize content
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        parent::initContent();

        if (!Tools::isSubmit('id_cart')) {
            $this->showError();

            return;
        }

        // TODO: validate secure_key and id_module

        $order = new Order(Order::getOrderByCartId((int) Tools::getValue('id_cart')));
        $paypalOrder = PayPalOrder::getOrderById($order->id);
        $price = Tools::displayPrice($paypalOrder['total_paid'], $this->context->currency);
        $orderState = new OrderState($order->id);

        if (Validate::isLoadedObject($orderState)) {
            $orderStateMessage = $orderState->template[$this->context->language->id];
        }

        if ((isset($orderStateMessage) && $orderStateMessage == 'payment_error')) {
            $this->context->smarty->assign([
                'logs' => [$this->module->l('An error occurred while processing payment.')],
                'order' => $paypalOrder,
                'price' => $price,
                'message' => $orderStateMessage,
            ]);
            $template = 'error.tpl';
        } else {
            $this->context->smarty->assign([
                'order' => $paypalOrder,
                'price' => $price,
                'reference_order' => Order::getUniqReferenceOf($paypalOrder['id_order']),
                'HOOK_ORDER_CONFIRMATION' => '',
                'HOOK_PAYMENT_RETURN' => $this->module->hookPaymentReturn(['order' => $order]),
            ]);

            $template = 'order-confirmation.tpl';
        }

        $this->setTemplate($template);
    }

    /**
     * @return void
     */
    protected function showError()
    {
        // TODO: implement
    }
}
