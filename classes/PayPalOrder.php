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

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalOrder
 *
 * @package PayPalModule
 */
class PayPalOrder extends \ObjectModel
{
    //PayPal notification fields
    const ID_INVOICE = 'invoice';
    const ID_PAYER = 'payer_id';
    const ID_PAYMENT = 'payment_id';
    const ID_TRANSACTION = 'txn_id';
    const CURRENCY = 'mc_currency';
    const PAYER_EMAIL = 'payer_email';
    const PAYMENT_DATE = 'payment_date';
    const TOTAL_PAID = 'mc_gross';
    const SHIPPING = 'shipping';
    const VERIFY_SIGN = 'verify_sign';

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'primary' => 'id_paypal_order',
        'table'   => 'paypal_order',
        'fields'  => [
            'id_order'       => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',  'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'id_transaction' => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(255)'],
            'id_payer'       => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(255)'],
            'id_payment'     => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(255)'],
            'id_invoice'     => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(255)'],
            'currency'       => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(10)'],
            'total_paid'     => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat',       'required' => true, 'db_type' => 'DECIMAL(15,5)'],
            'shipping'       => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(50)'],
            'capture'        => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2)'],
            'payment_date'   => ['type' => self::TYPE_DATE,   'validate' => 'isDate',        'required' => true, 'db_type' => 'DATETIME'],
            'payment_method' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2) UNSIGNED'],
            'payment_status' => ['type' => self::TYPE_STRING, 'validate' => 'isString',      'required' => true, 'db_type' => 'VARCHAR(255)'],
        ],
    ];
    /** @var int $id_order */
    public $id_order;
    /** @var string $id_transaction */
    public $id_transaction;
    /** @var string $id_payer */
    public $id_payer;
    /** @var string $id_payment */
    public $id_payment;
    /** @var string $id_invoice */
    public $id_invoice;
    /** @var string $currency */
    public $currency;
    /** @var float $total_paid */
    public $total_paid;
    /** @var string $shipping */
    public $shipping;
    /** @var string $capture */
    public $capture;
    /** @var string $payment_date */
    public $payment_date;
    /** @var int $payment_method */
    public $payment_method;
    /** @var string $payment_status */
    public $payment_status;
    // @codingStandardsIgnoreEnd

    /*
     * Get PayPal order data
     * - ID Order
     * - ID Transaction
     * - ID Invoice
     * - Currency (ISO)
     * - Total paid
     * - Shipping
     * - Capture (bool)
     * - Payment date
     * - Payment method (int)
     * - Payment status
     */

    /**
     * @param \stdClass $payment
     *
     * @return array
     */
    public static function getTransactionDetails($payment)
    {
        $transactionId = pSQL($payment->id);
        $paymentId = pSQL($payment->id);
        $payerId = pSQL($payment->payer->payer_info->payer_id);
        $transaction = $payment->transactions[0];
        $paymentDate = isset($payment->create_time) ? $payment->create_time : (isset($payment->update_time) ? $payment->update_time : '');

        return [
            'currency'       => pSQL($payment->transactions[0]->amount->currency),
            'id_invoice'     => null,
            'id_payer'       => $payerId,
            'id_payment'     => $paymentId,
            'id_transaction' => $transactionId,
            'transaction_id' => $transactionId,
            'total_paid'     => (float) $transaction->amount->total,
            'shipping'       => isset($transaction->amount->details->shipping) ? (float) $transaction->amount->details->shipping : 0,
            'payment_date'   => pSQL($paymentDate),
            'payment_status' => pSQL($payment->state),
        ];
    }

    /**
     * @param int $idOrder
     *
     * @return array
     */
    public static function getByOrderId($idOrder)
    {
        try {
            return (array) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new \DbQuery())
                    ->select('*')
                    ->from(bqSQL(self::$definition['table']))
                    ->where('`id_order` = '.(int) $idOrder)
            );
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * @param string $idTransaction
     *
     * @return int
     */
    public static function getIdOrderByTransactionId($idTransaction)
    {
        try {
            $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new \DbQuery())
                    ->select('po.`id_order`')
                    ->from('paypal_order', 'po')
                    ->where('po.`id_transaction` = \''.pSQL($idTransaction).'\'')
            );
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: {$e->getMessage()}");

            $result = false;
        }

        if ($result != false) {
            return (int) $result['id_order'];
        }

        return 0;
    }

    /**
     * @param int   $idOrder
     * @param array $transaction
     */
    public static function saveOrder($idOrder, $transaction)
    {
        $totalPaid = (float) $transaction['total_paid'];

        if (!isset($transaction['payment_status']) || !$transaction['payment_status']) {
            $transaction['payment_status'] = 'NULL';
        }

        try {
            \Db::getInstance()->insert(
                bqSQL(self::$definition['table']),
                [
                    'id_order'       => (int) $idOrder,
                    'id_payer'       => pSQL($transaction['id_payer']),
                    'id_payment'     => pSQL($transaction['id_payment']),
                    'id_transaction' => pSQL($transaction['id_transaction']),
                    'id_invoice'     => pSQL($transaction['id_invoice']),
                    'currency'       => pSQL($transaction['currency']),
                    'total_paid'     => $totalPaid,
                    'shipping'       => (float) $transaction['shipping'],
                    'capture'        => (int) \Configuration::get('PAYPAL_CAPTURE'),
                    'payment_date'   => pSQL($transaction['payment_date']),
                    'payment_method' => (int) \Configuration::get('PAYPAL_PAYMENT_METHOD'),
                    'payment_status' => pSQL($transaction['payment_status']),
                ]
            );
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: {$e->getMessage()}");
        }
    }

    /**
     * @param int   $idOrder
     * @param array $transaction
     */
    public static function updateOrder($idOrder, $transaction)
    {
        if (!isset($transaction['payment_status']) || !$transaction['payment_status']) {
            $transaction['payment_status'] = 'NULL';
        }

        try {
            \Db::getInstance()->update(
                bqSQL(static::$definition['table']),
                [
                    'payment_status' => pSQL($transaction['payment_status']),
                ],
                '`id_order` = \''.(int) $idOrder.'\' AND `id_transaction` = \''.pSQL($transaction['id_transaction']).'\' AND `currency` = \''.pSQL($transaction['currency']).'\''.((\Configuration::get(\PayPal::LIVE)) ? 'AND `total_paid` = \''.$transaction['total_paid'].'\' AND `shipping` = \''.(float) $transaction['shipping'].'\'' : '')
            );
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: {$e->getMessage()}");
        }
    }

    /**
     * Get PayPalOrder by Payment ID
     *
     * @param string $paymentId
     *
     * @return array
     */
    public static function getByPaymentId($paymentId)
    {
        try {
            return (array) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new \DbQuery())
                    ->select('*')
                    ->from(bqSQL(static::$definition['table']))
                    ->where('`id_payment` = \''.pSQL($paymentId).'\'')
            );
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: {$e->getMessage()}");

            return [];
        }
    }
}
