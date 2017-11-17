<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

use GuzzleHttp\Client;

if (!defined('_TB_VERSION_')) {
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
    const PATH_AUTHORIZATION = '/v1/payments/authorization/';
    const PATH_WEBHOOK_EVENT = '/v1/notifications/webhooks-events/';
    const PATH_WEBHOOK = '/v1/notifications/webhooks/';

    const STANDARD_PROFILE = 1;
    const PLUS_PROFILE = 2;
    const EXPRESS_CHECKOUT_PROFILE = 3;

    // @codingStandardsIgnoreStart
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
    /** @var null|string $accessToken */
    protected $accessToken = null;
    /** @var null|\stdClass $profiles */
    protected $profiles = null;
    // @codingStandardsIgnoreEnd

    /**
     * ApiPaypalPlus constructor.
     *
     * @param string|null $clientId
     * @param string|null $secret
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
     * @param int $type
     *
     * @return bool|array
     */
    public function getWebProfile($type = self::STANDARD_PROFILE)
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $data = $this->createWebProfile($type);

            $headers = [
                'Content-Type'  => 'application/json',
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
            $result = $this->send(self::PATH_WEBPROFILES, json_encode($data), $headers, false, 'POST');
            if (!$result) {
                return false;
            }

            $result = json_decode($result);

            if (isset($result->id)) {
                return $result->id;
            }
        }

        return false;
    }

    /**
     * @return bool
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
        if (!$result) {
            return false;
        }

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
     * @param string      $url         URL including get params
     * @param bool|string $body
     * @param bool        $headers
     * @param bool        $identify
     * @param bool|string $requestType
     *
     * @return mixed
     */
    public function send($url, $body = false, $headers = false, $identify = false, $requestType = 'GET')
    {
        if (!\Configuration::get(\PayPal::LIVE)) {
            $baseUri = 'https://api.sandbox.paypal.com';
        } else {
            $baseUri = 'https://api.paypal.com';
        }

        $guzzle = new Client(
            [
                'base_uri'    => $baseUri,
                'timeout'     => 60.0,
                'verify'      => _PS_TOOL_DIR_.'cacert.pem',
                'http_errors' => false,
            ]
        );

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

        try {
            $response = $guzzle->request($requestType, '/'.ltrim($url, '/'), $requestOptions);
        } catch (\Exception $e) {
            $context = \Context::getContext();
            if (isset($context->employee->id) && $context->employee->id) {
                $context->controller->errors[] = 'Connection error: '.$e->getMessage();
            }

            return false;
        }

        return (string) $response->getBody();
    }

    /**
     * @return array|false
     */
    public function getWebProfiles()
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $header = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            $result = $this->send(self::PATH_WEBPROFILES, false, $header);
            if (!$result) {
                return false;
            }

            $this->profiles = json_decode($result);

            return $this->profiles;
        }

        return [];
    }

    /**
     * @return bool
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
     * @param bool|string $returnUrl
     * @param bool|string $cancelUrl
     * @param int         $profile
     *
     * @return mixed
     */
    public function createPayment($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $data = $this->createPaymentObject($returnUrl, $cancelUrl, $profile);

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$this->getToken(),
        ];

        $result = $this->send(self::PATH_CREATE_PAYMENT, json_encode($data), $header, false, 'POST');
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @param string|bool $returnUrl
     * @param string|bool $cancelUrl
     * @param int         $profile
     *
     * @return \stdClass
     */
    public function createPaymentObject($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $cart = $this->cart;
        $customer = $this->customer;

        if (!$returnUrl) {
            $returnUrl = $this->context->link->getModuleLink('paypal', 'expresscheckoutconfirm', ['id_cart' => (int) $cart->id], true);
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

        if ($cart->gift) {
            $giftWithoutTax = $cart->getGiftWrappingPrice(false);
        } else {
            $giftWithoutTax = 0;
        }

        // PayPal does not always apply discounts and taxes in the way thirty bees can
        // To account for this we are going to keep track of the difference and apply
        // these to the `shipping_discount` (if negative difference) or `handling_fee` (positive)
        // fields when necessary.
        $remaining = round($totalCartWithTax, 2);

        $cartItems = $cart->getProducts();

        $state = new \State($address->id_state);
        $shippingAddress = [
            'recipient_name' => $customer->firstname.' '.$customer->lastname,
            'line1'          => $address->address1,
            'line2'          => $address->address2,
            'city'           => $address->city,
            'country_code'   => $isoCode,
            'postal_code'    => $address->postcode,
            'state'          => ($state->iso_code == null) ? '' : $state->iso_code,
        ];

        $payer = new \stdClass();
        $payer->payment_method = 'paypal';

        $subTotal = 0.00000;
        $subTotalTax = 0.00000;
        $aItems = [];
        /* Item */
        foreach ($cartItems as $cartItem) {
            $roundedTotalWithoutTax = round($cartItem['total'], 2);
            $roundedTax = round($cartItem['total_wt'] - $cartItem['total'], 2);
            $quantity = $cartItem['cart_quantity'];
            $lastItemPriceDifference = round($roundedTotalWithoutTax - round($cartItem['price'], 2) * $quantity, 2);
            $lastItemTaxDifference = round($roundedTax - round($cartItem['price_wt'] - $cartItem['price'], 2) * $quantity, 2);

            // If the last item has at least one cent difference on this cart line, then change the price of the last item
            if ($lastItemPriceDifference >= 0.01 || $lastItemTaxDifference >= 0.01) {
                $aItems[] = (object) [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => $quantity - 1,
                    'price'    => round($cartItem['price'], 2),
                    'tax'      => round($cartItem['price_wt'] - $cartItem['price'], 2),
                ];
                $remaining -= round($cartItem['price'], 2) * ($quantity - 1);
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) * ($quantity - 1);
                $subTotal += round($cartItem['price'], 2) * ($quantity - 1);
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) * ($quantity - 1);
                $aItems[] = (object) [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => 1,
                    'price'    => round($cartItem['price'], 2) + $lastItemPriceDifference,
                    'tax'      => round($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference,
                ];
                $remaining -= round($cartItem['price'], 2) + $lastItemPriceDifference;
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference;
                $subTotal += round($cartItem['price'], 2) + $lastItemPriceDifference;
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference;
            } else {
                $aItems[] = (object) [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => $quantity,
                    'price'    => round($cartItem['price'], 2),
                    'tax'      => round($cartItem['price_wt'] - $cartItem['price'], 2),
                ];
                $remaining -= round($cartItem['price'], 2) * $quantity;
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) * $quantity;
                $subTotal += round($cartItem['price'], 2) * $quantity;
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) * $quantity;
            }
        }

        $details = [
            'shipping'     => round($totalShippingCostWithoutTax, 2),
            'tax'          => round($subTotalTax, 2),
            'subtotal'     => round($subTotal, 2),
        ];

        $remaining -= round($totalShippingCostWithoutTax, 2);

        // Now we are going to handle the differences
        // if despite the gift wrapping costs, the remaining number is negative, we have applied some discounts
        // that couldn't be handled in a PayPal way. Therefore, we fill the `shipping_discount` field.
        if (round($remaining - $giftWithoutTax, 2) < 0) {
            $details['shipping_discount'] = abs(round($remaining - $giftWithoutTax, 2));
        } else {
            $details['handling_fee'] = round($remaining - $giftWithoutTax, 2);
        }

        /* Amount */
        $amount = (object) [
            'total'    => round($totalCartWithTax, 2),
            'currency' => $oCurrency->iso_code,
            'details'  => $details,
        ];

        /* Transaction */
        $transaction = (object) [
            'amount'      => $amount,
            'description' => 'Payment description',
            'item_list'   => [
                'items' => $aItems,
                'shipping_address' => \Validate::isLoadedObject($address) && \PayPal::checkAddress($address) ? $shippingAddress : null,
            ],
        ];

        /* Redirect Url */
        $redirectUrls = (object) [
            'cancel_url' => $cancelUrl,
            'return_url' => $returnUrl,
        ];

        /* Payment */
        $payment = (object) [
            'transactions' => [$transaction],
            'payer'        => $payer,
            'intent'       => 'authorize',
        ];
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
     * @param array $params
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
     */
    public function lookUpPayment($paymentId)
    {
        if (!$paymentId) {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];
        $result = $this->send(PayPalRestApi::PATH_LOOK_UP.$paymentId, false, $header);
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * Register a webhook
     *
     * @param string $webhookUrl
     *
     * @return \stdClass|false
     */
    public function registerWebhook($webhookUrl)
    {
        // The types we need
        $types = [
            [
                'name' => 'PAYMENT.AUTHORIZATION.CREATED',
            ], [
                'name' => 'PAYMENT.AUTHORIZATION.VOIDED',
            ], [
                'name' => 'PAYMENT.CAPTURE.COMPLETED',
            ], [
                'name' => 'PAYMENT.CAPTURE.DENIED',
            ], [
                'name' => 'PAYMENT.CAPTURE.PENDING',
            ], [
                'name' => 'PAYMENT.CAPTURE.REFUNDED',
            ], [
                'name' => 'PAYMENT.CAPTURE.REVERSED',
            ],
        ];

        $accessToken = $this->refreshToken();

        $data = [
            'url'         => $webhookUrl,
            'event_types' => $types,
        ];

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];
        $result = $this->send(rtrim(PayPalRestApi::PATH_WEBHOOK, '/'), json_encode($data), $header, false, 'POST');
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * Get a list of webhooks
     *
     * @return bool|mixed
     */
    public function getWebhooks()
    {
        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];
        $result = $this->send(rtrim(PayPalRestApi::PATH_WEBHOOK, '/'), false, $header);
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @param string $webhookId
     *
     * @return bool|mixed
     */
    public function lookUpWebhook($webhookId)
    {
        if (!$webhookId) {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];
        $result = $this->send(PayPalRestApi::PATH_WEBHOOK_EVENT.$webhookId, false, $header);
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @return bool
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
     * @param string $authorizationId
     * @param float  $amount
     * @param string $currencyCode
     *
     * @return bool|mixed
     */
    public function capturePayment($authorizationId, $amount, $currencyCode)
    {
        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $data = [
            'amount' => [
                'currency' => strtoupper($currencyCode),
                'total'    => (string) $amount,
            ],
            'is_final_capture' => true,
        ];

        $result = $this->send(PayPalRestApi::PATH_AUTHORIZATION.$authorizationId.'/capture', json_encode($data), $header, false, 'POST');
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @param string $payerId
     * @param string $paymentId
     *
     * @return bool|mixed
     */
    public function executePayment($payerId, $paymentId)
    {
        if ($payerId == 'NULL' || $paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $data = ['payer_id' => $payerId];

        $result = $this->send(PayPalRestApi::PATH_EXECUTE_PAYMENT.$paymentId.'/execute/', json_encode($data), $header, false, 'POST');
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @param string $paymentId
     * @param array  $data
     *
     * @return bool|mixed
     */
    public function executeRefund($paymentId, $data)
    {
        if ($paymentId == 'NULL' || !is_object($data)) {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $result = $this->send(PayPalRestApi::PATH_EXECUTE_REFUND.$paymentId.'/refund', json_encode($data), $header);
        if (!$result) {
            return false;
        }

        return json_decode($result);
    }

    /**
     * @param int $type
     *
     * @return array
     */
    protected function createWebProfile($type)
    {
        $name = 'thirtybees_'.(int) $this->context->shop->id.'_'.(int) $type;
        $idLang = (int) \Configuration::get('PS_LANG_DEFAULT');
        $language = new \Language($idLang);
        $iso = \Validate::isLoadedObject($language) ? strtolower($language->iso_code) : 'en';

        switch ($type) {
            case self::PLUS_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => _PS_BASE_URL_._PS_IMG_.\Configuration::get('PS_LOGO'),
                        'locale_code' => \PayPal::getLocaleByIso($iso),
                    ],
                    'input_fields' => [
                        'allow_note'       => false,
                        'no_shipping'      => 2,
                        'address_override' => 0,
                    ],
                    'flow_config'  => [
                        'landing_page_type' => 'billing',
                    ],
                ];
            case self::EXPRESS_CHECKOUT_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => _PS_BASE_URL_._PS_IMG_.\Configuration::get('PS_LOGO'),
                        'locale_code' => \PayPal::getLocaleByIso($iso),
                    ],
                    'input_fields' => [
                        'allow_note'       => false,
                        'no_shipping'      => 2,
                        'address_override' => 0,
                    ],
                    'flow_config'  => [
                        'landing_page_type' => 'billing',
                    ],
                ];
            default:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => \Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => _PS_BASE_URL_._PS_IMG_.\Configuration::get('PS_LOGO'),
                        'locale_code' => \PayPal::getLocaleByIso($iso),
                    ],
                    'input_fields' => [
                        'allow_note'       => false,
                        'no_shipping'      => 2,
                        'address_override' => 0,
                    ],
                    'flow_config'  => [
                        'landing_page_type' => 'billing',
                    ],
                ];
        }
    }
}
