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

class PayPalPlussubmitModuleFrontController extends \ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $display_column_left */
    public $display_column_left = false;

    /** @var bool $display_column_right */
    public $display_column_right = false;
    // @codingStandardsIgnoreEnd

    /** @var int $idModule */
    public $idModule;

    /** @var int $idOrder */
    public $idOrder;

    /** @var int $idCart */
    public $idCart;

    /** @var string $paymentId */
    public $paymentId;

    /** @var \PayPal $module */
    public $module;

    /** @var string $token */
    public $token;

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

        $this->idModule = (int) \Tools::getValue('id_module');
        $this->idCart = \Tools::getValue('id_cart');
        $this->paymentId = \Tools::getValue('paymentId');
        $this->token = \Tools::getValue('token');

        if ($this->idCart && $this->paymentId && $this->token) {
            $callApiPaypalPlus = new CallApiPayPalPlus();
            $payment = json_decode($callApiPaypalPlus->lookUpPayment($this->paymentId));

            if (isset($payment->state)) {
                $this->context->smarty->assign('state', $payment->state);

                $transaction = [
                    'id_transaction' => $payment->id,
                    'payment_status' => $payment->state,
                    'currency' => $payment->transactions[0]->amount->currency,
                    'payment_date' => date("Y-m-d H:i:s"),
                    'total_paid' => $payment->transactions[0]->amount->total,
                    'id_invoice' => 0,
                    'shipping' => 0,
                ];

                switch ($payment->state) {
                    case 'created':
                        /* LookUp OK */
                        /* Affichage bouton confirmation */

                        $this->context->smarty->assign([
                            'PayerID' => $payment->payer->payer_info->payer_id,
                            'paymentId' => $this->paymentId,
                            'id_cart' => $this->idCart,
                            'totalAmount' => \Tools::displayPrice(\Cart::getTotalCart($this->idCart)),
                            'linkSubmitPlus' => $this->context->link->getModuleLink('paypal', 'plussubmit', [], \Tools::usingSecureMode()),
                        ]);
                        break;
                    case 'canceled':
                        /* LookUp cancel */
                        $this->module->validateOrder(
                            $this->idCart,
                            $this->getOrderStatus('order_canceled'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );
                        break;
                    default:
                        /* Payment error */
                        $this->module->validateOrder(
                            $this->idCart,
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
        if (\Validate::isUnsignedId($this->idOrder) && \Validate::isUnsignedId($this->idModule)) {
            $order = new \Order((int) $this->idOrder);
            $currency = new \Currency((int) $order->id_currency);

            if (\Validate::isLoadedObject($order)) {
                $params = [];
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
        $ajax = \Tools::getValue('ajax');
        $return = [];
        if (!$ajax) {
            $return['error'][] = $this->module->l('An error occured during the payment');
            echo json_encode($return);
            die();
        }

        $idCart = \Tools::getValue('id_cart');
        $payerID = \Tools::getValue('payerID');
        $paymentId = \Tools::getValue('paymentId');
        $submit = \Tools::getValue('submit');

        if ((!empty($idCart) && $this->context->cart->id == $idCart) &&
            !empty($payerID) &&
            !empty($paymentId) &&
            !empty($submit)) {
            $callApiPaypalPlus = new CallApiPayPalPlus();
            $payment = json_decode($callApiPaypalPlus->executePayment($payerID, $paymentId));

            if (isset($payment->state)) {
                $paypal = \Module::getInstanceByName('paypal');

                $transaction = [
                    'id_transaction' => $payment->transactions[0]->related_resources[0]->sale->id,
                    'payment_status' => $payment->state,
                    'total_paid' => $payment->transactions[0]->amount->total,
                    'id_invoice' => 0,
                    'shipping' => 0,
                    'currency' => $payment->transactions[0]->amount->currency,
                    'payment_date' => date("Y-m-d H:i:s"),
                ];

                if ($submit == 'confirmPayment') {
                    if ($payment->state == 'approved') {
                        $paypal->validateOrder(
                            $this->idCart,
                            $this->getOrderStatus('payment'),
                            $payment->transactions[0]->amount->total,
                            $payment->payer->payment_method,
                            null,
                            $transaction
                        );
                        $return['success'][] = $this->module->l('Your payment has been taken into account');
                    } else {
                        $paypal->validateOrder(
                            $this->idCart,
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
                        $this->idCart,
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
            return \Hook::exec('displayPaymentReturn', $params, (int) $this->module->id);
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
            return \Hook::exec('displayOrderConfirmation', $params);
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
        $sql = new \DbQuery();
        $sql->select('`id_order_state`');
        $sql->from('order_state_lang');
        $sql->where('`template` = \''.pSQL($template).'\'');
        $sql->where('`id_lang` = '.(int) $this->context->language->id);

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}
