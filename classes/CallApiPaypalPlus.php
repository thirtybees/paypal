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

define('URL_PPP_CREATE_TOKEN', '/v1/oauth2/token');
define('URL_PPP_CREATE_PAYMENT', '/v1/payments/payment');
define('URL_PPP_LOOK_UP', '/v1/payments/payment/');
define('URL_PPP_WEBPROFILE', '/v1/payment-experience/web-profiles');
define('URL_PPP_EXECUTE_PAYMENT', '/v1/payments/payment/');
define('URL_PPP_EXECUTE_REFUND', '/v1/payments/sale/');

class CallApiPayPalPlus extends ApiPayPalPlus
{
    protected $cart = null;
    protected $customer = null;

    /**
     * @param $params
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
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getApprovalUrl()
    {
        /*
         * Récupération du token
         */
        $accessToken = $this->getToken(URL_PPP_CREATE_TOKEN, array('grant_type' => 'client_credentials'));

        if ($accessToken != false) {

            $result = \Tools::jsonDecode($this->createPayment($this->customer, $this->cart, $accessToken));

            if (isset($result->links)) {

                foreach ($result->links as $link) {

                    if ($link->rel == 'approval_url') {
                        return $link->href;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $paymentId
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function lookUpPayment($paymentId)
    {

        if ($paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = array(
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        );

        return $this->sendByCURL(URL_PPP_LOOK_UP.$paymentId, false, $header);
    }

    /**
     * @param $payer_id
     * @param $paymentId
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function executePayment($payer_id, $paymentId)
    {

        if ($payer_id == 'NULL' || $paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = array(
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        );

        $data = array('payer_id' => $payer_id);

        return $this->sendByCURL(URL_PPP_EXECUTE_PAYMENT.$paymentId.'/execute/', \Tools::jsonEncode($data), $header);
    }

    /**
     * @param $paymentId
     * @param $data
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

        $header = array(
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        );

        return $this->sendByCURL(URL_PPP_EXECUTE_REFUND.$paymentId.'/refund', \Tools::jsonEncode($data), $header);
    }
}
