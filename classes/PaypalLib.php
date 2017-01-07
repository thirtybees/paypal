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

define('PAYPAL_API_VERSION', '106.0');

class PaypalLib
{
    protected $enableLog = false;
    protected $logs = [];
    protected $paypal = null;

    /**
     * PaypalLib constructor.
     */
    public function __construct()
    {
        $this->paypal = \Module::getInstanceByName('paypal');
    }

    /**
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param        $host
     * @param        $script
     * @param        $methodName
     * @param        $data
     * @param string $methodVersion
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function makeCall($host, $script, $methodName, $data, $methodVersion = '')
    {
        // Making request string
        $methodVersion = (!empty($methodVersion)) ? $methodVersion : PAYPAL_API_VERSION;

        $params = [
            'METHOD' => $methodName,
            'VERSION' => $methodVersion,
            'PWD' => \Configuration::get('PAYPAL_API_PASSWORD'),
            'USER' => \Configuration::get('PAYPAL_API_USER'),
            'SIGNATURE' => \Configuration::get('PAYPAL_API_SIGNATURE'),
        ];

        $request = http_build_query($params, '', '&');
        $request .= '&'.(!is_array($data) ? $data : http_build_query($data, '', '&'));

        // Making connection
        $result = $this->makeSimpleCall($host, $script, $request, true);
        $response = explode('&', $result);
        $logsRequest = $this->logs;
        $return = [];

        if ($this->enableLog === true) {
            $handle = fopen(dirname(__FILE__).'/Results.txt', 'a+');
            fwrite($handle, 'Host : '.print_r($host, true)."\r\n");
            fwrite($handle, 'Request : '.print_r($request, true)."\r\n");
            fwrite($handle, 'Result : '.print_r($result, true)."\r\n");
            fwrite($handle, 'Logs : '.print_r($this->logs, true."\r\n"));
            fclose($handle);
        }

        foreach ($response as $value) {
            $tmp = explode('=', $value);
            $return[$tmp[0]] = urldecode(!isset($tmp[1]) ? $tmp[0] : $tmp[1]);
        }

        if (!\Configuration::get('PAYPAL_DEBUG_MODE')) {
            $this->logs = [];
        }

        $toExclude = ['TOKEN', 'SUCCESSPAGEREDIRECTREQUESTED', 'VERSION', 'BUILD', 'ACK', 'CORRELATIONID'];
        $this->logs[] = $this->paypal->l('PayPal response:');

        foreach ($return as $key => $value) {
            if (!\Configuration::get('PAYPAL_DEBUG_MODE') && in_array($key, $toExclude)) {
                continue;
            }

            $this->logs[] = $key.' -> '.$value;
        }

        if (count($this->logs) <= 2) {
            $this->logs = array_merge($this->logs, $logsRequest);
        }

        return $return;
    }

    /**
     * @param      $host
     * @param      $script
     * @param      $request
     * @param bool $simpleMode
     *
     * @return bool|mixed|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function makeSimpleCall($host, $script, $request, $simpleMode = false)
    {
        // Making connection
        $payPalConnect = new PayPalConnect();

        $result = $payPalConnect->makeConnection($host, $script, $request, $simpleMode);
        $this->logs = $payPalConnect->getLogs();

        return $result;
    }
}
