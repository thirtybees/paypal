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
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

use Configuration;
use Context;
use Currency;
use GuzzleHttp\Client;
use ImageManager;
use Language;
use PayPal;
use PayPalModule\Logger\DummyLogger;
use PayPalModule\Logger\Logger;
use PrestaShopException;
use stdClass;
use Throwable;
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

    const STANDARD_PROFILE = 1;
    const PLUS_PROFILE = 2;
    const EXPRESS_CHECKOUT_PROFILE = 3;

    /**
     * @var string $clientId
     */
    protected string $clientId;

    /**
     * @var string $secret
     */
    protected string $secret;

    /**
     * @var string
     */
    protected string $baseUri;

    /**
     * @var Logger
     */
    protected Logger $logger;


    /**
     * ApiPaypalPlus constructor.
     *
     * @param Logger|null $logger
     * @param string|null $clientId
     * @param string|null $secret
     *
     * @throws PrestaShopException
     */
    public function __construct(
        ?string $clientId = null,
        ?string $secret = null,
        ?Logger $logger = null
    )
    {
        $this->logger = $logger
            ? $logger
            : new DummyLogger();

        $this->clientId = $clientId
            ? $clientId
            : (string)Configuration::get(PayPal::CLIENT_ID);

        $this->secret = $secret
            ? $secret
            : (string)Configuration::get(PayPal::SECRET);

        $this->baseUri = !Configuration::get(PayPal::LIVE)
            ? 'https://api.sandbox.paypal.com'
            : 'https://api.paypal.com';
    }

    /**
     * @param int $profileType
     * @return string|null
     * @throws PrestaShopException
     */
    private function getWebProfileId($profileType)
    {
        $profileId = Configuration::get(static::getWebProfileConfigKey($profileType));
        return $profileId ? $profileId : null;
    }

    /**
     * @param int $profileType
     * @return string
     * @throws PrestaShopException
     */
    public static function getWebProfileConfigKey($profileType)
    {
        $profileType = (int)$profileType;
        $live = (bool)Configuration::get(PayPal::LIVE);

        switch ((int)$profileType) {
            case static::STANDARD_PROFILE:
                return $live
                    ? PayPal::STANDARD_WEBSITE_PROFILE_ID_LIVE
                    : PayPal::STANDARD_WEBSITE_PROFILE_ID;
            case static::PLUS_PROFILE:
                return $live
                    ? PayPal::PLUS_WEBSITE_PROFILE_ID_LIVE
                    : PayPal::PLUS_WEBSITE_PROFILE_ID;
            case static::EXPRESS_CHECKOUT_PROFILE:
                return $live
                    ? PayPal::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE
                    : PayPal::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID;
            default:
                throw new PrestaShopException("Invalid profile type: $profileType");
        }
    }

    /**
     * @param int $type
     *
     * @return bool|array
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createWebProfile($type)
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            // DELETE if profile exists
            $this->deleteWebProfile($type);

            // Then CREATE
            $definition = $this->getWebProfileDefinition($type);
            $result = json_decode($this->send(self::PATH_WEBPROFILES, json_encode($definition, JSON_PRETTY_PRINT), $headers, false, 'POST'));

            if (isset($result->id)) {
                return $result->id;
            }
        }

        return false;
    }

    /**
     * @param int $type
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function deleteWebProfile($type)
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            $profiles = $this->getWebProfiles();
            $profileName = $this->getWebProfileName($type, Context::getContext()->shop->id);
            foreach ($profiles as $profile) {
                if ($profile->name == $profileName) {
                    $profileId = $profile->id;
                    $this->send(self::PATH_WEBPROFILES.'/'.$profileId, false, $headers, false, 'DELETE');
                }
            }
        }

        return false;
    }

    /**
     * @param int $profileType
     * @param bool $enabled
     *
     * @return void
     * @throws PrestaShopException
     */
    public function updateWebProfile($profileType, $enabled)
    {
        $configKey = static::getWebProfileConfigKey($profileType);
        if ($enabled) {
            $profileId = $this->createWebProfile($profileType);
            if ($profileId) {
                Configuration::updateValue($configKey, $profileId);
            } else {
                Configuration::deleteByName($configKey);
            }
        } else {
            $this->deleteWebProfile($profileType);
            Configuration::deleteByName($configKey);
        }
    }

    /**
     * @return string|false
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getToken()
    {
        $accessTokens = $this->getValidAccessTokens();
        if (isset($accessTokens[$this->clientId])) {
            return $accessTokens[$this->clientId]['token'];
        }

        $record = $this->authenticate();
        if ($record) {
            $accessTokens[$this->clientId] = $record;
            $this->saveAccessTokens($accessTokens);
            return $record['token'];
        } else {
           return false;
        }
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getValidAccessTokens()
    {
        $now = time();
        $accessTokensString = Configuration::getGlobalValue(PayPal::ACCESS_TOKENS);
        $accessTokens = [];
        if ($accessTokensString) {
            $array = json_decode($accessTokensString, true);
            $update = false;
            if (is_array($array)) {
                foreach ($array as $clientId => $record) {
                    if ($record['expiration'] < $now) {
                        $update = true;
                    } else {
                        $accessTokens[$clientId] = $record;
                    }
                }
            }
            if ($update) {
                $this->saveAccessTokens($accessTokens);
            }
        }
        return $accessTokens;
    }

    /**
     * @param array $accessTokens
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function saveAccessTokens($accessTokens)
    {
        if ($accessTokens) {
            Configuration::updateGlobalValue(PayPal::ACCESS_TOKENS, json_encode($accessTokens));
        } else {
            Configuration::deleteByName(PayPal::ACCESS_TOKENS);
        }
    }

    /**
     * @return array|false
     * @throws PrestaShopException
     */
    protected function authenticate()
    {
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
            $timeMax = time() + $oPayPalToken->expires_in - 10;
            $accessToken = $oPayPalToken->access_token;

            return [
                'token' => $accessToken,
                'expiration' => $timeMax,
            ];
        }
    }

    /**
     * @param string $url URL including get params
     * @param string|false $body
     * @param string[] $headers
     * @param bool $identify
     * @param string $requestType
     *
     * @return string
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function send($url, $body = false, $headers = [], $identify = false, $requestType = 'GET')
    {
        $guzzle = new Client(
            [
                'base_uri'    => $this->baseUri,
                'timeout'     => 60.0,
                'verify'      => _PS_TOOL_DIR_.'cacert.pem',
                'http_errors' => false,
            ]
        );
        $headers['PayPal-Partner-Attribution-Id'] ='thirtybees_SP';
        $requestOptions = [];
        if ($identify) {
            $requestOptions['auth'] = [$this->clientId, $this->secret];
        }
        $requestOptions['headers'] = $headers;
        if ($body) {
            $requestOptions['body'] = (string) $body;
        }

        $uri = '/'.ltrim($url, '/');

        if ($this->logger->isEnabled()) {
            $message = "API REQUEST: $requestType " . $this->baseUri . $uri;
            if ($body) {
                $message .= "\n$body\n";
            }
            $this->logger->log($message);
        }

        try {
            $response = $guzzle->request($requestType, $uri, $requestOptions);
        } catch (Throwable $e) {
            if ($this->logger->isEnabled()) {
                $this->logger->exception($e);
            }
            throw new PrestaShopException("Paypal api failled", 0, $e);
        }

        $res = (string) $response->getBody();

        if ($this->logger->isEnabled()) {
            try  {
                $logData = json_decode($res, true);
                if ($logData) {
                    $logData = json_encode($logData, JSON_PRETTY_PRINT);
                    $this->logger->log("API RESPONSE:\n$logData\n");
                } else {
                    $this->logger->log("API RESPONSE:\n$res\n");
                }
            } catch (Throwable $e) {
                $this->logger->log("API RESPONSE:\n$res\n");
            }
        }

        return $res;
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getWebProfiles()
    {
        $accessToken = $this->getToken();

        if ($accessToken) {
            $header = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$accessToken,
            ];

            $profiles = json_decode($this->send(self::PATH_WEBPROFILES, false, $header));
            return is_array($profiles)
                ? $profiles
                : [];
        }

        return [];
    }

    /**
     * @param bool|string $returnUrl
     * @param bool|string $cancelUrl
     * @param int $profile
     *
     * @return mixed
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPayment($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $data = $this->createPaymentObject($returnUrl, $cancelUrl, $profile);

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$this->getToken(),
        ];
        $result = json_decode($this->send(self::PATH_CREATE_PAYMENT, json_encode($data, JSON_PRETTY_PRINT), $header, false, 'POST'));
        return $result;
    }

    /**
     * @param string|bool $returnUrl
     * @param string|bool $cancelUrl
     * @param int $profile
     *
     * @return stdClass
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPaymentObject($returnUrl = false, $cancelUrl = false, $profile = self::STANDARD_PROFILE)
    {
        $context = Context::getContext();
        $cart = $context->cart;

        if (!$returnUrl) {
            $returnUrl = $context->link->getModuleLink('paypal', 'expresscheckout', ['id_cart' => (int) $cart->id], true);
        }

        if (!$cancelUrl) {
            $cancelUrl = $context->link->getModuleLink('paypal', 'expresscheckoutcancel', ['id_cart' => (int) $cart->id], true);
        }

        $oCurrency = new Currency($cart->id_currency);

        $totalCartWithTax = $cart->getOrderTotal(true);

        $payer = new stdClass();
        $payer->payment_method = 'paypal';

        /* Amount */
        $amount = (object) [
            'total'    => (string)round($totalCartWithTax, 2),
            'currency' => $oCurrency->iso_code,
        ];

        /* Transaction */
        $transaction = (object) [
            'amount' => $amount,
            'custom' => 'CART#' . $cart->id,
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
            'intent'       => 'sale',
        ];
        $payment->experience_profile_id = $this->getWebProfileId($profile);
        $payment->redirect_urls = $redirectUrls;
        return $payment;
    }

    /**
     * @param string $paymentId
     *
     * @return bool|mixed
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function lookUpPayment($paymentId)
    {
        if (!$paymentId) {
            return false;
        }

        $accessToken = $this->getToken();

        $header = [
            'Content-Type'  => 'application/json',
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
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function executePayment($payerId, $paymentId)
    {
        if ($payerId == 'NULL' || $paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->getToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $data = ['payer_id' => $payerId];

        return json_decode($this->send(PayPalRestApi::PATH_EXECUTE_PAYMENT.$paymentId.'/execute/', json_encode($data, JSON_PRETTY_PRINT), $header, false, 'POST'));
    }

    /**
     * @param string $paymentId
     * @param stdClass $data
     *
     * @return bool|mixed
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function executeRefund($paymentId, $data)
    {
        if ($paymentId == 'NULL' || !is_object($data)) {
            return false;
        }

        $accessToken = $this->getToken();

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        return json_decode($this->send(PayPalRestApi::PATH_EXECUTE_REFUND.$paymentId.'/refund', json_encode($data, JSON_PRETTY_PRINT), $header));
    }


    /**
     * @param int $type
     * @param int $shopId
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function getWebProfileName($type, $shopId)
    {
        $type = (int)$type;
        $shopId = (int)$shopId;
        return "tb_" . PayPal::getInstallationId() . '_' . $shopId . '_' . $type;
    }

    /**
     * @param int $type
     * @param bool $adjustLogo
     *
     * @return array
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function getWebProfileDefinition($type, $adjustLogo = true)
    {
        $shopId = (int)Context::getContext()->shop->id;
        $name = $this->getWebProfileName($type, $shopId);
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $language = new Language($idLang);
        $iso = Validate::isLoadedObject($language) ? strtolower($language->iso_code) : 'en';

        $logoUrl = _PS_BASE_URL_SSL_._PS_IMG_. Configuration::get('PS_LOGO');
        if ($adjustLogo) {
            $logo = _PS_IMG_DIR_. Configuration::get('PS_LOGO');
            list($width, $height) = getimagesize($logo);
            // PayPal expects images below 190x60 - let's resize it if our logo is bigger
            if ($width > 190 || $height > 60) {
                $ratio = min(190 / $width, 60 / $height);
                $dstWidth = $width * $ratio;
                $dstHeight = $height * $ratio;

                $ext = substr($logo, strrpos($logo, '.') + 1);
                ImageManager::resize($logo, _PS_IMG_DIR_."logo_{$shopId}_paypal_resized.$ext", $dstWidth, $dstHeight, $ext);
                $logoUrl = _PS_BASE_URL_SSL_._PS_IMG_."logo_{$shopId}_paypal_resized.$ext";
            }
        }

        switch ($type) {
            case self::PLUS_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => $logoUrl,
                        'locale_code' => PayPal::getLocaleByIso($iso),
                    ],
                    'input_fields' => [
                        'allow_note'       => false,
                        'no_shipping'      => 2,
                        'address_override' => 1,
                    ],
                    'flow_config'  => [
                        'landing_page_type' => 'billing',
                    ],
                ];
            case self::EXPRESS_CHECKOUT_PROFILE:
                return [
                    'name'         => $name,
                    'presentation' => [
                        'brand_name'  => Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => $logoUrl,
                        'locale_code' => PayPal::getLocaleByIso($iso),
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
                        'brand_name'  => Configuration::get('PS_SHOP_NAME'),
                        'logo_image'  => $logoUrl,
                        'locale_code' => PayPal::getLocaleByIso($iso),
                    ],
                    'input_fields' => [
                        'allow_note'       => false,
                        'no_shipping'      => 2,
                        'address_override' => 1,
                    ],
                    'flow_config'  => [
                        'landing_page_type' => (Configuration::get(PayPal::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE) == 'login') ? 'login' : 'billing',
                        'user_action' => 'commit',
                    ],
                ];
        }
    }

}
