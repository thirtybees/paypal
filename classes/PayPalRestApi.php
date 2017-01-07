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
    const URL_PPP_CREATE_TOKEN = '/v1/oauth2/token';
    const URL_PPP_CREATE_PAYMENT = '/v1/payments/payment';
    const URL_PPP_LOOK_UP = '/v1/payments/payment/';
    const URL_PPP_WEBPROFILE = '/v1/payment-experience/web-profiles';
    const URL_PPP_EXECUTE_PAYMENT = '/v1/payments/payment/';
    const URL_PPP_EXECUTE_REFUND = '/v1/payments/sale/';

    /**
     * ApiPaypalPlus constructor.
     */
    public function __construct()
    {
        $this->context = \Context::getContext();
    }

    /**
     * @param      $url
     * @param      $body
     * @param bool $httpHeader
     * @param bool $identify
     *
     * @return mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function sendWithCurl($url, $body, $httpHeader = false, $identify = false)
    {
        $ch = curl_init();

        if ($ch) {
            if ((int) \Configuration::get('PAYPAL_SANDBOX') == 1) {
                curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com'.$url);
            } else {
                curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com'.$url);
            }

            if ($identify) {
                curl_setopt($ch, CURLOPT_USERPWD, \Configuration::get('PAYPAL_PLUS_CLIENT_ID').':'.\Configuration::get('PAYPAL_PLUS_SECRET'));
            }

            if ($httpHeader) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }
            if ($body) {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($identify) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }

            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSv1') ? CURL_SSLVERSION_TLSv1 : 1);
            curl_setopt($ch, CURLOPT_VERBOSE, false);

            $result = curl_exec($ch);

            curl_close($ch);
        }

        return isset($result) ? $result : false;
    }

    /**
     * @param string $url
     * @param string $body
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getToken($url, $body)
    {
        $result = $this->sendWithCurl($url, $body, false, true);

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
            $this->context->cookie->__set('paypal_access_token_time_max', $timeMax);
            $this->context->cookie->__set('paypal_access_token_access_token', $accessToken);
            $this->context->cookie->write();

            return $accessToken;
        }
    }

    /**
     * @return \stdClass
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function createWebProfile()
    {

        $presentation = new \stdClass();
        $presentation->brand_name = \Configuration::get('PS_SHOP_NAME');
        $presentation->logo_image = _PS_BASE_URL_.__PS_BASE_URI__.'img/logo.jpg';
        $presentation->locale_code = \Tools::strtoupper(\Language::getIsoById($this->context->language->id));

        $inputFields = new \stdClass();
        $inputFields->allow_note = true;
        $inputFields->no_shipping = 1;
        $inputFields->address_override = 1;

        $flowConfig = new \stdClass();
        $flowConfig->landing_page_type = 'billing';

        $webProfile = new \stdClass();
        $webProfile->name = \Configuration::get('PS_SHOP_NAME');
        $webProfile->presentation = $presentation;
        $webProfile->input_fields = $inputFields;
        $webProfile->flow_config = $flowConfig;

        return $webProfile;
    }

    /**
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getWebProfile()
    {
        $accessToken = $this->getToken(self::URL_PPP_CREATE_TOKEN, ['grant_type' => 'client_credentials']);

        if ($accessToken) {
            $data = $this->createWebProfile();

            $header = [
                'Content-Type:application/json',
                'Authorization:Bearer '.$accessToken,
            ];

            $result = json_decode($this->sendWithCurl(self::URL_PPP_WEBPROFILE, json_encode($data), $header));

            if (isset($result->id)) {
                return $result->id;
            } else {
                $results = $this->getListProfile();

                foreach ($results as $result) {
                    if (isset($result->id) && $result->name == \Configuration::get('PS_SHOP_NAME')) {
                        return $result->id;
                    }
                }

                return false;
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
        $accessToken = $this->getToken(self::URL_PPP_CREATE_TOKEN, ['grant_type' => 'client_credentials']);

        if ($accessToken) {
            $header = [
                'Content-Type:application/json',
                'Authorization:Bearer '.$accessToken,
            ];

            return json_decode($this->sendWithCurl(self::URL_PPP_WEBPROFILE, false, $header));
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
    public function refreshToken()
    {
        if ($this->context->cookie->paypal_access_token_time_max < time()) {
            return $this->getToken(self::URL_PPP_CREATE_TOKEN, ['grant_type' => 'client_credentials']);
        } else {
            return $this->context->cookie->paypal_access_token_access_token;
        }
    }

    /**
     * @param $customer
     * @param $cart
     *
     * @return \stdClass
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPaymentObject(\Customer $customer, \Cart $cart)
    {
        /*
         * Init Variable
         */
        $oCurrency = new \Currency($cart->id_currency);
        $address = new \Address((int) $cart->id_address_invoice);

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

        $shopUrl = \PayPal::getShopDomainSsl(true, true);

        /*
         * Création de l'obj à envoyer à Paypal
         */

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
        $redirectUrls->cancel_url = $this->context->link->getModuleLink('paypal', 'pluscancel', ['id_cart' => (int) $cart->id], \Tools::usingSecureMode());
        $redirectUrls->return_url = $this->context->link->getModuleLink('paypal', 'plussubmit', ['id_cart' => (int) $cart->id], \Tools::usingSecureMode());

        /* Payment */
        $payment = new \stdClass();
        $payment->transactions = [$transaction];
        $payment->payer = $payer;
        $payment->intent = 'sale';
        if (\Configuration::get('PAYPAL_WEB_PROFILE_ID')) {
            $payment->experience_profile_id = \Configuration::get('PAYPAL_WEB_PROFILE_ID');
        }
        $payment->redirect_urls = $redirectUrls;

        return $payment;
    }

    /**
     * @param $customer
     * @param $cart
     * @param $accessToken
     *
     * @return mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createPayment($customer, $cart, $accessToken)
    {

        $data = $this->createPaymentObject($customer, $cart);

        $header = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        ];

        $result = $this->sendWithCurl(self::URL_PPP_CREATE_PAYMENT, json_encode($data), $header);

        return $result;
    }
}
