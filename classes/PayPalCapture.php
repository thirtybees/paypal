<?php
/**
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

use Cart;
use Db;
use DbQuery;
use Logger;
use ObjectModel;
use Order;
use PrestaShopException;
use Tools;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalCapture
 *
 * @package PayPalModule
 */
class PayPalCapture extends ObjectModel
{
    const AUTHORIZATION_PENDING = 1;
    const CAPTURE_PENDING = 2;
    const CAPTURED = 3;

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'paypal_capture',
        'primary' => 'id_paypal_capture',
        'fields'  => [
            'id_order'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'capture_amount' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',      'required' => true, 'db_type' => 'DECIMAL(15,5)'],
            'result'         => ['type' => self::TYPE_STRING, 'validate' => 'isString',     'required' => true, 'db_type' => 'TEXT'],
            'date_add'       => ['type' => self::TYPE_DATE,   'validate' => 'isDate',       'required' => true, 'db_type' => 'DATETIME'],
            'date_upd'       => ['type' => self::TYPE_DATE,   'validate' => 'isDate',       'required' => true, 'db_type' => 'DATETIME'],
        ],
    ];
    /** @var int $id_order */
    public $id_order;
    /** @var float $capture_amount */
    public $capture_amount;
    /** @var $result */
    public $result;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    /** @var int $id_paypal_capture */
    public $id_paypal_capture;
    // @codingStandardsIgnoreEnd

    /**
     * Get the total amount that has been captured for the given Order
     *
     * @param int $idOrder
     *
     * @return float Total amount captured
     */
    public static function getTotalAmountCapturedByIdOrder($idOrder)
    {
        try {
            return Tools::ps_round(Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('SUM(`capture_amount`)')
                    ->from(self::$definition['table'])
                    ->where('`id_order` = '.(int) $idOrder)
                    ->where('`result` = \'Completed\'')
            ), 2);
        } catch (PrestaShopException $e) {
        }

        return 0;
    }

    /**
     * @param \Order $order
     *
     * @return float
     * @throws \PrestaShopException
     */
    public function getRestToPaid(Order $order)
    {
        $cart = new Cart($order->id_cart);
        $totalPaid = Tools::ps_round($cart->getOrderTotal(), 2);

        return Tools::ps_round($totalPaid, 2) - Tools::ps_round(self::getTotalAmountCapturedByIdOrder($order->id), 2);
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function getRestToCapture($idOrder)
    {
        try {
            $cart = Cart::getCartByOrderId($idOrder);
        } catch (PrestaShopException $e) {
            Logger::addLog("PayPal module error: {$e->getMessage()}");

            return false;
        }

        $total = Tools::ps_round($cart->getOrderTotal(), 2) - Tools::ps_round(self::getTotalAmountCapturedByIdOrder($idOrder), 2);

        if ($total > Tools::ps_round(0, 2)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function getListCaptured()
    {
        try {
            $result = (array) Db::getInstance()->executeS(
                (new DbQuery())
                    ->from(bqSQL(static::$definition['table']))
                    ->where('`id_order` = '.$this->id_order)
                    ->orderBy('`date_add` DESC')
            );
        } catch (PrestaShopException $e) {
            Logger::addLog("PayPal module error: {$e->getMessage()}");

            return [];
        }

        return $result;
    }

    /**
     * @param float $price
     *
     * @return bool|float
     */
    public static function parsePrice($price)
    {
        $regexp = "/^([0-9\s]{0,10})((\.|,)[0-9]{0,2})?$/isD";

        if (preg_match($regexp, $price)) {
            $arrayRegexp = ['#,#isD', '# #isD'];
            $arrayReplace = ['.', ''];
            $price = preg_replace($arrayRegexp, $arrayReplace, $price);

            return \Tools::ps_round($price, 2);
        } else {
            return false;
        }
    }

    /**
     * Get the payment state of a payment
     *
     * @param array $payment
     *
     * @return int
     */
    public static function getPaymentState(array $payment)
    {
        if (empty($payment['transactions'][0]['related_resources']) || !is_array($payment['transactions'][0]['related_resources'])) {
            return static::AUTHORIZATION_PENDING;
        }

        $history = array_map(function ($resource) {
            return array_keys($resource)[0];
        }, $payment['transactions'][0]['related_resources']);

        if (in_array('capture', $history)) {
            return static::CAPTURED;
        } elseif (in_array('authorization', $history)) {
            return static::CAPTURE_PENDING;
        }

        return static::AUTHORIZATION_PENDING;
    }

    /**
     * Get the authorization of a payment
     *
     * @param array $payment
     *
     * @return null|array
     */
    public static function getAuthorization(array $payment)
    {
        if (empty($payment['transactions'][0]['related_resources']) || !is_array($payment['transactions'][0]['related_resources'])) {
            return null;
        }

        foreach ($payment['transactions'][0]['related_resources'] as $resource) {
            if (array_keys($resource)[0] === 'authorization') {
                return array_values($resource)[0];
            }
        }

        return null;
    }

    /**
     * Get the capture of a payment
     *
     * @param array $payment
     *
     * @return null|array
     */
    public static function getCapture(array $payment)
    {
        if (empty($payment['transactions'][0]['related_resources']) || !is_array($payment['transactions'][0]['related_resources'])) {
            return null;
        }

        foreach ($payment['transactions'][0]['related_resources'] as $resource) {
            if (array_keys($resource)[0] === 'capture') {
                return array_values($resource)[0];
            }
        }

        return null;
    }
}
