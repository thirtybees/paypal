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

if (!defined('_TB_VERSION_')) {
    exit;
}

use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalRestApi;

/**
 * Class PayPalexpresscheckoutModuleFrontController
 */
class paypalexpresscheckoutModuleFrontController extends \ModuleFrontController
{
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
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();

        if (\Tools::isSubmit('paymentId') && \Tools::isSubmit('PayerID')) {
            $this->processPayment();

            return;
        }

        $this->preparePayment();

        return;
    }

    /**
     * Prepare to redirect visitor to PayPal website
     */
    public function preparePayment()
    {
        $rest = new PayPalRestApi();
        $payment = $rest->createPayment(false, false, PayPalRestApi::STANDARD_PROFILE);

        if (isset($payment->id) && $payment->id) {
            foreach ($payment->links as $link) {
                if ($link->rel === 'approval_url') {
                    Tools::redirectLink($link->href);
                }
            }
        }
    }

    /**
     * Process PayPal payment
     */
    public function processPayment()
    {
        $cart = $this->context->cart;
        $paymentId = Tools::getValue('paymentId');
        $payerId = Tools::getValue('PayerID');

        $rest = new PayPalRestApi();
        $payment = $rest->executePayment($payerId, $paymentId);
        if (isset($payment->name) && \Tools::strtoupper($payment->name) === 'PAYMENT_ALREADY_DONE') {
            $payment = $rest->lookUpPayment($paymentId);
        }

        $ready = false;
        if (isset($payment->state) && \Tools::strtolower($payment->state) == 'approved') {
            $ready = true;
            $address = $customer = null;
            $email = $payment->payer->payer_info->email;

            /* Create Customer if not exist with address etc */
            if ($this->context->cookie->logged) {
                $idCustomer = PaypalCustomer::getPayPalCustomerIdByEmail($email);
                if (!$idCustomer) {
                    $ppc = new PayPalCustomer();
                    $ppc->id_customer = $this->context->customer->id;
                    $ppc->paypal_email = $email;
                    $ppc->add();
                }

                $customer = $this->context->customer;
            } elseif ($idCustomer = \Customer::customerExists($email, true)) {
                $customer = new \Customer($idCustomer);
            } else {
                $customer = $this->setCustomerInformation($payment, $email);
                $customer->add();

                $ppc = new PayPalCustomer();
                $ppc->id_customer = $this->context->customer->id;
                $ppc->paypal_email = $email;
                $ppc->add();
            }

//            if (!$customer->id) {
//                $ppec->logs[] = $this->module->l('Cannot create customer');
//            }

            $shippingAddress = $payment->payer->payer_info->shipping_address;
            if (!isset($shippingAddress->line1) || !isset($shippingAddress->city)
                || !isset($shippingAddress->postal_code) || !isset($shippingAddress->country_code)
            ) {
                Tools::redirectLink($this->context->link->getPageLink('order'));
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
                $address = $this->setCustomerAddress($payment, $customer);
                $address->add();
            }
//            else {
//                if ($ppec->type != 'payment_cart') {
//                    We used Express Checkout Shortcut => we override address
//                    $address = $this->checkAndModifyAddress($ppec, $customer);
//                }
//            }

//            if ($customer->id && !$address->id) {
//                $ppec->logs[] = $this->module->l('Cannot create Address');
//                $ppec->ready = false;
//            }

            /* Create Order */
            if ($customer->id && $address->id) {
                $cart->id_customer = $customer->id;
                $cart->id_address_delivery = $address->id;
                $cart->id_address_invoice = $address->id;
                $cart->id_guest = $this->context->cookie->id_guest;

                if (!$this->context->cart->update()) {
//                    $ppec->logs[] = $this->module->l('Cannot update existing cart');
                    $ready = false;
                }
            }
        }

        // if previous steps succeed, the errors array should be empty
        if ($ready) {
            /* Check modification on the product cart / quantity */
            // FIXME: broken, find a better way
//            if ($ppec->isProductsListStillRight()) {
            $customer = new \Customer((int) $cart->id_customer);

            // When all information are checked before, we can validate the payment to paypal
            // and create the prestashop order

            $this->validateOrder($customer, $cart, $payment);

            unset($this->context->cookie->express_checkout);

            if (!$this->module->currentOrder) {
//                $ppec->logs[] = $this->module->l('Cannot create order');
            } else {
                $idOrder = (int) $this->module->currentOrder;
                $order = new \Order($idOrder);
            }

            /* Check payment details to display the appropriate content */
            if (isset($order) && (\Tools::strtolower($payment->state) !== 'approved')) {
                $values = [
                    'key' => $customer->secure_key,
                    'id_module' => (int) $this->module->id,
                    'id_cart' => (int) $cart->id,
                    'id_order' => (int) $this->module->currentOrder,
                ];

                $link = $this->context->link->getModuleLink('paypal', 'submit', $values);
                \Tools::redirect($link);
            } elseif (\Tools::strtolower($payment->state) !== 'approved') {
                $this->context->smarty->assign([
                    'logs' => [],
                    'message' => $this->module->l('Error occurred:'),
                ]);

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
//            $ppec->logs[] = $this->module->l('Your cart is empty.');
        }
        $this->context->smarty->assign(
            [
            'logs' => [],
            'message' => $this->module->l('Error occurred:'),
            ]
        );

        $template = 'error.tpl';


        /**
         * Detect if we are using mobile or not
         * Check the 'ps_mobile_site' parameter.
         */
        $this->context->smarty->assign([
            'use_mobile' => (bool) $this->context->getMobileDevice(),
        ]);

        $this->setTemplate($template);
    }

    /**
     * Set customer information
     * Used to create user account with PayPal account information
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     *
     * @param \stdClass $payment
     * @param string    $email
     *
     * @return \Customer
     */
    protected function setCustomerInformation($payment, $email)
    {
        $customer = new \Customer();
        $customer->email = $email;
        $customer->firstname = $payment->payer->payer_info->first_name;
        $customer->lastname = $payment->payer->payer_info->last_name;
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
     *
     * @param \stdClass $payment
     * @param \Customer $customer
     * @param int       $idAddress
     *
     * @return Address
     */
    protected function setCustomerAddress($payment, $customer, $idAddress = null)
    {
        $payerInfo = $payment->payer->payer_info;
        $shippingAddress = $payerInfo->shipping_address;

        $address = new \Address($idAddress);
        $address->id_country = \Country::getByIso($shippingAddress->country_code);
        if ($idAddress == null) {
            $address->alias = 'Paypal_Address';
        }

        $name = trim($shippingAddress->recipient_name);
        $name = explode(' ', $name);
        if (isset($name[1])) {
            $firstname = $name[0];
            unset($name[0]);
            $lastname = implode(' ', $name);
        } else {
            $firstname = $payerInfo->first_name;
            $lastname = $payerInfo->last_name;
        }

        $address->lastname = $lastname;
        $address->firstname = $firstname;
        $address->address1 = $shippingAddress->line1;
        if (isset($shippingAddress->line2)) {
            $address->address2 = $shippingAddress->line2;
        }

        $address->city = $shippingAddress->city;
        if (\Country::containsStates($address->id_country)) {
            $address->id_state = (int) \State::getIdByIso($shippingAddress->state, $address->id_country);
        }

        $address->postcode = $shippingAddress->postal_code;
        $address->phone = '0000000000';

        $address->id_customer = $customer->id;

        return $address;
    }

    /**
     * @param \stdClass $payment
     * @param \Customer $customer
     *
     * @return \Address|bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function checkAndModifyAddress($payment, $customer)
    {
        $context = \Context::getContext();
        $customerAddresses = $customer->getAddresses($context->cookie->id_lang);
        $paypalAddress = false;
        if (count($customerAddresses) == 0) {
            $paypalAddress = $this->setCustomerAddress($payment, $customer);
        } else {
            foreach ($customerAddresses as $address) {
                if ($address['alias'] == 'Paypal_Address') {
                    //If a PayPal address already exists we use it to override new address from paypal
                    $paypalAddress = $this->setCustomerAddress($payment, $customer, $address['id_address']);
                    break;
                } else {
                    $payerInfo = $payment->payer->payer_info;
                    $shippingAddress = $payerInfo->shipping_address;
                    //We check if an address exists with the same country / city / street
                    if ($address['firstname'] == $payerInfo->first_name &&
                        $address['lastname'] == $payerInfo->last_name &&
                        $address['id_country'] == \Country::getByIso($shippingAddress->country_code) &&
                        $address['address1'] == $shippingAddress->line1 &&
                        $address['address2'] == isset($shippingAddress->line2) ? $shippingAddress->line2 : null &&
                        $address['city'] == $shippingAddress->city
                    ) {
                        $paypalAddress = new \Address($address['id_address']);
                        break;
                    }
                }
            }
        }
        if ($paypalAddress == false) {
            $paypalAddress = $this->setCustomerAddress($payment, $customer);
        }

        $paypalAddress->save();

        return $paypalAddress;
    }

    /**
     * Check payment return
     *
     * @param \Customer $customer
     * @param \Cart     $cart
     * @param \stdClass $payment
     */
    protected function validateOrder($customer, $cart, $payment)
    {
        $transactionAmount = (float) $payment->transactions[0]->amount->total;
        $orderTotal = (float) $cart->getOrderTotal(true, \Cart::BOTH);

        // Payment succeed
        if (\Tools::strtoupper($payment->state) === 'VERIFIED' && $transactionAmount == $orderTotal) {
            if ((bool) \Configuration::get(\PayPal::IMMEDIATE_CAPTURE)) {
                $paymentType = (int) \Configuration::get('PS_OS_PAYPAL');
                $message = $this->module->l('Pending payment capture.').'<br />';
            } else {
                if (isset($payment->state)) {
                    $paymentStatus = $payment->payer->status;
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
//                    $message = implode('<br />', $ppec->logs).'<br />';
                }
            }
        } else {
            // FIXME: proper error handling
//            $paymentStatus = isset($ppec->result['PAYMENTINFO_0_PAYMENTSTATUS']) ? $ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'] : false;
//            $paymentType = (int) \Configuration::get('PS_OS_ERROR');
//
//            if ($amountMatch) {
//                $message = implode('<br />', $ppec->logs).'<br />';
//            } else {
//                $message = $this->module->l('Price paid on paypal is not the same that on Thirty Bees.').'<br />';
//            }
        }

        $transaction = PayPalOrder::getTransactionDetails($payment);
        $this->context->cookie->id_cart = $cart->id;

        $this->module->validateOrder(
            (int) $cart->id,
            isset($paymentType) ? $paymentType : \Configuration::get('PS_OS_PAYMENT'),
            $orderTotal,
            'PayPal',
            isset($message) ? $message : '',
            $transaction,
            (int) $cart->id_currency,
            false,
            $customer->secure_key,
            $this->context->shop
        );

        Tools::redirectLink($this->context->link->getPageLink('order-confirmation', true, '&id_cart='.$cart->id.'&id_module='.$this->module->id.'&key='.$customer->secure_key));
    }

    /**
     * @param \Customer $customer
     * @param bool      $redirect
     */
    public function redirectToCheckout(\Customer $customer, $redirect = false)
    {
        $this->ready = true;

        $context = $this->context;
        $context->cookie->id_customer = (int) $customer->id;
        $context->cookie->customer_lastname = $customer->lastname;
        $context->cookie->customer_firstname = $customer->firstname;
        $context->cookie->passwd = $customer->passwd;
        $context->cookie->email = $customer->email;
        $context->cookie->is_guest = $customer->isGuest();
        $context->cookie->logged = 1;

        \Hook::exec('authentication');

        if ($redirect) {
            $link = $context->link->getPageLink('order.php', false, null, ['step' => '1']);
            \Tools::redirectLink($link);
            exit(0);
        }
    }
}
