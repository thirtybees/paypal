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

use GuzzleHttp\Client;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class PayPalRestApi
 *
 * @package PayPalModule
 */
class PayPalRestApi
{
    const PATH_CREATE_TOKEN = '/v1/oauth2/token';
    const PATH_CREATE_PAYMENT = '/v1/payments/payment';
    const PATH_LOOK_UP = '/v1/payments/payment/';
    const PATH_WEBPROFILES = '/v1/payment-experience/web-profiles';
    const PATH_EXECUTE_PAYMENT = '/v1/payments/payment/';
    const PATH_EXECUTE_REFUND = '/v1/payments/sale/';

    const STANDARD_PROFILE = 1;
    const PLUS_PROFILE = 2;
    const EXPRESS_CHECKOUT_PROFILE = 3;

    /** @var \Context $context */
    protected $context;

    /** @var \Cart $cart */
    protected $cart;

    /** @var \Customer $customer */
    protected $customer;

    /** @var string $clientId */
    protected $clientId;

    /** @var string $secret */
    protected $secret;

    /** @var null|string $accessToken  */
    protected $accessToken = null;

    /** @var null|\stdClass $profiles */
    protected $profiles = null;

    /**
     * ApiPaypalPlus constructor.
     */
    public function __construct($clientId = null, $secret = null)
    {
        $this->context = \Context::getContext();
        $this->cart = $this->context->cart;
        $this->customer = $this->context->customer;

        $this->clientId = ($clientId) ? $clientId : \Configuration::get(\PayPal::CLIENT_ID);
        $this->secret = ($secret) ? $secret : \Configuration::get(\PayPal::SECRET);
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $result = $this->send(
            self::PATH_CREATE_TOKEN,
            http_build_query(['grant_type' => 'client_credentials']),
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            true,
            'POST'
        );

        /*
         * Init variable
         */
        $oPayPalToken = json_decode($result);

        if (isset($oPayPalToken->error)) {
            return false;
        } else {
            $timeMax = time() + $oPayPalToken->expires_in;
            $accessToken = $oPayPalToken->access_token;

            /*
             * Set Token in Cookie
             */
            $this->context->cookie->paypal_access_token_time_max = $timeMax;
            $this->context->cookie->paypal_access_token_access_token = $accessToken;
            $this->context->cookie->write();

            if (!$this->accessToken) {
                $this->accessToken = $accessToken;
            }

            return $accessToken;
        }
    }

    /**
     * @param int $type
     *
     * @return bool|array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getWebProfile($type = self::STANDARD_PROFILE)
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $data = $this->createWebProfile($type);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            if ($this->profiles) {
                $profileId = '';
                foreach ($this->profiles as $profile) {
                    if ($profile->name == $data['name']) {
                        $profileId = $profile->id;
                    }
                }

                if ($profileId) {
                    // DELETE first
                    $this->send(self::PATH_WEBPROFILES.'/'.$profileId, false, $headers, false, 'DELETE');
                }
            }

            // Then create
            $result = json_decode($this->send(self::PATH_WEBPROFILES, json_encode($data), $headers, false, 'POST'));

            if (isset($result->id)) {
                return $result->id;
            }
        }

        return false;
    }

    /**
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getListProfile()
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $header = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            $this->profiles = json_decode($this->send(self::PATH_WEBPROFILES, false, $header));

            return $this->profiles;
        }

        return [];
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function deleteProfile()
    {
//        $accessToken = $this->getToken();
//
//        if ($accessToken) {
//            $this->send(self::PATH_WEBPROFILES, false, false, false, 'DELETE');
//        }

        return true;
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function refreshToken()
    {
        if ($this->context->cookie->paypal_access_token_time_max < time()) {
            return $this->getToken();
        } else {
            return $this->context->cookie->paypal_access_token_access_token;
        }
    }

    /**
     * @return \stdClass
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPaymentObject($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $cart = $this->cart;
        $customer = $this->customer;

        if (!$returnUrl) {
            $returnUrl = $this->context->link->getModuleLink('paypal', 'expresscheckout', ['id_cart' => (int) $cart->id], true);
        }

        if (!$cancelUrl) {
            $cancelUrl = $this->context->link->getModuleLink('paypal', 'expresscheckoutcancel', ['id_cart' => (int) $cart->id], true);
        }

        $oCurrency = new \Currency($this->cart->id_currency);
        $address = new \Address((int) $this->cart->id_address_invoice);

        $country = new \Country((int) $address->id_country);
        $isoCode = $country->iso_code;

        $totalShippingCostWithoutTax = $cart->getTotalShippingCost(null, false);


        $totalCartWithTax = $cart->getOrderTotal(true);
        $totalCartWithoutTax = $cart->getOrderTotal(false);
        $totalTax = $totalCartWithTax - $totalCartWithoutTax;

        if ($cart->gift) {
            $giftWithoutTax = $cart->getGiftWrappingPrice(false);
        } else {
            $giftWithoutTax = 0;
        }

        $cartItems = $cart->getProducts();

        $state = new \State($address->id_state);
        $shippingAddress = new \stdClass();
        $shippingAddress->recipient_name = $address->alias;
        $shippingAddress->type = 'residential';
        $shippingAddress->line1 = $address->address1;
        $shippingAddress->line2 = $address->address2;
        $shippingAddress->city = $address->city;
        $shippingAddress->country_code = $isoCode;
        $shippingAddress->postal_code = $address->postcode;
        $shippingAddress->state = ($state->iso_code == null) ? '' : $state->iso_code;
        $shippingAddress->phone = $address->phone;

        $payerInfo = new \stdClass();
        $payerInfo->email = '"'.$customer->email.'"';
        $payerInfo->first_name = $address->firstname;
        $payerInfo->last_name = $address->lastname;
        $payerInfo->country_code = '"'.$isoCode.'"';
        $payerInfo->shipping_address = [$shippingAddress];

        $payer = new \stdClass();
        $payer->payment_method = 'paypal';
        //$payer->payer_info = $payer_info; // Objet set by PayPal

        $aItems = [];
        /* Item */
        foreach ($cartItems as $cartItem) {
            $item = new \stdClass();
            $item->name = $cartItem['name'];
            $item->currency = $oCurrency->iso_code;
            $item->quantity = $cartItem['quantity'];
            $item->price = number_format(round($cartItem['price'], 2), 2);
            $item->tax = number_format(round($cartItem['price_wt'] - $cartItem['price'], 2), 2);
            $aItems[] = $item;
            unset($item);
        }

        /* ItemList */
        $itemList = new \stdClass();
        $itemList->items = $aItems;

        /* Detail */
        $details = new \stdClass();
        $details->shipping = number_format($totalShippingCostWithoutTax, 2);
        $details->tax = number_format($totalTax, 2);
        $details->handling_fee = number_format($giftWithoutTax, 2);
        $details->subtotal = number_format($totalCartWithoutTax - $totalShippingCostWithoutTax - $giftWithoutTax, 2);

        /* Amount */
        $amount = new \stdClass();
        $amount->total = number_format($totalCartWithTax, 2);
        $amount->currency = $oCurrency->iso_code;
        $amount->details = $details;

        /* Transaction */
        $transaction = new \stdClass();
        $transaction->amount = $amount;
        $transaction->item_list = $itemList;
        $transaction->description = 'Payment description';

        /* Redirect Url */
        $redirectUrls = new \stdClass();
        $redirectUrls->cancel_url = $cancelUrl;
        $redirectUrls->return_url = $returnUrl;

        /* Payment */
        $payment = new \stdClass();
        $payment->transactions = [$transaction];
        $payment->payer = $payer;
        $payment->intent = 'sale';
        if (\Configuration::get(\PayPal::LIVE)) {
            switch ($profile) {
                case self::PLUS_PROFILE:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID_LIVE);
                    break;
                case self::EXPRESS_CHECKOUT_PROFILE:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID_LIVE);
                    break;
                default:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID_LIVE);
                    break;
            }
        } else {
            switch ($profile) {
                case self::PLUS_PROFILE:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID);
                    break;
                case self::EXPRESS_CHECKOUT_PROFILE:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID);
                    break;
                default:
                    $payment->experience_profile_id = \Configuration::get(\PayPal::STANDARD_WEBSITE_PROFILE_ID);
                    break;
            }
        }



        $payment->redirect_urls = $redirectUrls;

        return $payment;
    }

    /**
     * @param bool|string $returnUrl
     * @param bool|string $cancelUrl
     * @param int         $profile
     *
     * @return mixed
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPayment($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $data = $this->createPaymentObject($returnUrl, $cancelUrl, $profile);

        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->getToken(),
        ];

        $result = json_decode($this->send(self::PATH_CREATE_PAYMENT, json_encode($data), $header, false, 'POST'));

        return $result;
    }

    /**
     * @param string      $url      URL including get params
     * @param bool|string $body
     * @param bool        $headers
     * @param bool        $identify
     *
     * @param bool|string $requestType
     *
     * @return mixed
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function send($url, $body = false, $headers = false, $identify = false, $requestType = 'GET')
    {
        if (!\Configuration::get(\PayPal::LIVE)) {
            $baseUri = 'https://api.sandbox.paypal.com';
        } else {
            $baseUri = 'https://api.paypal.com';
        }

        $guzzle = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 60.0,
            'verify'  => _PS_TOOL_DIR_.'cacert.pem',
            'http_errors'  => false,
        ]);

        $requestOptions = [];
        if ($identify) {
            $requestOptions['auth'] = [$this->clientId, $this->secret];
        }
        if ($headers) {
            $requestOptions['headers'] = $headers;
        }
        if ($body) {
            $requestOptions['body'] = (string) $body;
        }

        $response = $guzzle->request($requestType, '/'.ltrim($url, '/'), $requestOptions);

        return (string) $response->getBody();
    }

    /**
     * @param int $type
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function createWebProfile($type)
    {
        $name = 'ThirtyBees_'.(int) $this->context->shop->id.'_'.(int) $type;

        switch ($type) {
            case self::PLUS_PROFILE:
                return [
                    'name' => $name,
                    'presentation' => [
                        'brand_name' => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image' => _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg',
                        'locale_code' => 'en_US',
                    ],
                    'input_fields' => [
                        'allow_note' => false,
                        'no_shipping' => 2,
                        'address_override' => 1,
                    ],
                    'flow_config' => [
                        'landing_page_type' => 'billing',
                    ],
                ];
            case self::EXPRESS_CHECKOUT_PROFILE:
                return [
                    'name' => $name,
                    'presentation' => [
                        'brand_name' => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image' => _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg',
                        'locale_code' => 'en_US',
                    ],
                    'input_fields' => [
                        'allow_note' => false,
                        'no_shipping' => 1,
                        'address_override' => 0,
                    ],
                    'flow_config' => [
                        'landing_page_type' => 'billing',
                    ],
                ];
            default:
                return [
                    'name' => $name,
                    'presentation' => [
                        'brand_name' => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image' => _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg',
                        'locale_code' => 'en_US',
                    ],
                    'input_fields' => [
                        'allow_note' => false,
                        'no_shipping' => 2,
                        'address_override' => 1,
                    ],
                    'flow_config' => [
                        'landing_page_type' => 'billing',
                    ],
                ];
        }
    }

    /**
     * @param array $params
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function setParams($params)
    {
        $this->cart = new \Cart($params['cart']->id);
        $this->customer = new \Customer($params['cookie']->id_customer);
    }

    /**
     * @param string $paymentId
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function lookUpPayment($paymentId)
    {
        if (!$paymentId) {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        return json_decode($this->send(PayPalRestApi::PATH_LOOK_UP.$paymentId, false, $header));
    }

    /**
     * @param string $payerId
     * @param string $paymentId
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function executePayment($payerId, $paymentId)
    {
        if ($payerId == 'NULL' || $paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $data = ['payer_id' => $payerId];

        return json_decode($this->send(PayPalRestApi::PATH_EXECUTE_PAYMENT.$paymentId.'/execute/', json_encode($data), $header, false, 'POST'));
    }

    /**
     * @param string    $paymentId
     * @param \stdClass $data
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function executeRefund($paymentId, $data)
    {
        if ($paymentId == 'NULL' || !is_object($data)) {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        return json_decode($this->send(PayPalRestApi::PATH_EXECUTE_REFUND.$paymentId.'/refund', json_encode($data), $header));
    }
}
