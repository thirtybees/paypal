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

class TLSVerificator
{
    /** @var float $tlsVersion */
    protected $tlsVersion;

    /** @var string $url */
    protected $url;

    /** @var \PayPal $paypal */
    protected $paypal;

    /** @var array $logs */
    protected $logs;

    /**
     * TLSVerificator constructor.
     *
     * @param $check
     * @param \PayPal $paypal
     */
    public function __construct($check, \PayPal $paypal)
    {
        $this->url = 'https://www.howsmyssl.com/a/check';
        $this->paypal = $paypal;
        if ($check) {
            $this->makeCheck();
        }

    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->tlsVersion;
    }

    /**
     * @return bool
     */
    public function makeCheck()
    {
        if (function_exists('curl_exec')) {
            $tlsCheck = $this->connectWithCurl($this->url);
        } else {
            $tlsCheck = file_get_contents($this->url);
        }

        if ($tlsCheck == false) {
            $this->tlsVersion = false; // Not detectable

            return false;
        }

        $tlsCheck = \Tools::jsonDecode($tlsCheck);
        if ($tlsCheck->tls_version == 'TLS 1.2') {
            $this->tlsVersion = 1.2;
        } else {
            $this->tlsVersion = 1;
        }

    }

    /************************************************************/
    /********************** CONNECT METHODS *********************/
    /************************************************************/
    /**
     * @param      $url
     * @param bool $httpHeader
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function connectWithCurl($url, $httpHeader = false)
    {
        $ch = @curl_init();

        if (!$ch) {
            $this->logs[] = $this->paypal->l('Connect failed with CURL method');
        } else {
            $this->logs[] = $this->paypal->l('Connect with CURL method successful');
            $this->logs[] = '<b>'.$this->paypal->l('Sending this params:').'</b>';
            $this->logs[] = '<b>'.$url.'</b>';

            @curl_setopt($ch, CURLOPT_URL, $url);

            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_HEADER, false);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            @curl_setopt($ch, CURLOPT_VERBOSE, false);
            if ($httpHeader) {
                @curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }

            $result = @curl_exec($ch);

            if (!$result) {
                $this->logs[] = $this->paypal->l('Send with CURL method failed ! Error:').' '.curl_error($ch);
            } else {
                $this->logs[] = $this->paypal->l('Send with CURL method successful');
            }

            @curl_close($ch);
        }

        return (isset($result) && $result) ? $result : false;
    }
}
