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

use PayPalModule\PayPalExpressCheckout;
use PayPalModule\PayPalLogin;
use PayPalModule\PayPalLoginUser;
use PayPalModule\PayPalOrder;

require_once dirname(__FILE__).'/../../paypal.php';

class PayPalExpresscheckoutpaymentModuleFrontController extends \ModuleFrontController
{
    /** @var PayPalExpressCheckout $payPalExpressCheckout */
    public $payPalExpressCheckout;

    /** @var int $idOrder */
    public $idOrder;

    /** @var int $idModule */
    public $idModule;

    /** @var string $payPalKey */
    public $payPalKey;

    /** @var \PayPal $module */
    public $module;

    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        $this->payPalExpressCheckout = new PayPalExpressCheckout(\Tools::getValue('express_checkout'));

        if (\Tools::isSubmit('token') && \Tools::isSubmit('PayerID')) {
            return $this->processPayment();
        }

        return $this->preparePayment();
    }

    /**
     * Prepare to redirect visitor to PayPal website
     */
    public function preparePayment()
    {
        /* Normal payment process */
        if (\Tools::isSubmit('key')) {
            $this->payPalKey = \Tools::getValue('key');
        }

        $ppec = $this->payPalExpressCheckout;

        $idProduct = (int) \Tools::getValue('id_product');
        $productQuantity = (int) \Tools::getValue('quantity');
        $idProductAttribute = \Tools::getValue('id_product_attribute');

        if (($idProduct > 0) && $idProductAttribute !== false && ($productQuantity > 0)) {
            if (!$this->context->cart->add()) {
                $ppec->logs[] = $this->module->l('Cannot create new cart');

                $this->context->smarty->assign(
                    [
                        'logs' => $ppec->logs,
                        'message' => $this->module->l('Error occurred:'),
                        'use_mobile' => (bool) $this->context->getMobileDevice(),
                    ]
                );
            } else {
                $this->context->cookie->id_cart = (int) $this->context->cart->id;
            }

            $this->context->cart->updateQty((int) $productQuantity, (int) $idProduct, (int) $idProductAttribute);
            $this->context->cart->update();
        }

        $loginUser = PayPalLoginUser::getByIdCustomer((int) $this->context->customer->id);

        if ($loginUser && $loginUser->expires_in <= time()) {
            $obj = new PayPalLogin();
            $loginUser = $obj->getRefreshToken();
        }

        /* Set details for a payment */
        $ppec->setExpressCheckout(($loginUser ? $loginUser->access_token : false));

        if (\Tools::getValue('ajax') && $this->module->useInContextCheckout()) {
            $ppec->displayPaypalInContextCheckout();
        }
        if ($ppec->hasSucceedRequest() && !empty($ppec->token)) {
            $ppec->redirectToAPI();
        } else {
            // Display Error and die with this method
            $this->module->displayPayPalAPIError($this->module->l('Error during the preparation of the Express Checkout payment'), $ppec->logs);
        }
    }

    /**
     * Process PayPal payment
     */
    public function processPayment()
    {
        $this->payPalExpressCheckout->idCart = \Context::getContext()->cart->id;
        $this->payPalExpressCheckout->token = \Tools::getValue('token');
        $this->payPalExpressCheckout->payerId = \Tools::getValue('PayerID');

        $ppec = $this->payPalExpressCheckout;

        //If a token exist with payer_id, then we are back from the PayPal API
        /* Get payment infos from paypal */
        $ppec->getExpressCheckout();

        if ($ppec->hasSucceedRequest() && $ppec->token) {
            $ppec->ready = true;
            $address = $customer = null;
            $email = $ppec->result['EMAIL'];

            /* Create Customer if not exist with address etc */
            if ($this->context->cookie->logged) {
                $idCustomer = \Paypal::getPayPalCustomerIdByEmail($email);
                if (!$idCustomer) {
                    \PayPal::addPayPalCustomer($this->context->customer->id, $email);
                }

                $customer = $this->context->customer;
            } elseif ($idCustomer = \Customer::customerExists($email, true)) {
                $customer = new \Customer($idCustomer);
            } else {
                $customer = $this->setCustomerInformation($ppec, $email);
                $customer->add();

                \PayPal::addPayPalCustomer($customer->id, $email);
            }

            if (!$customer->id) {
                $ppec->logs[] = $this->module->l('Cannot create customer');
            }

            if (!isset($ppec->result['PAYMENTREQUEST_0_SHIPTOSTREET']) || !isset($ppec->result['PAYMENTREQUEST_0_SHIPTOCITY'])
                || !isset($ppec->result['SHIPTOZIP']) || !isset($ppec->result['COUNTRYCODE'])
            ) {
                $ppec->redirectToCheckout($customer, ($ppec->type != 'payment_cart'));
            }

            $addresses = $customer->getAddresses($this->context->language->id);
            foreach ($addresses as $address) {
                if ($address['alias'] == 'Paypal_Address') {
                    //If address has already been created
                    $address = new \Address($address['id_address']);
                    break;
                }
            }

            /* Create address */
            if (is_array($address) && isset($address['id_address'])) {
                $address = new \Address($address['id_address']);
            }

            if ((!$address || !$address->id) && $customer->id) {
                //If address does not exists, we create it
                $address = $this->setCustomerAddress($ppec, $customer);
                $address->add();
            } else {
                if ($ppec->type != 'payment_cart') {
                    //We used Express Checkout Shortcut => we override address
                    $address = $this->checkAndModifyAddress($ppec, $customer);
                }
            }

            if ($customer->id && !$address->id) {
                $ppec->logs[] = $this->module->l('Cannot create Address');
                $ppec->ready = false;
            }

            /* Create Order */
            if ($customer->id && $address->id) {
                $this->context->cart->id_customer = $customer->id;
                $this->context->cart->id_address_delivery = $address->id;
                $this->context->cart->id_address_invoice = $address->id;
                $this->context->cart->id_guest = $this->context->cookie->id_guest;

                if (!$this->context->cart->update()) {
                    $ppec->logs[] = $this->module->l('Cannot update existing cart');
                    $ppec->ready = false;
                }
            }
        }

        // if previous steps succeed, the errors array should be empty
        if ($ppec->ready && $ppec->token && $ppec->payerId) {
            /* Check modification on the product cart / quantity */
            // FIXME: broken, find a better way
//            if ($ppec->isProductsListStillRight()) {
            $cart = $this->context->cart;
            $customer = new \Customer((int) $cart->id_customer);

            // When all information are checked before, we can validate the payment to paypal
            // and create the prestashop order
            $ppec->doExpressCheckout();

            if (isset($ppec->result['RedirectRequired']) && $ppec->result['RedirectRequired'] == 'true') {
                $ppec->redirectToAPI();
            }

            $this->validateOrder($customer, $cart, $ppec);

            unset($this->context->cookie->{PayPalExpressCheckout::$cookieName});

            if (!$this->module->currentOrder) {
                $ppec->logs[] = $this->module->l('Cannot create order');
            } else {
                $idOrder = (int) $this->module->currentOrder;
                $order = new \Order($idOrder);
            }

            /* Check payment details to display the appropriate content */
            if (isset($order) && ($ppec->result['ACK'] != 'Failure')) {
                $values = [
                    'key' => $customer->secure_key,
                    'id_module' => (int) $this->module->id,
                    'id_cart' => (int) $cart->id,
                    'id_order' => (int) $this->module->currentOrder,
                ];

                $link = $this->context->link->getModuleLink('paypal', 'submit', $values);
                \Tools::redirect($link);
            } elseif ($ppec->result['ACK'] != 'Failure') {
                $this->context->smarty->assign(
                    [
                    'logs' => $ppec->logs,
                    'message' => $this->module->l('Error occurred:'),
                    ]
                );

                $this->setTemplate('error.tpl');
            }
//            } else {
//                /* If Cart changed, no need to keep the paypal data */
//                unset($this->context->cookie->{PayPalExpressCheckout::$cookieName});
//                $ppec->errors[] = $this->module->l('Cart changed since the last checkout express, please make a new Paypal checkout payment');
//            }
        }

        /* Display result if error occurred */
        if (!$this->context->cart->id) {
            $this->context->cart->delete();
            $ppec->logs[] = $this->module->l('Your cart is empty.');
        }
        $this->context->smarty->assign(
            [
            'logs' => $ppec->logs,
            'message' => $this->module->l('Error occurred:'),
            ]
        );

        $template = 'error.tpl';


        /**
         * Detect if we are using mobile or not
         * Check the 'ps_mobile_site' parameter.
         */
        $this->context->smarty->assign(
            [
            'use_mobile' => (bool) $this->context->getMobileDevice(),
            ]
        );

        $this->setTemplate($template);
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

    /**
     * @param PayPalExpressCheckout $payPalExpressCheckout
     * @param \Customer              $customer
     *
     * @return \Address|bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function checkAndModifyAddress($payPalExpressCheckout, $customer)
    {
        $context = \Context::getContext();
        $customerAddresses = $customer->getAddresses($context->cookie->id_lang);
        $paypalAddress = false;
        if (count($customerAddresses) == 0) {
            $paypalAddress = $this->setCustomerAddress($payPalExpressCheckout, $customer);
        } else {
            foreach ($customerAddresses as $address) {
                if ($address['alias'] == 'Paypal_Address') {
                    //If a PayPal address already exists we use it to override new address from paypal
                    $paypalAddress = $this->setCustomerAddress($payPalExpressCheckout, $customer, $address['id_address']);
                    break;
                } else {
                    //We check if an address exists with the same country / city / street
                    if ($address['firstname'] == $payPalExpressCheckout->result['FIRSTNAME'] &&
                        $address['lastname'] == $payPalExpressCheckout->result['LASTNAME'] &&
                        $address['id_country'] == \Country::getByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']) &&
                        $address['address1'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET'] &&
                        $address['address2'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET2'] &&
                        $address['city'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCITY']
                    ) {
                        $paypalAddress = new \Address($address['id_address']);
                        break;
                    }
                }
            }
        }
        if ($paypalAddress == false) {
            $paypalAddress = $this->setCustomerAddress($payPalExpressCheckout, $customer);
        }

        $paypalAddress->save();

        return $paypalAddress;
    }

    /**
     * Check payment return
     *
     * @param \Customer              $customer
     * @param \Cart                  $cart
     * @param PayPalExpressCheckout $ppec
     */
    protected function validateOrder($customer, $cart, $ppec)
    {
        $amountMatch = $ppec->rightPaymentProcess();
        $orderTotal = (float) $cart->getOrderTotal(true, \Cart::BOTH);

        // Payment succeed
        if ($ppec->hasSucceedRequest() && !empty($ppec->token) && $amountMatch) {
            if ((bool) \Configuration::get('PAYPAL_CAPTURE')) {
                $paymentType = (int) \Configuration::get('PS_OS_PAYPAL');
                $paymentStatus = 'Pending_capture';
                $message = $this->module->l('Pending payment capture.').'<br />';
            } else {
                if (isset($ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                    $paymentStatus = $ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'];
                } else {
                    $paymentStatus = 'Error';
                }

                if ((strcasecmp($paymentStatus, 'Completed') === 0) || (strcasecmp($paymentStatus, 'Completed_Funds_Held') === 0)) {
                    $paymentType = (int) \Configuration::get('PS_OS_PAYMENT');
                    $message = $this->module->l('Payment accepted.').'<br />';
                } elseif (\Tools::getValue('banktxnpendingurl') && \Tools::getValue('banktxnpendingurl') == 'true') {
                    $paymentType = (int) \Configuration::get('PS_OS_PAYPAL');
                    $message = $this->module->l('eCheck').'<br />';
                } elseif (strcasecmp($paymentStatus, 'Pending') === 0) {
                    $paymentType = (int) \Configuration::get('PS_OS_PAYPAL');
                    $message = $this->module->l('Pending payment confirmation.').'<br />';
                } else {
                    $paymentType = (int) \Configuration::get('PS_OS_ERROR');
                    $message = implode('<br />', $ppec->logs).'<br />';
                }
            }
        } else {
            // Payment error
            //Check if error is 10486, if it is redirect user to paypal
            if ($ppec->result['L_ERRORCODE0'] == 10486) {
                $ppec->redirectToAPI();
            }

            $paymentStatus = isset($ppec->result['PAYMENTINFO_0_PAYMENTSTATUS']) ? $ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'] : false;
            $paymentType = (int) \Configuration::get('PS_OS_ERROR');

            if ($amountMatch) {
                $message = implode('<br />', $ppec->logs).'<br />';
            } else {
                $message = $this->module->l('Price paid on paypal is not the same that on Thirty Bees.').'<br />';
            }

        }

        $transaction = PayPalOrder::getTransactionDetails($ppec, $paymentStatus);
        $this->context->cookie->id_cart = $cart->id;

        $this->module->validateOrder(
            (int) $cart->id,
            $paymentType,
            $orderTotal,
            'PayPal',
            $message,
            $transaction,
            (int) $cart->id_currency,
            false,
            $customer->secure_key,
            $this->context->shop
        );
    }
}
