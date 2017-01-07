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
 * Class CallPayPalPlusApi
 *
 * @package PayPalModule
 */
class CallPayPalPlusApi extends PayPalRestApi
{
    protected $cart = null;
    protected $customer = null;

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
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getApprovalUrl()
    {
        $accessToken = $this->getToken(PayPalRestApi::URL_PPP_CREATE_TOKEN, ['grant_type' => 'client_credentials']);

        if ($accessToken != false) {
            $result = json_decode($this->createPayment($this->customer, $this->cart, $accessToken));

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
        if ($paymentId == 'NULL') {
            return false;
        }

        $accessToken = $this->refreshToken();

        $header = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        ];

        return $this->sendWithCurl(PayPalRestApi::URL_PPP_LOOK_UP.$paymentId, false, $header);
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
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        ];

        $data = ['payer_id' => $payerId];

        return $this->sendWithCurl(PayPalRestApi::URL_PPP_EXECUTE_PAYMENT.$paymentId.'/execute/', json_encode($data), $header);
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
            'Content-Type:application/json',
            'Authorization:Bearer '.$accessToken,
        ];

        return $this->sendWithCurl(PayPalRestApi::URL_PPP_EXECUTE_REFUND.$paymentId.'/refund', json_encode($data), $header);
    }
}
