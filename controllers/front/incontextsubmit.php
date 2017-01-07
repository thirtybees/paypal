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

require_once dirname(__FILE__).'/../../paypal.php';

use PayPalModule\CallPayPalPlusApi;

class PayPalIncontextsubmitModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public $payerId;

    public $paymentId;

    /** @var \PayPal $module */
    public $module;

    /**
     * Initialize content
     */
    public function initContent()
    {
        $this->payerId = \Tools::getValue('payerID');
        $this->paymentId = \Tools::getValue('paymentID');

        if ($this->payerId && $this->paymentId) {
            $callApiPaypalPlus = new CallPayPalPlusApi();
            $callApiPaypalPlus->getWebProfile();
            $payment = json_decode($callApiPaypalPlus->executePayment($this->payerId, $this->paymentId));
            // TODO: Use the $payment object to create the customer and address first

            if (isset($payment->state) && $payment->state === 'approved') {
                $transaction = [
                    'id_transaction' => $payment->id,
                    'payment_status' => $payment->state,
                    'currency' => $payment->transactions[0]->amount->currency,
                    'payment_date' => date("Y-m-d H:i:s"),
                    'total_paid' => $payment->transactions[0]->amount->total,
                    'id_invoice' => 0,
                    'shipping' => 0,
                ];

                // TODO: find out why secure key has to be forced
                if (!$secureKey = $this->context->cart->secure_key) {
                    $sql = new DbQuery();
                    $sql->select('`secure_key`');
                    $sql->from('cart');
                    $sql->where('`id_cart` = '.(int) $this->context->cart->id);

                    if (!$secureKey = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql)) {
                        $secureKey = md5(Tools::encrypt('lkasjdf'));

                        $this->context->cart->secure_key = $secureKey;
                        $this->context->cart->update();
                    }
                }

                $this->module->validateOrder(
                    $this->context->cart->id,
                    (int) \Configuration::get('PS_OS_PAYMENT'),
                    $payment->transactions[0]->amount->total,
                    $payment->payer->payment_method,
                    null,
                    $transaction,
                    null,
                    false,
                    $secureKey
                );

                header('Content-Type: application/json');
                die(json_encode(['success' => true]));
            } else {
                if (($this->context->customer->is_guest) || $this->context->customer->id == false) {
                    /* If guest we clear the cookie for security reason */
                    $this->context->customer->mylogout();
                }

                header('Content-Type: application/json');
                die(json_encode(['success' => false]));
            }
        }
    }

    /**
     * Set customer information
     * Used to create user account with PayPal account information
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setCustomerInformation($payPalExpressCheckout, $email)
    {
        $customer = new \Customer();
        $customer->email = $email;
        $customer->lastname = $payPalExpressCheckout->result['LASTNAME'];
        $customer->firstname = $payPalExpressCheckout->result['FIRSTNAME'];
        $customer->passwd = \Tools::encrypt(\Tools::passwdGen());

        return $customer;
    }

    /**
     * Set customer address (when not logged in)
     * Used to create user address with PayPal account information
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setCustomerAddress($payPalExpressCheckout, $customer, $id = null)
    {
        $address = new \Address($id);
        $address->id_country = \Country::getByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']);
        if ($id == null) {
            $address->alias = 'Paypal_Address';
        }

        $name = trim($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTONAME']);
        $name = explode(' ', $name);
        if (isset($name[1])) {
            $firstname = $name[0];
            unset($name[0]);
            $lastname = implode(' ', $name);
        } else {
            $lastname = $payPalExpressCheckout->result['LASTNAME'];
            $firstname = $payPalExpressCheckout->result['FIRSTNAME'];
        }

        $address->lastname = $lastname;
        $address->firstname = $firstname;
        $address->address1 = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET'];
        if (isset($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET2'])) {
            $address->address2 = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET2'];
        }

        $address->city = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCITY'];
        if (\Country::containsStates($address->id_country)) {
            $address->id_state = (int) \State::getIdByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTATE'], $address->id_country);
        }

        $address->postcode = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOZIP'];
        if (isset($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOPHONENUM'])) {
            $address->phone = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOPHONENUM'];
        }

        $address->id_customer = $customer->id;

        return $address;
    }
}
