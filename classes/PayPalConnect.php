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

class PayPalConnect
{
    protected $_logs = array();
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
     */
    public function makeConnection($host, $script, $body, $simple_mode = false, $http_header = false, $identify = false)
    {
        $this->_logs[] = $this->paypal->l('Making new connection to').' \''.$host.$script.'\'';

        if (function_exists('curl_exec')) {
            $return = $this->_connectByCURL($host.$script, $body, $http_header, $identify);
        }

        if (isset($return) && $return) {
            return $return;
        }

        $tmp = $this->_connectByFSOCK($host, $script, $body);

        if (!$simple_mode || !preg_match('/[A-Z]+=/', $tmp, $result)) {
            return $tmp;
        }

        return \Tools::substr($tmp, strpos($tmp, $result[0]));
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
        return $this->_logs;
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
    protected function _connectByCURL($url, $body, $httpHeader = false, $identify = false)
    {
        $ch = @curl_init();

        if (!$ch) {
            $this->_logs[] = $this->paypal->l('Connect failed with CURL method');
        } else {
            $this->_logs[] = $this->paypal->l('Connect with CURL method successful');
            $this->_logs[] = '<b>'.$this->paypal->l('Sending this params:').'</b>';
            $this->_logs[] = $body;

            @curl_setopt($ch, CURLOPT_URL, 'https://'.$url);

            if ($identify) {
                @curl_setopt($ch, CURLOPT_USERPWD, \Configuration::get('PAYPAL_LOGIN_CLIENT_ID').':'.\Configuration::get('PAYPAL_LOGIN_SECRET'));
            }

            @curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                @curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_HEADER, false);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            //@curl_setopt($ch, CURLOPT_SSLVERSION, Configuration::get('PAYPAL_VERSION_TLS_CHECKED') == '1.2' ? 6 : 1);

            @curl_setopt($ch, CURLOPT_VERBOSE, false);
            if ($httpHeader) {
                @curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }

            $result = @curl_exec($ch);

            if (!$result) {
                $this->_logs[] = $this->paypal->l('Send with CURL method failed ! Error:').' '.curl_error($ch);
                if (curl_errno($ch)) {
                    $this->_logPaypal(curl_error($ch));
                }

            } else {
                $this->_logs[] = $this->paypal->l('Send with CURL method successful');
            }

            @curl_close($ch);
        }
        return $result ? $result : false;
    }

    /**
     * @param $host
     * @param $script
     * @param $body
     *
     * @return bool|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function _connectByFSOCK($host, $script, $body)
    {
        $fp = @fsockopen('tls://'.$host, 443, $errno, $errstr, 4);
        
        if (!$fp) {
            $this->_logs[] = $this->paypal->l('Connect failed with fsockopen method');
        } else {
            $header = $this->_makeHeader($host, $script, \Tools::strlen($body));
            $this->_logs[] = $this->paypal->l('Sending this params:').' '.$header.$body;

            @fputs($fp, $header.$body);

            $tmp = '';
            while (!feof($fp)) {
                $tmp .= trim(fgets($fp, 1024));
            }

            fclose($fp);

            if (!isset($tmp) || $tmp == false) {
                $this->_logs[] = $this->paypal->l('Send with fsockopen method failed !');
            } else {
                $this->_logs[] = $this->paypal->l('Send with fsockopen method successful');
            }

        }
        return isset($tmp) ? $tmp : false;
    }

    /**
     * @param $host
     * @param $script
     * @param $lenght
     *
     * @return string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function _makeHeader($host, $script, $lenght)
    {
        return 'POST '.(string) $script.' HTTP/1.1'."\r\n".
        'Host: '.(string) $host."\r\n".
        'Content-Type: application/x-www-form-urlencoded'."\r\n".
        'Content-Length: '.(int) $lenght."\r\n".
            'Connection: close'."\r\n\r\n";
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
    protected function _logPaypal($message){
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
    }
}
