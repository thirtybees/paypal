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

if (version_compare(_PS_VERSION_, '1.5', '<')) {
    require_once _PS_ROOT_DIR_.'/controllers/OrderConfirmationController.php';
}

/**
 * 1.4 Retro-compatibility class
 */
class PayPalExpressCheckoutSubmit extends OrderConfirmationControllerCore
{
    /**
     * PayPalExpressCheckoutSubmit constructor.
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct()
    {
        $this->paypal = new PayPal();
        $this->context = $this->paypal->context;

        parent::__construct();

        $this->run();
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayContent()
    {
        $id_order = (int) Tools::getValue('id_order');

        $order = new Order($id_order);
        $paypal_order = PayPalOrder::getOrderById($id_order);

        $price = Tools::displayPrice($paypal_order['total_paid'], $this->context->currency);

        $order_state = new OrderState($id_order);

        if ($order_state) {
            $order_state_message = $order_state->template[$this->context->language->id];
        }

        if (!$order || !$order_state || (isset($order_state_message) && ($order_state_message == 'payment_error'))) {
            $this->context->smarty->assign(
                array(
                    'logs' => array($this->paypal->l('An error occurred while processing payment.')),
                    'order' => $paypal_order,
                    'price' => $price,
                )
            );

            if (isset($order_state_message) && $order_state_message) {
                $this->context->smarty->assign('message', $order_state_message);
            }

            $template = 'error.tpl';
        } else {
            $this->context->smarty->assign(
                array(
                    'order' => $paypal_order,
                    'price' => $price,
                )
            );

            if (version_compare(_PS_VERSION_, '1.5', '>')) {

                $this->context->smarty->assign(array(
                    'reference_order' => Order::getUniqReferenceOf($paypal_order['id_order']),
                ));
            }

            $template = 'order-confirmation.tpl';
        }

        $this->context->smarty->assign('use_mobile', (bool) $this->paypal->useMobile());
        echo $this->paypal->fetchTemplate($template);
    }
}
