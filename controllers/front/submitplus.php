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

use PayPalModule\CallApiPayPalPlus;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/../../paypal.php';

class PayPalSubmitplusModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;

    /** @var PayPal $module */
    public $module;

    /** @var int $id_module */
    public $id_module;

    /** @var int $id_order */
    public $id_order;

    /** @var int $id_cart */
    public $id_cart;

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
        $this->context = Context::getContext();
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function initContent()
    {
        parent::initContent();

        $this->id_module = (int) Tools::getValue('id_module');
        $this->idCart = Tools::getValue('id_cart');
        $this->paymentId = Tools::getValue('paymentId');
        $this->token = Tools::getValue('token');

        if (!empty($this->id_cart) && !empty($this->paymentId) && !empty($this->token)) {
            $callApiPaypalPlus = new CallApiPayPalPlus();
            $payment = json_decode($callApiPaypalPlus->lookUpPayment($this->paymentId));

            if (isset($payment->state)) {
                $this->context->smarty->assign('state', $payment->state);

                $transaction = array(
                    'id_transaction' => $payment->id,
                    'payment_status' => $payment->state,
                    'currency' => $payment->transactions[0]->amount->currency,
                    'payment_date' => date("Y-m-d H:i:s"),
                    'total_paid' => $payment->transactions[0]->amount->total,
                    'id_invoice' => 0,
                    'shipping' => 0,
                );

                switch ($payment->state) {
                    case 'created':
                        /* LookUp OK */
                        /* Affichage bouton confirmation */

                        $this->context->smarty->assign(array(
                            'PayerID' => $payment->payer->payer_info->payer_id,
                            'paymentId' => $this->paymentId,
                            'id_cart' => $this->id_cart,
                            'totalAmount' => Tools::displayPrice(Cart::getTotalCart($this->id_cart)),
                            'linkSubmitPlus' => $this->context->link->getModuleLink('paypal', 'submitplus'),
                        ));
                        break;

                    case 'canceled':
                        /* LookUp cancel */
                        $this->module->validateOrder(
                            $this->id_cart,
                            $this->getOrderStatus('order_canceled'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );
                        break;

                    default:
                        /* Erreur de payment */
                        $this->module->validateOrder(
                            $this->id_cart,
                            $this->getOrderStatus('payment_error'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );

                        break;
                }
            } else {
                $this->context->smarty->assign('state', 'failed');
            }

        } else {
            $this->context->smarty->assign('state', 'failed');
        }

        if (($this->context->customer->is_guest) || $this->context->customer->id == false) {

            /* If guest we clear the cookie for security reason */
            $this->context->customer->mylogout();
        }

        $this->module->assignCartSummary();

        if ($this->context->getMobileDevice() == true) {
            $this->setTemplate('order-confirmation-plus-mobile.tpl');
        } else {
            $this->setTemplate('order-confirmation-plus.tpl');
        }

    }

    /**
     * @return array|bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function displayHook()
    {
        if (Validate::isUnsignedId($this->id_order) && Validate::isUnsignedId($this->id_module)) {
            $order = new Order((int) $this->id_order);
            $currency = new Currency((int) $order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params = array();
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;
                $params['currency'] = $currency->sign;
                $params['total_to_pay'] = $order->getOrdersTotalPaid();

                return $params;
            }
        }

        return false;
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayAjax()
    {
        $ajax = Tools::getValue('ajax');
        $return = array();
        if (!$ajax) {
            $return['error'][] = $this->module->l('An error occured during the payment');
            echo json_encode($return);
            die();
        }

        $idCart = Tools::getValue('id_cart');
        $payerID = Tools::getValue('payerID');
        $paymentId = Tools::getValue('paymentId');
        $submit = Tools::getValue('submit');

        if ((!empty($idCart) && $this->context->cart->id == $idCart) &&
            !empty($payerID) &&
            !empty($paymentId) &&
            !empty($submit)) {
            $callApiPaypalPlus = new CallApiPayPalPlus();
            $payment = json_decode($callApiPaypalPlus->executePayment($payerID, $paymentId));

            if (isset($payment->state)) {
                $paypal = new PayPal();

                $transaction = array(
                    'id_transaction' => $payment->transactions[0]->related_resources[0]->sale->id,
                    'payment_status' => $payment->state,
                    'total_paid' => $payment->transactions[0]->amount->total,
                    'id_invoice' => 0,
                    'shipping' => 0,
                    'currency' => $payment->transactions[0]->amount->currency,
                    'payment_date' => date("Y-m-d H:i:s"),
                );

                if ($submit == 'confirmPayment') {
                    if ($payment->state == 'approved') {
                        $paypal->validateOrder(
                            $this->id_cart,
                            $this->getOrderStatus('payment'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );
                        $return['success'][] = $this->module->l('Your payment has been taken into account');
                    } else {
                        $paypal->validateOrder(
                            $this->id_cart,
                            $this->getOrderStatus('payment_error'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );
                        $return['error'][] = $this->module->l('An error occurred during the payment');
                    }
                } elseif ($submit == 'confirmCancel') {
                    $paypal->validateOrder(
                        $this->id_cart,
                        $this->getOrderStatus('order_canceled'),
                        $payment->transactions[0]->amount->total,
                        $payment->payer->payment_method,
                        null,
                        $transaction
                    );
                    $return['success'][] = $this->module->l('Your order has been canceled');
                } else {
                    $return['error'][] = $this->module->l('An error occurred during the payment');
                }

            } else {
                $return['error'][] = $this->module->l('An error occurred during the payment');
            }

        } else {
            $return['error'][] = $this->module->l('An error occurred during the payment');
        }

        echo json_encode($return);
        die();
    }

    /**
     * Execute the hook displayPaymentReturn
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayPaymentReturn()
    {
        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayPaymentReturn', $params, (int) $this->module->id);
        }

        return false;
    }

    /**
     * Execute the hook displayOrderConfirmation
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayOrderConfirmation()
    {
        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayOrderConfirmation', $params);
        }

        return false;
    }

    /**
     * @param $template
     *
     * @return false|null|string
     */
    public function getOrderStatus($template)
    {
        $sql = new DbQuery();
        $sql->select('`id_order_state`');
        $sql->from('order_state_lang');
        $sql->where('`template` = \''.pSQL($template).'\'');
        $sql->where('`id_lang` = '.(int) $this->context->language->id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}