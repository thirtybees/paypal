<?php
/**
 * Copyright (C) 2017 thirty bees
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
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */


namespace PayPalModule;

use GuzzleHttp\Client;
use Hybridauth\Exception\Exception;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalNotifier
 *
 * @package PayPalModule
 */
class PayPalNotifier extends \PayPal
{
    /** @var int $decimals */
    public $decimals;

    /**
     * @param array $custom
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function confirmOrder($custom)
    {
        $cart = new \Cart((int) $custom['id_cart']);

        $cartHash = sha1(serialize($cart->nbProducts()));

        $this->context->cart = $cart;
        $address = new \Address((int) $cart->id_address_invoice);
        $this->context->country = new \Country((int) $address->id_country);
        $this->context->customer = new \Customer((int) $cart->id_customer);
        $this->context->language = new \Language((int) $cart->id_lang);
        $this->context->currency = new \Currency((int) $cart->id_currency);

        if (isset($cart->id_shop)) {
            $this->context->shop = new \Shop($cart->id_shop);
        }

        $result = $this->getResult();

        if (strcmp(trim($result), "VERIFIED") == 0) {
            $currencyDecimals = is_array($this->context->currency) ? (int) $this->context->currency['decimals'] : (int) $this->context->currency->decimals;
            $this->decimals = $currencyDecimals * _PS_PRICE_DISPLAY_PRECISION_;

            $message = null;
            $mcGross = \Tools::ps_round(\Tools::getValue('mc_gross'), $this->decimals);

            $cartDetails = $cart->getSummaryDetails(null, true);

            $shipping = $cartDetails['total_shipping_tax_exc'];
            $subtotal = $cartDetails['total_price_without_tax'] - $cartDetails['total_shipping_tax_exc'];
            $tax = $cartDetails['total_tax'];

            $totalPrice = \Tools::ps_round($shipping + $subtotal + $tax, $this->decimals);

            if (bccomp($mcGross, $totalPrice, 2) !== 0) {
                $payment = (int) \Configuration::get('PS_OS_ERROR');
                $message = $this->l('Price paid on paypal is not the same that on Thirty Bees.').'<br />';
            } elseif ($custom['hash'] != $cartHash) {
                $payment = (int) \Configuration::get('PS_OS_ERROR');
                $message = $this->l('Cart changed, please retry.').'<br />';
            } else {
                $payment = (int) \Configuration::get('PS_OS_PAYMENT');
                $message = $this->l('Payment accepted.').'<br />';
            }

            $customer = new \Customer((int) $cart->id_customer);
            $transaction = PayPalOrder::getTransactionDetails(false);
            $idShop = $this->context->shop->id;
            $shop = new \Shop($idShop);

            $this->validateOrder($cart->id, $payment, $totalPrice, 'PayPal', $message, $transaction, $cart->id_currency, false, $customer->secure_key, $shop);
        }
    }

    /**
     * @return false|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getResult()
    {
        if (!\Configuration::get(self::LIVE)) {
            $actionUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_notify-validate';
        } else {
            $actionUrl = 'https://www.paypal.com/cgi-bin/webscr?cmd=_notify-validate';
        }

        $request = '&'.http_build_query($_POST, '&');

        $guzzle = new Client([
            'timeout' => 60.0,
            'verify'  => _PS_TOOL_DIR_.'cacert.pem',
        ]);

        try {
            return (string) $guzzle->get($actionUrl.$request)->getBody();
        } catch (Exception $e) {
            return false;
        }
    }
}
