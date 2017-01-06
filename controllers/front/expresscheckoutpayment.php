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

class PayPalExpresscheckoutpaymentModuleFrontController extends ModuleFrontController
{
    /** @var PayPalExpressCheckout $payPalExpressCheckout */
    public $payPalExpressCheckout;

    public $idOrder;

    public $idModule;

    public $payPalKey;

    /** @var string $requestType */
    public $requestType;

    public function initContent()
    {
        parent::initContent();

        return $this->processPayment();
    }

    public function processPayment()
    {
        /* Normal payment process */
        $this->requestType = Tools::getValue('express_checkout');

        $this->payPalExpressCheckout = new PayPalExpressCheckout($this->requestType);
        $this->payPalExpressCheckout->idCart = Context::getContext()->cart->id;

        if (Tools::isSubmit('id_order')) {
            $this->idOrder = Tools::getValue('id_order');
        }

        if (Tools::isSubmit('key')) {
            $this->payPalKey = Tools::getValue('key');
        }

        if (Tools::isSubmit('token')) {
            $this->payPalExpressCheckout->token = Tools::getValue('token');
        }

        if (Tools::isSubmit('PayerID')) {
            $this->payPalExpressCheckout->payerId = Tools::getValue('PayerID');
        }

        $ppec = $this->payPalExpressCheckout;
        if ($this->requestType && $ppec->type) {
            $idProduct = (int) Tools::getValue('id_product');
            $productQuantity = (int) Tools::getValue('quantity');
            $idProductAttribute = Tools::getValue('id_product_attribute');

            if (($idProduct > 0) && $idProductAttribute !== false && ($productQuantity > 0)) {
                $this->setContextData($ppec);

                if (!$ppec->context->cart->add()) {
                    $ppec->logs[] = $ppec->l('Cannot create new cart');

                    $ppec->context->smarty->assign(
                        array(
                            'logs' => $ppec->logs,
                            'message' => $ppec->l('Error occurred:'),
                            'use_mobile' => (bool) $ppec->useMobile(),
                        )
                    );
                } else {
                    $ppec->context->cookie->id_cart = (int) $this->context->cart->id;
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

            if (Tools::getValue('ajax') && $ppec->useInContextCheckout()) {
                $ppec->displayPaypalInContextCheckout();
            }
            if ($ppec->hasSucceedRequest() && !empty($ppec->token)) {
                $ppec->redirectToAPI();
            } else {
                // Display Error and die with this method
                $ppec->displayPayPalAPIError($ppec->l('Error during the preparation of the Express Checkout payment'), $ppec->logs);
            }
        } elseif ($ppec->token) {
            //If a token exist with payer_id, then we are back from the PayPal API
            /* Get payment infos from paypal */
            $ppec->getExpressCheckout();

            if ($ppec->hasSucceedRequest() && !empty($ppec->token)) {
                $address = $customer = null;
                $email = $ppec->result['EMAIL'];

                /* Create Customer if not exist with address etc */
                if ($ppec->context->cookie->logged) {
                    $idCustomer = Paypal::getPayPalCustomerIdByEmail($email);
                    if (!$idCustomer) {
                        PayPal::addPayPalCustomer($ppec->context->customer->id, $email);
                    }

                    $customer = $ppec->context->customer;
                } elseif ($idCustomer = Customer::customerExists($email, true)) {
                    $customer = new Customer($idCustomer);
                } else {
                    $customer = $this->setCustomerInformation($ppec, $email);
                    $customer->add();

                    PayPal::addPayPalCustomer($customer->id, $email);
                }

                if (!$customer->id) {
                    $ppec->logs[] = $ppec->l('Cannot create customer');
                }

                if (!isset($ppec->result['PAYMENTREQUEST_0_SHIPTOSTREET']) || !isset($ppec->result['PAYMENTREQUEST_0_SHIPTOCITY'])
                    || !isset($ppec->result['SHIPTOZIP']) || !isset($ppec->result['COUNTRYCODE'])
                ) {
                    $ppec->redirectToCheckout($customer, ($ppec->type != 'payment_cart'));
                }

                $addresses = $customer->getAddresses($ppec->context->language->id);
                foreach ($addresses as $address) {
                    if ($address['alias'] == 'Paypal_Address') {
                        //If address has already been created
                        $address = new Address($address['id_address']);
                        break;
                    }
                }

                /* Create address */
                if (is_array($address) && isset($address['id_address'])) {
                    $address = new Address($address['id_address']);
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
                    $ppec->logs[] = $ppec->l('Cannot create Address');
                }

                /* Create Order */
                if ($customer->id && $address->id) {
                    $ppec->context->cart->id_customer = $customer->id;
                    $ppec->context->cart->id_address_delivery = $address->id;
                    $ppec->context->cart->id_address_invoice = $address->id;
                    $ppec->context->cart->id_guest = $ppec->context->cookie->id_guest;

                    if (!$ppec->context->cart->update()) {
                        $ppec->logs[] = $ppec->l('Cannot update existing cart');
                    } else {
                        $paymentCart = (bool) ($ppec->type != 'payment_cart');
                        $ppec->redirectToCheckout($customer, $paymentCart);
                    }
                }
            }
        }

        /* If Previous steps succeed, ready (means 'ready to pay') will be set to true */
        if ($ppec->ready && !empty($ppec->token) && (Tools::isSubmit('confirmation') || $ppec->type == 'payment_cart')) {
            /* Check modification on the product cart / quantity */
            if ($ppec->isProductsListStillRight()) {
                $cart = $ppec->context->cart;
                $customer = new Customer((int) $cart->id_customer);

                // When all information are checked before, we can validate the payment to paypal
                // and create the prestashop order
                $ppec->doExpressCheckout();

                if (isset($ppec->result['RedirectRequired']) && $ppec->result['RedirectRequired'] == 'true') {
                    $ppec->redirectToAPI();
                }

                $this->validateOrder($customer, $cart, $ppec);

                unset($ppec->context->cookie->{PayPalExpressCheckout::$cookieName});

                if (!$ppec->currentOrder) {
                    $ppec->logs[] = $ppec->l('Cannot create order');
                } else {
                    $idOrder = (int) $ppec->currentOrder;
                    $order = new Order($idOrder);
                }

                /* Check payment details to display the appropriate content */
                if (isset($order) && ($ppec->result['ACK'] != 'Failure')) {
                    $values = array(
                        'key' => $customer->secure_key,
                        'id_module' => (int) $ppec->id,
                        'id_cart' => (int) $cart->id,
                        'id_order' => (int) $ppec->currentOrder,
                    );

                    $link = $ppec->context->link->getModuleLink('paypal', 'submit', $values);
                    Tools::redirect($link);
                } elseif ($ppec->result['ACK'] != 'Failure') {
                    $ppec->context->smarty->assign(array(
                        'logs' => $ppec->logs,
                        'message' => $ppec->l('Error occurred:'),
                    ));

                    $this->setTemplate('error.tpl');
                }
            } else {
                /* If Cart changed, no need to keep the paypal data */
                unset($ppec->context->cookie->{PayPalExpressCheckout::$cookieName});
                $ppec->logs[] = $ppec->l('Cart changed since the last checkout express, please make a new Paypal checkout payment');
            }
        }

        /* Display result if error occurred */
        if (!$this->context->cart->id) {
            $this->context->cart->delete();
            $ppec->logs[] = $ppec->l('Your cart is empty.');
        }
        $ppec->context->smarty->assign(array(
            'logs' => $ppec->logs,
            'message' => $ppec->l('Error occurred:'),
        ));

        $template = 'error.tpl';


        /**
         * Detect if we are using mobile or not
         * Check the 'ps_mobile_site' parameter.
         */
        $this->context->smarty->assign(array(
            'use_mobile' => (bool) $ppec->useMobile(),
        ));

        $this->setTemplate($template);
    }

    /**
     * @param PayPalExpressCheckout $ppec
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setContextData($ppec)
    {
        // Create new Cart to avoid any refresh or other bad manipulations
        $ppec->context->cart = new Cart();
        $ppec->context->cart->id_currency = (int) $ppec->context->currency->id;
        $ppec->context->cart->id_lang = (int) $ppec->context->language->id;

        // Customer settings
        $ppec->context->cart->id_guest = (int) $ppec->context->cookie->id_guest;
        $ppec->context->cart->id_customer = (int) $ppec->context->customer->id;

        // Secure key information
        $secureKey = isset($ppec->context->customer) ? $ppec->context->customer->secure_key : null;
        $ppec->context->cart->secure_key = $secureKey;
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
        $customer = new Customer();
        $customer->email = $email;
        $customer->lastname = $payPalExpressCheckout->result['LASTNAME'];
        $customer->firstname = $payPalExpressCheckout->result['FIRSTNAME'];
        $customer->passwd = Tools::encrypt(Tools::passwdGen());

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
        $address = new Address($id);
        $address->id_country = Country::getByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']);
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
        if (Country::containsStates($address->id_country)) {
            $address->id_state = (int) State::getIdByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTATE'], $address->id_country);
        }

        $address->postcode = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOZIP'];
        if (isset($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOPHONENUM'])) {
            $address->phone = $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOPHONENUM'];
        }

        $address->id_customer = $customer->id;

        return $address;
    }

    /**
     * @param $payPalExpressCheckout
     * @param $customer
     *
     * @return Address|bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function checkAndModifyAddress($payPalExpressCheckout, $customer)
    {
        $context = Context::getContext();
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
                        $address['id_country'] == Country::getByIso($payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']) &&
                        $address['address1'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET'] &&
                        $address['address2'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOSTREET2'] &&
                        $address['city'] == $payPalExpressCheckout->result['PAYMENTREQUEST_0_SHIPTOCITY']
                    ) {
                        $paypalAddress = new Address($address['id_address']);
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
     * @param Customer              $customer
     * @param Cart                  $cart
     * @param PayPalExpressCheckout $ppec
     */
    protected function validateOrder($customer, $cart, $ppec)
    {
        $amountMatch = $ppec->rightPaymentProcess();
        $orderTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);

        // Payment succeed
        if ($ppec->hasSucceedRequest() && !empty($ppec->token) && $amountMatch) {
            if ((bool) Configuration::get('PAYPAL_CAPTURE')) {
                $paymentType = (int) Configuration::get('PS_OS_PAYPAL');
                $paymentStatus = 'Pending_capture';
                $message = $ppec->l('Pending payment capture.').'<br />';
            } else {
                if (isset($ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'])) {
                    $paymentStatus = $ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'];
                } else {
                    $paymentStatus = 'Error';
                }

                if ((strcasecmp($paymentStatus, 'Completed') === 0) || (strcasecmp($paymentStatus, 'Completed_Funds_Held') === 0)) {
                    $paymentType = (int) Configuration::get('PS_OS_PAYMENT');
                    $message = $ppec->l('Payment accepted.').'<br />';
                } elseif (Tools::getValue('banktxnpendingurl') && Tools::getValue('banktxnpendingurl') == 'true') {
                    $paymentType = (int) Configuration::get('PS_OS_PAYPAL');
                    $message = $ppec->l('eCheck').'<br />';
                } elseif (strcasecmp($paymentStatus, 'Pending') === 0) {
                    $paymentType = (int) Configuration::get('PS_OS_PAYPAL');
                    $message = $ppec->l('Pending payment confirmation.').'<br />';
                }
            }
        } else {
            // Payment error
            //Check if error is 10486, if it is redirect user to paypal
            if ($ppec->result['L_ERRORCODE0'] == 10486) {
                $ppec->redirectToAPI();
            }

            $paymentStatus = isset($ppec->result['PAYMENTINFO_0_PAYMENTSTATUS']) ? $ppec->result['PAYMENTINFO_0_PAYMENTSTATUS'] : false;
            $paymentType = (int) Configuration::get('PS_OS_ERROR');

            if ($amountMatch) {
                $message = implode('<br />', $ppec->logs).'<br />';
            } else {
                $message = $ppec->l('Price paid on paypal is not the same that on PrestaShop.').'<br />';
            }

        }

        $transaction = PayPalOrder::getTransactionDetails($ppec, $paymentStatus);
        $ppec->context->cookie->id_cart = $cart->id;

        $ppec->validateOrder(
            (int) $cart->id,
            $paymentType,
            $orderTotal,
            $ppec->displayName,
            $message,
            $transaction,
            (int) $cart->id_currency,
            false,
            $customer->secure_key,
            $ppec->context->shop
        );
    }
}
