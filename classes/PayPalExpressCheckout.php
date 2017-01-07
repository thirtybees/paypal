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

namespace PayPalModule;

require_once dirname(__FILE__).'/../paypal.php';

class PayPalExpressCheckout
{
    public $logs = [];

    public $methodVersion = '106';

    public $method;

    /** @var \Context $context */
    protected $context;

    /** @var \PayPal $module */
    protected $module;

    /** @var \Currency $currency Currency used for the payment process **/
    public $currency;

    /** @var int $decimals Used to set prices precision **/
    public $decimals;

    /** @var array $result Contains the last request result **/
    public $result;

    /** @var string $token Contains the last token **/
    public $token;

    /**
     * Depending of the type set, id_cart or id_product will be set
     *
     * @var int $idCart
     */
    public $idCart;

    /**
     * Depending of the type set, id_cart or id_product will be set
     *
     * @var int $idProduct
     */
    public $idProduct;

    /** @var int $idProductAttribute */
    public $idProductAttribute;

    /** @var int $quantity */
    public $quantity;

    /** @var string $payerId */
    public $payerId;

    /** @var array $availableTypes */
    public $availableTypes = ['cart', 'product', 'payment_cart'];

    public $totalDifferentProduct;

    /** @var array $productList */
    public $productList = [];

    /**
     * Used to know if user can validated his payment after shipping / address selection
     *
     * @var bool $ready
     */
    public $ready = false;

    /**
     * Take for now cart or product value
     *
     * @var bool $type
     */
    public $type = false;

    /** @var string $cookieName */
    public static $cookieName = 'express_checkout';

    /** @var array $cookieKey */
    public $cookieKey = [
        'token',
        'idProduct',
        'idProductAttribute',
        'quantity',
        'type',
        'totalDifferentProduct',
        'secureKey',
        'ready',
        'payerId',
    ];

    /** @var string $secureKey */
    public $secureKey;

    /**
     * PayPalExpressCheckout constructor.
     *
     * @param bool $type
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct($type = false)
    {
        $this->context = \Context::getContext();
        $this->module = \Module::getInstanceByName('paypal');

        // If type is sent, the cookie has to be delete
        if ($type) {
            unset($this->context->cookie->{self::$cookieName});
            $this->setExpressCheckoutType($type);
        }

        // Store back the PayPal data if present under the cookie
        if (isset($this->context->cookie->{self::$cookieName})) {
            $paypal = unserialize($this->context->cookie->{self::$cookieName});

            foreach ($this->cookieKey as $key) {
                if (isset($paypal[$key])) {
                    $this->{$key} = $paypal[$key];
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function initParameters()
    {
        if (!$this->context->cart || !$this->context->cart->id) {
            return false;
        }

        $context = $this->context;
        $module = $this->module;

        $cartCurrency = new \Currency((int) $context->cart->id_currency);
        $currencyModule = $module->getCurrency((int) $context->cart->id_currency);

        $this->currency = $cartCurrency;

        if (!\Validate::isLoadedObject($this->currency)) {
            $context->controller->errors[] = $module->l('Not a valid currency');
        }

        if (count($context->controller->errors)) {
            return false;
        }

        $currencyDecimals = is_array($this->currency) ? (int) $this->currency['decimals'] : (int) $this->currency->decimals;
        $this->decimals = $currencyDecimals * _PS_PRICE_DISPLAY_PRECISION_;

        if ($cartCurrency !== $currencyModule) {
            $context->cart->id_currency = $currencyModule->id;
            $context->cart->update();
        }

        $context->currency = $currencyModule;
        $this->productList = $context->cart->getProducts(true);

        return (bool) count($this->productList);
    }

    /**
     * @param bool $accessToken
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function setExpressCheckout($accessToken = false)
    {
        $this->method = 'SetExpressCheckout';
        $fields = [];
        $this->setCancelUrl($fields);

        // Only this call need to get the value from the $_GET / $_POST array
        if (!$this->initParameters() || !$fields['CANCELURL']) {
            return false;
        }

        // Set payment detail (reference)
        $this->setPaymentDetails($fields);
        $fields['SOLUTIONTYPE'] = 'Sole';
        $fields['LANDINGPAGE'] = 'Login';

        // Seller informations
        $fields['USER'] = \Configuration::get('PAYPAL_API_USER');
        $fields['PWD'] = \Configuration::get('PAYPAL_API_PASSWORD');
        $fields['SIGNATURE'] = \Configuration::get('PAYPAL_API_SIGNATURE');

        if ($accessToken) {
            $fields['IDENTITYACCESSTOKEN'] = $accessToken;
        }

        if (\Country::getIsoById(\Configuration::get('PAYPAL_COUNTRY_DEFAULT')) == 'de') {
            $fields['BANKTXNPENDINGURL'] = $this->context->link->getModuleLink('paypal', 'expresscheckoutpayment', ['banktxnpendingurl' => 'true'], \Tools::usingSecureMode());
        }

        $this->callAPI($fields);
        $this->storeToken();

        return false;
    }

    /**
     * @param $fields
     */
    public function setCancelUrl(&$fields)
    {
        $url = $this->context->link->getModuleLink('paypal', 'expresscheckoutpayment', [], \Tools::usingSecureMode()).'?'.urldecode($_SERVER['QUERY_STRING']);
        $parsedData = parse_url($url);

        $parsedData['scheme'] .= '://';

        if (isset($parsedData['path'])) {
            $parsedData['path'] .= '?paypal_ec_canceled=1&';
            $parsedData['query'] = isset($parsedData['query']) ? $parsedData['query'] : null;
        } else {
            $parsedData['path'] = '?paypal_ec_canceled=1&';
            $parsedData['query'] = '/'.(isset($parsedData['query']) ? $parsedData['query'] : null);
        }

        $cancelUrl = implode($parsedData);

        if (!empty($cancelUrl)) {
            $fields['CANCELURL'] = $cancelUrl;
        }

    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getExpressCheckout()
    {
        $this->method = 'GetExpressCheckoutDetails';
        $fields = [];
        $fields['TOKEN'] = $this->token;

        $this->initParameters();
        $this->callAPI($fields);

        // The same token of SetExpressCheckout
        $this->storeToken();
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function doExpressCheckout()
    {
        $this->method = 'DoExpressCheckoutPayment';
        $fields = [];
        $fields['TOKEN'] = $this->token;
        $fields['PAYERID'] = $this->payerId;

        if (\Configuration::get('PAYPAL_COUNTRY_DEFAULT') == 1) {
            $fields['BANKTXNPENDINGURL'] = '';
        }

        if (count($this->productList) <= 0) {
            $this->initParameters();
        }

        // Set payment detail (reference)
        $this->setPaymentDetails($fields);
        $this->callAPI($fields);

        $this->result += $fields;
    }

    /**
     * @param $fields
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function callAPI($fields)
    {
        $this->logs = [];
        $paypalLib = new PaypalLib();

        $this->result = $paypalLib->makeCall($this->module->getAPIURL(), $this->module->getAPIScript(), $this->method, $fields, $this->methodVersion);
        $this->logs = array_merge($this->logs, $paypalLib->getLogs());

        $this->storeToken();
    }

    /**
     * @param $fields
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setPaymentDetails(&$fields)
    {
        // Required field
        $fields['RETURNURL'] = $this->context->link->getModuleLink('paypal', 'expresscheckoutpayment', [], \Tools::usingSecureMode());
        $fields['NOSHIPPING'] = '1';
        $fields['BUTTONSOURCE'] = $this->module->getTrackingCode((int) \Configuration::get('PAYPAL_PAYMENT_METHOD'));

        // Products
//        $taxes = $total = 0;
        $index = -1;

        // Set cart products list
        $this->setProductsList($fields, $index, $total /* , $taxes */);
        $this->setDiscountsList($fields, $index, $total /* , $taxes */);
        $this->setGiftWrapping($fields, $index, $total);

        // Payment values
        $this->setPaymentValues($fields, $index, $total /* , $taxes */);

        $idAddress = (int) $this->context->cart->id_address_delivery;
        if (($idAddress == 0) && ($this->context->customer)) {
            $idAddress = \Address::getFirstCustomerAddressId($this->context->customer->id);
        }

        if ($idAddress && method_exists($this->context->cart, 'isVirtualCart') && !$this->context->cart->isVirtualCart()) {
            $this->setShippingAddress($fields, $idAddress);
        } else {
            $fields['NOSHIPPING'] = '0';
        }

        foreach ($fields as &$field) {
            if (is_numeric($field)) {
                $field = str_replace(',', '.', $field);
            }
        }

    }

    /**
     * @param $fields
     * @param $idAddress
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setShippingAddress(&$fields, $idAddress)
    {
        $address = new \Address($idAddress);

        //We allow address modification when using express checkout shortcut
        if ($this->type != 'payment_cart') {
            $fields['ADDROVERRIDE'] = '0';
            $fields['NOSHIPPING'] = '0';
        } else {
            $fields['ADDROVERRIDE'] = '1';
        }

        $fields['EMAIL'] = $this->context->customer->email;
        $fields['PAYMENTREQUEST_0_SHIPTONAME'] = $address->firstname.' '.$address->lastname;
        $fields['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = (empty($address->phone)) ? $address->phone_mobile : $address->phone;
        $fields['PAYMENTREQUEST_0_SHIPTOSTREET'] = $address->address1;
        $fields['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $address->address2;
        $fields['PAYMENTREQUEST_0_SHIPTOCITY'] = $address->city;

        if ($address->id_state) {
            $state = new \State((int) $address->id_state);
            $fields['PAYMENTREQUEST_0_SHIPTOSTATE'] = $state->iso_code;
        }

        $country = new \Country((int) $address->id_country);
        $fields['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $country->iso_code;
        $fields['PAYMENTREQUEST_0_SHIPTOZIP'] = $address->postcode;
    }

    /**
     * @param $fields
     * @param $index
     * @param $total
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setProductsList(&$fields, &$index, &$total)
    {
        foreach ($this->productList as $product) {
            $fields['L_PAYMENTREQUEST_0_NUMBER'.++$index] = (int) $product['id_product'];

            $fields['L_PAYMENTREQUEST_0_NAME'.$index] = $product['name'];

            if (isset($product['attributes']) && (empty($product['attributes']) === false)) {
                $fields['L_PAYMENTREQUEST_0_NAME'.$index] .= ' - '.$product['attributes'];
            }

            $fields['L_PAYMENTREQUEST_0_DESC'.$index] = \Tools::substr(strip_tags($product['description_short']), 0, 50).'...';

            $fields['L_PAYMENTREQUEST_0_AMT'.$index] = \Tools::ps_round($product['price_wt'], $this->decimals);
            $fields['L_PAYMENTREQUEST_0_QTY'.$index] = $product['quantity'];

            $total = $total + ($fields['L_PAYMENTREQUEST_0_AMT'.$index] * $product['quantity']);
        }
    }

    /**
     * @param $fields
     * @param $index
     * @param $total
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setDiscountsList(&$fields, &$index, &$total)
    {
        $discounts = (_PS_VERSION_ < '1.5') ? $this->context->cart->getDiscounts() : $this->context->cart->getCartRules();

        if (count($discounts) > 0) {
            foreach ($discounts as $discount) {
                $fields['L_PAYMENTREQUEST_0_NUMBER'.++$index] = $discount['id_discount'];

                $fields['L_PAYMENTREQUEST_0_NAME'.$index] = $discount['name'];
                if (isset($discount['description']) && !empty($discount['description'])) {
                    $fields['L_PAYMENTREQUEST_0_DESC'.$index] = \Tools::substr(strip_tags($discount['description']), 0, 50).'...';
                }

                /* It is a discount so we store a negative value */
                $fields['L_PAYMENTREQUEST_0_AMT'.$index] = -1 * \Tools::ps_round($discount['value_real'], $this->decimals);
                $fields['L_PAYMENTREQUEST_0_QTY'.$index] = 1;

                $total = \Tools::ps_round($total + $fields['L_PAYMENTREQUEST_0_AMT'.$index], $this->decimals);
            }
        }

    }

    /**
     * @param $fields
     * @param $index
     * @param $total
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setGiftWrapping(&$fields, &$index, &$total)
    {
        if ($this->context->cart->gift == 1) {
            // FIXME: find access to PaymenmtModule::getGiftWrappingPrice
//            $giftWrappingPrice = $this->getGiftWrappingPrice();

            $fields['L_PAYMENTREQUEST_0_NAME'.++$index] = $this->module->l('Gift wrapping');

            $fields['L_PAYMENTREQUEST_0_AMT'.$index] = 0;//\Tools::ps_round($giftWrappingPrice, $this->decimals);
            $fields['L_PAYMENTREQUEST_0_QTY'.$index] = 1;

            $total = \Tools::ps_round($total /*+ $giftWrappingPrice*/, $this->decimals);
        }
    }

    /**
     * @param $fields
     * @param $index
     * @param $total
     * @param $taxes
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setPaymentValues(&$fields, &$index, &$total /* , &$taxes */)
    {
        $shippingCostTaxIncl = $this->context->cart->getTotalShippingCost();

        if ((bool) \Configuration::get('PAYPAL_CAPTURE')) {
            $fields['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Authorization';
        } else {
            $fields['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';
        }

        $currency = new \Currency((int) $this->context->cart->id_currency);
        $fields['PAYMENTREQUEST_0_CURRENCYCODE'] = $currency->iso_code;

        /**
         * If the total amount is lower than 1 we put the shipping cost as an item
         * so the payment could be valid.
         */
        if ($total <= 1) {
            $carrier = new \Carrier($this->context->cart->id_carrier);
            $fields['L_PAYMENTREQUEST_0_NUMBER'.++$index] = $carrier->id_reference;
            $fields['L_PAYMENTREQUEST_0_NAME'.$index] = $carrier->name;
            $fields['L_PAYMENTREQUEST_0_AMT'.$index] = \Tools::ps_round($shippingCostTaxIncl, $this->decimals);
            $fields['L_PAYMENTREQUEST_0_QTY'.$index] = 1;

            $fields['PAYMENTREQUEST_0_ITEMAMT'] = \Tools::ps_round($total, $this->decimals) + \Tools::ps_round($shippingCostTaxIncl, $this->decimals);
            $fields['PAYMENTREQUEST_0_AMT'] = $total + \Tools::ps_round($shippingCostTaxIncl, $this->decimals);
        } else {
            if ($currency->iso_code == 'HUF') {
                $fields['PAYMENTREQUEST_0_SHIPPINGAMT'] = round($shippingCostTaxIncl);
                $fields['PAYMENTREQUEST_0_ITEMAMT'] = \Tools::ps_round($total, $this->decimals);
                $fields['PAYMENTREQUEST_0_AMT'] = sprintf('%.2f', ($total + $fields['PAYMENTREQUEST_0_SHIPPINGAMT']));
            } else {
                $fields['PAYMENTREQUEST_0_SHIPPINGAMT'] = sprintf('%.2f', $shippingCostTaxIncl);
                $fields['PAYMENTREQUEST_0_ITEMAMT'] = \Tools::ps_round($total, $this->decimals);
                $fields['PAYMENTREQUEST_0_AMT'] = sprintf('%.2f', ($total + $fields['PAYMENTREQUEST_0_SHIPPINGAMT']));
            }
        }
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function rightPaymentProcess()
    {
        $total = $this->getTotalPaid();

        // float problem with php, have to use the string cast.
        if ((isset($this->result['AMT']) && ((string) $this->result['AMT'] != (string) $total)) ||
            (isset($this->result['PAYMENTINFO_0_AMT']) && ((string) $this->result['PAYMENTINFO_0_AMT'] != (string) $total))) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getTotalPaid()
    {
        $total = 0.00;

        $context = $this->context;
        $cart = $context->cart;

        foreach ($this->productList as $product) {
            $price = \Tools::ps_round($product['price_wt'], $this->decimals);
            $quantity = \Tools::ps_round($product['quantity'], $this->decimals);
            $total = \Tools::ps_round($total + ($price * $quantity), $this->decimals);
        }

        if ($cart->gift) {
            // FIXME: find access to PaymentModule::getGiftWrappingPrice
            $total = \Tools::ps_round($total /*+ $this->module->getGiftWrappingPrice()*/, $this->decimals);
        }

        $discounts = $cart->getCartRules();
        $shippingCost = $cart->getTotalShippingCost();

        if (count($discounts) > 0) {
            foreach ($discounts as $product) {
                $price = -1 * \Tools::ps_round($product['value_real'], $this->decimals);
                $total = \Tools::ps_round($total + $price, $this->decimals);
            }
        }

        return \Tools::ps_round($shippingCost, $this->decimals) + $total;
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function storeToken()
    {
        if (is_array($this->result) && isset($this->result['TOKEN'])) {
            $this->token = (string) $this->result['TOKEN'];
        }

    }

    // Store data for the next reloading page
    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function storeCookieInfo()
    {
        $tab = [];

        foreach ($this->cookieKey as $key) {
            $tab[$key] = $this->{$key};
        }

        $this->context->cookie->{self::$cookieName} = serialize($tab);
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function displayPaypalInContextCheckout()
    {
        $this->secureKey = $this->getSecureKey();
        $this->storeCookieInfo();
        echo $this->token;
        die;
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function hasSucceedRequest()
    {
        if (is_array($this->result)) {
            foreach (['ACK', 'PAYMENTINFO_0_ACK'] as $key) {
                if (isset($this->result[$key]) && \Tools::strtoupper($this->result[$key]) == 'SUCCESS') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function getSecureKey()
    {
        if (!count($this->productList)) {
            $this->initParameters();
        }

        $key = [];

        foreach ($this->productList as $product) {
            $idProduct = $product['id_product'];
            $idProductAttribute = $product['id_product_attribute'];
            $quantity = $product['quantity'];

            $key[] = $idProduct.$idProductAttribute.$quantity._COOKIE_KEY_;
        }

        return md5(serialize($key));
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function isProductsListStillRight()
    {
        return $this->secureKey == $this->getSecureKey();
    }

    /**
     * @param $type
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function setExpressCheckoutType($type)
    {
        if (in_array($type, $this->availableTypes)) {
            $this->type = $type;

            return true;
        }

        return false;
    }

    /**
     * Redirect to API
     */
    public function redirectToAPI()
    {
        $this->secureKey = $this->getSecureKey();
        $this->storeCookieInfo();

        $url = '/websc&cmd=_express-checkout';


        if (($this->method == 'SetExpressCheckout') && (\Configuration::get('PAYPAL_COUNTRY_DEFAULT') == 1) && ($this->type == 'payment_cart')) {
            $url .= '&useraction=commit';
        }

        \Tools::redirectLink('https://'.$this->module->getPayPalURL().$url.'&token='.urldecode($this->token));
        exit(0);
    }

    /**
     * @param \Customer $customer
     * @param bool     $redirect
     */
    public function redirectToCheckout(\Customer $customer, $redirect = false)
    {
        $this->ready = true;
        $this->storeCookieInfo();

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
