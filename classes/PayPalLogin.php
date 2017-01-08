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
 * Class PayPalLogin
 *
 * @package PayPalModule
 */
class PayPalLogin
{
    protected $logs = [];
    protected $enableLog = false;

    protected $paypalConnect = null;

    /**
     * PayPalLogin constructor.
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct()
    {
        $this->paypalConnect = new PayPalConnect();
    }

    /**
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getIdentityAPIURL()
    {
        if (!\Configuration::get(\PayPal::LIVE)) {
            //return 'www.sandbox.paypal.com';
            return 'api.sandbox.paypal.com';
        } else {
            return 'api.paypal.com';
        }

    }

    /**
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getTokenServiceEndpoint()
    {
        if (!\Configuration::get(\PayPal::LIVE)) {
            // return '/webapps/auth/protocol/openidconnect/v1/tokenservice';
            return '/v1/identity/openidconnect/tokenservice';
        } else {
            return '/v1/identity/openidconnect/tokenservice';
        }

    }

    /**
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getUserInfoEndpoint()
    {
        return '/v1/identity/openidconnect/userinfo';
    }

    /**
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getReturnLink()
    {
        return \Context::getContext()->link->getModuleLink('paypal', 'logintoken', [], \Tools::usingSecureMode());
    }

    /**
     * @return array|bool|mixed|PayPalLoginUser
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getAuthorizationCode()
    {
        unset($this->logs);

        $context = \Context::getContext();
        $isLogged = $context->customer->isLogged();

        if ($isLogged) {
            return $this->getRefreshToken();
        }

        $params = [
            'grant_type' => 'authorization_code',
            'code' => \Tools::getValue('code'),
            'redirect_url' => PayPalLogin::getReturnLink(),
        ];

        $request = http_build_query($params, '', '&');
        $result = $this->paypalConnect->makeConnection($this->getIdentityAPIURL(), $this->getTokenServiceEndpoint(), $request, false, false, true);

        if ($this->enableLog === true) {
            $handle = fopen(dirname(__FILE__).'/Results.txt', 'a+');
            fwrite($handle, "Request => ".print_r($request, true)."\r\n");
            fwrite($handle, "Result => ".print_r($result, true)."\r\n");
            fwrite($handle, "Journal => ".print_r($this->logs, true."\r\n"));
            fclose($handle);
        }

        $result = json_decode($result);

        if ($result && isset($result->access_token)) {
            $login = new PayPalLoginUser();

            $customer = $this->getUserInformations($result->access_token, $login);

            if (!$customer) {
                return false;
            }

            $temp = PayPalLoginUser::getByIdCustomer((int) $context->customer->id);

            if ($temp) {
                $login = $temp;
            }

            $login->id_customer = $customer->id;
            $login->token_type = $result->token_type;
            $login->expires_in = (string) (time() + (int) $result->expires_in);
            $login->refresh_token = $result->refresh_token;
            $login->id_token = $result->id_token;
            $login->access_token = $result->access_token;

            $login->save();

            return $login;
        }

        return false;
    }

    /**
     * @return array|bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getRefreshToken()
    {
        unset($this->logs);
        $login = PayPalLoginUser::getByIdCustomer((int) \Context::getContext()->customer->id);

        if (!is_object($login)) {
            return false;
        }

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $login->refresh_token,
        ];

        $request = http_build_query($params, '', '&');
        $result = $this->paypalConnect->makeConnection($this->getIdentityAPIURL(), $this->getTokenServiceEndpoint(), $request, false, false, true);

        if ($this->enableLog === true) {
            $handle = fopen(dirname(__FILE__).'/Results.txt', 'a+');
            fwrite($handle, "Request => ".print_r($request, true)."\r\n");
            fwrite($handle, "Result => ".print_r($result, true)."\r\n");
            fwrite($handle, "Journal => ".print_r($this->logs, true."\r\n"));
            fclose($handle);
        }

        $result = json_decode($result);

        if ($result) {
            $login->access_token = $result->access_token;
            $login->expires_in = (string) (time() + $result->expires_in);
            $login->save();

            return $login;
        }

        return false;
    }

    /**
     * @param $accessToken
     * @param $login
     *
     * @return bool|\Customer
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function getUserInformations($accessToken, &$login)
    {
        unset($this->logs);
        $headers = [
            // 'Content-Type:application/json',
            'Authorization: Bearer '.$accessToken,
        ];

        $params = [
            'schema' => 'openid',
        ];

        $request = http_build_query($params, '', '&');
        $result = $this->paypalConnect->makeConnection($this->getIdentityAPIURL(), $this->getUserInfoEndpoint(), $request, false, $headers, true);

        if ($this->enableLog === true) {
            $handle = fopen(dirname(__FILE__).'/Results.txt', 'a+');
            fwrite($handle, "Request => ".print_r($request, true)."\r\n");
            fwrite($handle, "Result => ".print_r($result, true)."\r\n");
            fwrite($handle, "Headers => ".print_r($headers, true)."\r\n");
            fwrite($handle, "Journal => ".print_r($this->logs, true."\r\n"));
            fclose($handle);
        }

        $result = json_decode($result);

        if ($result) {
            $customer = new \Customer();
            $customer = $customer->getByEmail($result->email);

            if (!$customer) {
                $customer = $this->setCustomer($result);
            }

            $login->account_type = $result->account_type;
            $login->user_id = $result->user_id;
            $login->verified_account = $result->verified_account;
            $login->zoneinfo = $result->zoneinfo;
            $login->age_range = $result->age_range;

            return $customer;
        }

        return false;
    }

    /**
     * @param $result
     *
     * @return \Customer
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function setCustomer($result)
    {
        $customer = new \Customer();
        $customer->firstname = $result->given_name;
        $customer->lastname = $result->family_name;
        if (version_compare(_PS_VERSION_, '1.5.3.1', '>')) {
            $customer->id_lang = \Language::getIdByIso(strstr($result->language, '_', true));
        }

        $customer->birthday = $result->birthday;
        $customer->email = $result->email;
        $customer->passwd = \Tools::encrypt(\Tools::passwdGen());
        $customer->save();

        $resultAddress = $result->address;

        $address = new \Address();
        $address->id_customer = $customer->id;
        $address->id_country = \Country::getByIso($resultAddress->country);
        $address->alias = 'My address';
        $address->lastname = $customer->lastname;
        $address->firstname = $customer->firstname;
        $address->address1 = $resultAddress->street_address;
        $address->postcode = $resultAddress->postal_code;
        $address->city = $resultAddress->locality;
        $address->phone = $result->phone_number;

        $address->save();

        return $customer;
    }
}
