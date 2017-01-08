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
 * Class PayPalConnect
 *
 * @package PayPalModule
 */
class PayPalConnect
{
    protected $logs = [];
    protected $paypal = null;

    /**
     * PayPalConnect constructor.
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct()
    {
        $this->paypal = new \PayPal();
    }

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     *
     * @param string $host
     * @param string $script
     * @param string $body
     * @param bool   $simpleMode
     * @param bool   $httpHeader
     * @param bool   $identify
     *
     * @return bool|mixed|string
     */
    public function makeConnection($host, $script, $body, $simpleMode = false, $httpHeader = false, $identify = false)
    {
        $this->logs[] = $this->paypal->l('Making new connection to').' \''.$host.$script.'\'';

        return $this->sendWithCurl($host.$script, $body, $httpHeader, $identify);
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

    /************************************************************/
    /********************** CONNECT METHODS *********************/
    /************************************************************/
    /**
     * @param      $url
     * @param      $body
     * @param bool $httpHeader
     * @param bool $identify
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function sendWithCurl($url, $body, $httpHeader = false, $identify = false)
    {
        $ch = @curl_init();

        if (!$ch) {
            $this->logs[] = $this->paypal->l('Connect failed with CURL method');
        } else {
            $this->logs[] = $this->paypal->l('Connect with CURL method successful');
            $this->logs[] = '<b>'.$this->paypal->l('Sending this params:').'</b>';
            $this->logs[] = $body;

            @curl_setopt($ch, CURLOPT_URL, 'https://'.$url);

            if ($identify) {
                @curl_setopt($ch, CURLOPT_USERPWD, \Configuration::get(\PayPal::CLIENT_ID).':'.\Configuration::get(\PayPal::SECRET));
            }

            @curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                @curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_HEADER, false);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            @curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/../cacert.pem');
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            @curl_setopt($ch, CURLOPT_SSLVERSION, 6);

            @curl_setopt($ch, CURLOPT_VERBOSE, false);
            if ($httpHeader) {
                @curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }

            $result = @curl_exec($ch);

            if (!$result) {
                $this->logs[] = $this->paypal->l('Send with CURL method failed ! Error:').' '.curl_error($ch);
                if (curl_errno($ch)) {
                    $this->logPayPal(curl_error($ch));
                }

            } else {
                $this->logs[] = $this->paypal->l('Send with CURL method successful');
            }

            @curl_close($ch);
        }

        return (isset($result) && $result) ? $result : false;
    }

    /**
     * @param $host
     * @param $script
     * @param $length
     *
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function createHeader($host, $script, $length)
    {
        return implode("\r\n", [
            'POST '.(string) $script.' HTTP/1.1',
            'Host: '.(string) $host,
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '.(int) $length,
            'Connection: close',
        ])."\r\n\r\n";
    }

    /**
     * @param $message
     *
     * @return bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function logPayPal($message)
    {
        try {
            $date = date('Ymd');
            $path = _PS_MODULE_DIR_.'paypal/log/';
            $context = \Context::getContext();
            file_put_contents($path.$date.'_paypal_curl.log', date('d/m/Y H:i:s').' cart : '.$context->cart->id.' => '.$message.PHP_EOL, FILE_APPEND);
            $dateLastPurge = \Configuration::get('PAYPAL_PURGE_LOG_DATE');
            // if date not set : set at yesterday
            if (!$dateLastPurge) {
                $dateLastPurge = date('Ymd', strtotime('yesterday'));
            }
            if ($dateLastPurge < $date) {
                $dateLimitPurge = date('Ymd', strtotime('-1 month'));
                $dir = opendir($path);
                while ($file = readdir($dir)) {
                    $dateFile = \Tools::substr($file, 0, 8);
                    if ($file != '.' && $file != '..' && $dateFile <= $dateLimitPurge) {
                        unlink($path.$file);
                    }
                }
                \Configuration::updateValue('PAYPAL_PURGE_LOG_DATE', $date);
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
