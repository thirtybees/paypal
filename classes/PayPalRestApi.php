<?php
/**
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

use Adapter_Exception;
use Cart;
use Configuration;
use Context;
use Customer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Language;
use Logger;
use PayPal;
use PayPalModule\Exception\Payment\CaptureException;
use PayPalModule\Exception\Payment\PaymentException;
use PayPalModule\Exception\Auth\TokenException;
use PrestaShopDatabaseException;
use PrestaShopException;
use Validate;

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
    /** @var Context $context */
    protected $context;
    /** @var Cart $cart */
    protected $cart;
    /** @var Customer $customer */
    protected $customer;
    /** @var string $clientId */
    protected static $clientId;
    /** @var string $secret */
    protected static $secret;
    /** @var null|string $accessToken */
    protected static $accessToken;
    /** @var string $accessTokenExpire */
    protected static $accessTokenExpire;
    /** @var null|array $profiles */
    protected $profiles = null;
    /** @var Client $guzzle */
    protected static $guzzle;
    /** @var static $instance */
    protected static $instance;
    // @codingStandardsIgnoreEnd

    /**
     * Set API credentials
     *
     * @param string $clientId
     * @param string $secret
     */
    public static function setCredentials($clientId, $secret)
    {
        static::$clientId = $clientId;
        static::$secret = $secret;
        static::$instance = null;
    }

    /**
     * Get instance
     *
     * @return static
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get the base URI
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getBaseUri()
    {
        return !Configuration::get(PayPal::LIVE)
            ? $baseUri = 'https://api.sandbox.paypal.com'
            : $baseUri = 'https://api.paypal.com';
    }

    /**
     * ApiPaypalPlus constructor.
     *
     * @param string|null $clientId
     * @param string|null $secret
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function __construct()
    {
        $this->context = \Context::getContext();
        $this->cart = $this->context->cart;
        $this->customer = $this->context->customer;
    }

    /**
     * Get Guzzle client
     *
     * @return Client
     * @throws PrestaShopException
     * @throws TokenException
     */
    protected static function getGuzzle()
    {
        if (!static::$guzzle) {
            if (!Configuration::get(PayPal::LIVE)) {
                $baseUri = 'https://api.sandbox.paypal.com';
            } else {
                $baseUri = 'https://api.paypal.com';
            }

            $accessToken = static::getAccessToken();
            static::$guzzle = new Client(
                [
                    'base_uri'    => $baseUri,
                    'timeout'     => PayPal::CONNECTION_TIMEOUT,
                    'verify'      => _PS_TOOL_DIR_.'cacert.pem',
                    'headers'     => ['Authorization' => "Bearer $accessToken"],
                ]
            );
        }

        return static::$guzzle;
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopException
     * @throws TokenException
     */
    protected static function getAccessToken()
    {
        if (static::$accessToken && time() < strtotime(static::$accessTokenExpire.' +2 minutes')) {
            return static::$accessToken;
        }
        if (Configuration::get(PayPal::ACCESS_TOKEN)
            && time() < strtotime(Configuration::get(PayPal::ACCESS_TOKEN_EXPIRE).' +2 minutes')
        ) {
            static::$accessToken = Configuration::get(PayPal::ACCESS_TOKEN);
            static::$accessTokenExpire = date('Y-m-d H:i:s', strtotime(Configuration::get(PayPal::ACCESS_TOKEN_EXPIRE)));

            return static::$accessToken;
        }

        if (!static::$clientId || !static::$secret) {
            throw new TokenException('Trying to request access token without credentials');
        }

        $guzzle = new Client([
            'base_uri'    => static::getBaseUri(),
            'timeout'     => PayPal::CONNECTION_TIMEOUT,
            'verify'      => _PS_TOOL_DIR_.'cacert.pem',
            'auth'        => [static::$clientId, static::$secret],
        ]);

        try {
            $result = (string) $guzzle->post(static::PATH_CREATE_TOKEN, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body'    => http_build_query(['grant_type' => 'client_credentials']),
            ])->getBody();
        } catch (ClientException $e) {
            throw new TokenException(
                'Unable to retrieve access token',
                0,
                null,
                $e->getRequest(),
                $e->getResponse()
            );
        } catch (RequestException $e) {
            throw new TokenException('Unable to retrieve token', 0, $e, $e->getRequest(), $e->getResponse());
        } catch (TransferException $e) {
            throw new TokenException('Unable to retrieve token', 0, $e);
        }
        if (!$result) {
            return false;
        }

        $tokenResult = json_decode($result, true);
        if (!empty($tokenResult['error']) || !isset($tokenResult['access_token']) || !isset($tokenResult['expires_in'])) {
            throw new TokenException(isset($tokenResult['error_description']) ? $tokenResult['error_description'] : 'Unable to retrieve token');
        }

        // TODO: insert scope check here
        static::$accessToken = $tokenResult['access_token'];
        static::$accessTokenExpire = date('Y-m-d H:i:s', time() + (int) $tokenResult['expires_in']);
        Configuration::updateValue(PayPal::ACCESS_TOKEN, static::$accessToken);
        Configuration::updateValue(PayPal::ACCESS_TOKEN_EXPIRE, static::$accessTokenExpire);

        return static::$accessToken;
    }

    /**
     * @param int $type
     *
     * @return bool|array
     *
     * @throws PrestaShopException
     * @throws ClientException
     * @throws TokenException
     */
    public function getWebProfile($type = self::STANDARD_PROFILE)
    {
        $guzzle = static::getGuzzle();
        $data = $this->createWebProfile($type);
        if ($this->profiles) {
            $profileId = '';
            foreach ($this->profiles as $profile) {
                /** @var array $profile */
                if ($profile['name'] == $data['name']) {
                    $profileId = $profile['id'];
                }
            }

            if ($profileId) {
                // DELETE first
                try {
                    $guzzle->delete(static::PATH_WEBPROFILES.'/'.$profileId);
                } catch (ClientException $e) {
                    // Not sure if we should handle incorrect DELETEs, it's kind of fire and forget
                } catch (TransferException $e) {
                    Logger::addLog("PayPal module connection error: {$e->getMessage()}", 3);
                }
            }
        }

        // Then create
        try {
            $result = (string) $guzzle->post(static::PATH_WEBPROFILES, ['json' => $data])->getBody();
        } catch (ClientException $e) {
            $requestBody = $e->getRequest()->getBody();
            $responseBody = $e->getResponse()->getBody();
            Logger::addLog("PayPal module client error: {$requestBody} -- {$responseBody}");
            $result = false;
        }
        if (!$result) {
            return false;
        }

        $result = json_decode($result, true);

        if (isset($result['id'])) {
            return $result;
        }

        return false;
    }

    /**
     * @return array|false
     * @throws PrestaShopException
     */
    public function getWebProfiles()
    {
        try {
            $result = (string) static::getGuzzle()->get(static::PATH_WEBPROFILES)->getBody();
        } catch (ClientException $e) {
            return false;
        } catch (TransferException $e) {
            return false;
        }
        if (!$result) {
            return false;
        }

        $this->profiles = json_decode($result, true);

        return $this->profiles;
    }

    /**
     * Delete a web profile by ID
     *
     * @param string $id Web profile ID
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function deleteProfile($id)
    {
        try {
            static::getGuzzle()->delete(static::PATH_WEBPROFILES.'/'.$id);
        } catch (ClientException $e) {
            return false;
        } catch (TransferException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param bool|string $returnUrl
     * @param bool|string $cancelUrl
     * @param int         $profile
     *
     * @return array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PaymentException
     */
    public function createPayment($returnUrl = null, $cancelUrl = null, $profile = self::STANDARD_PROFILE)
    {
        try {
            $object = $this->createPaymentObject($returnUrl, $cancelUrl, $profile);
            $result = (string) static::getGuzzle()->post(static::PATH_CREATE_PAYMENT, [
                'json' => $object,
            ])->getBody();
        } catch (ClientException $e) {
            $requestBody = (string) $e->getRequest()->getBody();
            $responseBody = (string) $e->getResponse()->getBody();
            Logger::addLog("PayPal module error while creating payment: {$requestBody} -- {$responseBody}", 3);
            throw new PaymentException('Unable to initialize payment', 0, $e, $e->getRequest(), $e->getResponse());
        } catch (TransferException $e) {
            throw new PaymentException('Unable to initialize payment');
        }
        if (!$result) {
            throw new PaymentException('Unable to initialize payment -- empty response');
        }

        return json_decode($result, true);
    }

    /**
     * @param string|null $returnUrl
     * @param string|null $cancelUrl
     * @param int         $profile
     *
     * @return array
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createPaymentObject($returnUrl = null, $cancelUrl = null, $profile = self::STANDARD_PROFILE)
    {
        $cart = $this->cart;

        if (!$returnUrl) {
            $returnUrl = $this->context->link->getModuleLink('paypal', 'expresscheckoutconfirm', ['id_cart' => (int) $cart->id], true);
        }

        if (!$cancelUrl) {
            $cancelUrl = $this->context->link->getModuleLink('paypal', 'expresscheckoutcancel', ['id_cart' => (int) $cart->id], true);
        }

        $oCurrency = new \Currency($this->cart->id_currency);
        $shippingAddress = new \Address((int) $this->cart->id_address_delivery);

        $country = new \Country((int) $shippingAddress->id_country);
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
        $state = new \State($shippingAddress->id_state);
        $shippingAddress = [
            'recipient_name' => $shippingAddress->firstname.' '.$shippingAddress->lastname,
            'line1'          => $shippingAddress->address1,
            'line2'          => $shippingAddress->address2,
            'city'           => $shippingAddress->city,
            'country_code'   => $isoCode,
            'postal_code'    => $shippingAddress->postcode,
            'state'          => ($state->iso_code == null) ? '' : $state->iso_code,
        ];

        $payer = [];
        $payer['payment_method'] = 'paypal';

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
                $aItems[] = [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => $quantity - 1,
                    'price'    => number_format($cartItem['price'], 2),
                    'tax'      => number_format($cartItem['price_wt'] - $cartItem['price'], 2),
                ];
                $remaining -= round($cartItem['price'], 2) * ($quantity - 1);
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) * ($quantity - 1);
                $subTotal += round($cartItem['price'], 2) * ($quantity - 1);
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) * ($quantity - 1);
                $aItems[] = [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => 1,
                    'price'    => number_format($cartItem['price'], 2) + $lastItemPriceDifference,
                    'tax'      => number_format($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference,
                ];
                $remaining -= round($cartItem['price'], 2) + $lastItemPriceDifference;
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference;
                $subTotal += round($cartItem['price'], 2) + $lastItemPriceDifference;
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) + $lastItemTaxDifference;
            } else {
                $aItems[] = [
                    'name'     => $cartItem['name'],
                    'currency' => strtoupper($oCurrency->iso_code),
                    'quantity' => $quantity,
                    'price'    => number_format($cartItem['price'], 2),
                    'tax'      => number_format($cartItem['price_wt'] - $cartItem['price'], 2),
                ];
                $remaining -= round($cartItem['price'], 2) * $quantity;
                $remaining -= round($cartItem['price_wt'] - $cartItem['price'], 2) * $quantity;
                $subTotal += round($cartItem['price'], 2) * $quantity;
                $subTotalTax += round($cartItem['price_wt'] - $cartItem['price'], 2) * $quantity;
            }
        }

        $details = [
            'shipping'     => number_format($totalShippingCostWithoutTax, 2),
            'tax'          => number_format($subTotalTax, 2),
            'subtotal'     => number_format($subTotal, 2),
        ];

        $remaining -= round($totalShippingCostWithoutTax, 2);

        // Now we are going to handle the differences
        // if despite the gift wrapping costs, the remaining number is negative, we have applied some discounts
        // that couldn't be handled in a PayPal way. Therefore, we fill the `shipping_discount` field.
        if (round($remaining - $giftWithoutTax, 2) < 0) {
            $details['shipping_discount'] = number_format(abs($remaining - $giftWithoutTax), 2);
        } else {
            $details['handling_fee'] = number_format($remaining - $giftWithoutTax, 2);
        }

        /* Amount */
        $amount = [
            'total'    => number_format($totalCartWithTax, 2),
            'currency' => $oCurrency->iso_code,
            'details'  => $details,
        ];

        /* Transaction */
        $transaction = [
            'amount'      => $amount,
            'description' => 'Payment description',
            'item_list'   => [
                'items' => $aItems,
                'shipping_address' => Validate::isLoadedObject($shippingAddress) && PayPal::checkAddress($shippingAddress) ? $shippingAddress : null,
            ],
        ];

        /* Redirect Url */
        $redirectUrls = [
            'cancel_url' => $cancelUrl,
            'return_url' => $returnUrl,
        ];

        /* Payment */
        $payment = [
            'transactions' => [$transaction],
            'payer'        => $payer,
            'intent'       => 'authorize',
        ];
        if (Configuration::get(PayPal::LIVE)) {
            switch ($profile) {
                case static::PLUS_PROFILE:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::PLUS_WEBSITE_PROFILE_ID_LIVE);
                    break;
                case static::EXPRESS_CHECKOUT_PROFILE:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE);
                    break;
                default:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::STANDARD_WEBSITE_PROFILE_ID_LIVE);
                    break;
            }
        } else {
            switch ($profile) {
                case static::PLUS_PROFILE:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::PLUS_WEBSITE_PROFILE_ID);
                    break;
                case static::EXPRESS_CHECKOUT_PROFILE:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID);
                    break;
                default:
                    $payment['experience_profile_id'] = Configuration::get(PayPal::STANDARD_WEBSITE_PROFILE_ID);
                    break;
            }
        }

        $payment['redirect_urls'] = $redirectUrls;

        return $payment;
    }

    /**
     * @param array $params
     *
     * @throws \PrestaShopException
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
     * @throws PrestaShopException
     */
    public function lookUpPayment($paymentId)
    {
        if (!$paymentId) {
            return false;
        }

        $result = (string) static::getGuzzle()->get(PayPalRestApi::PATH_LOOK_UP.$paymentId)->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * Register a webhook
     *
     * @param string $webhookUrl
     *
     * @return array|false
     * @throws PrestaShopException
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

        $data = [
            'url'         => $webhookUrl,
            'event_types' => $types,
        ];

        if (!Configuration::get(PayPal::LIVE) && !empty($_COOKIE['PayPal-Mock-Response'])) {
            $header[] = ['PayPal-Mock-Response' => $_COOKIE['PayPal-Mock-Response']];
        }

        $result = (string) static::getGuzzle()->post(
            rtrim(PayPalRestApi::PATH_WEBHOOK, '/'),
            ['json' => $data]
        )->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * Get a list of webhooks
     *
     * @return bool|mixed
     * @throws PrestaShopException
     * @throws TokenException
     */
    public function getWebhooks()
    {
        $result = (string) static::getGuzzle()->get(rtrim(PayPalRestApi::PATH_WEBHOOK, '/'))->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param string $webhookId
     *
     * @return bool|mixed
     * @throws PrestaShopException
     */
    public function lookUpWebhook($webhookId)
    {
        if (!$webhookId) {
            return false;
        }

        $result = (string) static::getGuzzle()->get(PayPalRestApi::PATH_WEBHOOK_EVENT.$webhookId)->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param string $authorizationId
     *
     * @return bool|mixed
     * @throws PrestaShopException
     */
    public function voidAuthorization($authorizationId)
    {
        $result = static::getGuzzle()->post(
            PayPalRestApi::PATH_AUTHORIZATION.$authorizationId.'/void',
            '{}'
        );
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param string $authorizationId
     * @param float  $amount
     * @param string $currencyCode
     *
     * @return false|array
     * @throws PrestaShopException
     * @throws CaptureException
     */
    public function capturePayment($authorizationId, $amount, $currencyCode)
    {
        $data = [
            'amount' => [
                'currency' => strtoupper($currencyCode),
                'total'    => number_format($amount, 2),
            ],
            'is_final_capture' => true,
        ];

        try {
            $result = (string) static::getGuzzle()->post(
                PayPalRestApi::PATH_AUTHORIZATION.$authorizationId.'/capture',
                ['json' => $data]
            )->getBody();
        } catch (ClientException $e) {
            throw new CaptureException('Unable to capture payment', 0, $e, $e->getRequest(), $e->getResponse());
        } catch (TransferException $e) {
            throw new CaptureException('Unable to capture payment', 0, $e);
        }
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param string $payerId
     * @param string $paymentId
     *
     * @return false|array
     * @throws PrestaShopException
     */
    public function executePayment($payerId, $paymentId)
    {
        if ($payerId === 'NULL' || $paymentId === 'NULL') {
            return false;
        }

        $result = (string) static::getGuzzle()->post(
            PayPalRestApi::PATH_EXECUTE_PAYMENT.$paymentId.'/execute/',
            ['json' => ['payer_id' => $payerId]]
        )->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param string $paymentId
     * @param array  $data
     *
     * @return false|array
     * @throws PrestaShopException
     */
    public function executeRefund($paymentId, $data)
    {
        if ($paymentId == 'NULL' || !is_object($data)) {
            return false;
        }

        $result = (string) static::getGuzzle()->post(PayPalRestApi::PATH_EXECUTE_REFUND.$paymentId.'/refund', ['json' => $data])->getBody();
        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * @param int $type
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function createWebProfile($type)
    {
        $name = 'thirtybees_'.(int) $this->context->shop->id.'_'.(int) $type;
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $language = new Language($idLang);
        $iso = Validate::isLoadedObject($language) ? strtolower($language->iso_code) : 'en';
        $brandName = Configuration::get('PS_SHOP_NAME');
        $logoImage = _PS_BASE_URL_._PS_IMG_.Configuration::get('PS_LOGO');

        switch ($type) {
            case static::PLUS_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => $brandName,
                        'logo_image'  => $logoImage,
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
            case static::EXPRESS_CHECKOUT_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => $brandName,
                        'logo_image'  => $logoImage,
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
                        'brand_name'  => $brandName,
                        'logo_image'  => $logoImage,
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
