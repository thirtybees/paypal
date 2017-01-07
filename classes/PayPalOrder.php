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
 * Class PayPalOrder
 *
 * @package PayPalModule
 */
class PayPalOrder extends PayPalObjectModel
{
    //PayPal notification fields
    const ID_INVOICE = 'invoice';
    const ID_PAYER = 'payer_id';
    const ID_TRANSACTION = 'txn_id';
    const CURRENCY = 'mc_currency';
    const PAYER_EMAIL = 'payer_email';
    const PAYMENT_DATE = 'payment_date';
    const TOTAL_PAID = 'mc_gross';
    const SHIPPING = 'shipping';
    const VERIFY_SIGN = 'verify_sign';

    // @codingStandardsIgnoreStart
    /** @var int $id_order */
    public $id_order;

    /** @var string $id_transaction */
    public $id_transaction;

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

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'paypal_order',
        'primary' => 'id_paypal_order',
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'id_transaction' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'],
            'id_invoice' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'],
            'currency' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(10)'],
            'total_paid' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'db_type' => 'DECIMAL(15,5)'],
            'shipping' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(50)'],
            'capture' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2)'],
            'payment_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true, 'db_type' => 'DATETIME'],
            'payment_method' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2) UNSIGNED'],
            'payment_status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'],
        ],
    ];

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
     * @param PayPalExpressCheckout|null $ppec
     * @param bool                       $paymentStatus
     *
     * @return array
     */
    public static function getTransactionDetails(PayPalExpressCheckout $ppec = null, $paymentStatus = false)
    {
        if ($ppec && $paymentStatus) {
            $transactionId = pSQL($ppec->result['PAYMENTINFO_0_TRANSACTIONID']);

            return [
                'currency' => pSQL($ppec->result['PAYMENTINFO_0_CURRENCYCODE']),
                'id_invoice' => null,
                'id_transaction' => $transactionId,
                'transaction_id' => $transactionId,
                'total_paid' => (float) $ppec->result['PAYMENTINFO_0_AMT'],
                'shipping' => (float) $ppec->result['PAYMENTREQUEST_0_SHIPPINGAMT'],
                'payment_date' => pSQL($ppec->result['PAYMENTINFO_0_ORDERTIME']),
                'payment_status' => pSQL($paymentStatus),
            ];
        } else {
            $transactionId = pSQL(\Tools::getValue(self::ID_TRANSACTION));

            return [
                'currency' => pSQL(\Tools::getValue(self::CURRENCY)),
                'id_invoice' => pSQL(\Tools::getValue(self::ID_INVOICE)),
                'id_transaction' => $transactionId,
                'transaction_id' => $transactionId,
                'total_paid' => (float) \Tools::getValue(self::TOTAL_PAID),
                'shipping' => (float) \Tools::getValue(self::SHIPPING),
                'payment_date' => pSQL(\Tools::getValue(self::PAYMENT_DATE)),
                'payment_status' => pSQL($paymentStatus),
            ];
        }
    }

    /**
     * @param int $idOrder
     *
     * @return array|bool|null|object
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getOrderById($idOrder)
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(self::$definition['table']));
        $sql->where('`id_order` = '.(int) $idOrder);

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * @param string $idTransaction
     *
     * @return int
     */
    public static function getIdOrderByTransactionId($idTransaction)
    {
        $sql = new \DbQuery();
        $sql->select('po.`id_order`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_transaction` = \''.pSQL($idTransaction).'\'');
        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if ($result != false) {
            return (int) $result['id_order'];
        }

        return 0;
    }

    /**
     * @param int    $idOrder
     * @param string $transaction
     */
    public static function saveOrder($idOrder, $transaction)
    {
        $totalPaid = (float) $transaction['total_paid'];

        if (!isset($transaction['payment_status']) || !$transaction['payment_status']) {
            $transaction['payment_status'] = 'NULL';
        }

        \Db::getInstance()->insert(
            bqSQL(self::$definition['table']),
            [
                'id_order' => (int) $idOrder,
                'id_transaction' => pSQL($transaction['id_transaction']),
                'id_invoice' => pSQL($transaction['id_invoice']),
                'currency' => pSQL($transaction['currency']),
                'total_paid' => $totalPaid,
                'shipping' => (float) $transaction['shipping'],
                'capture' => (int) \Configuration::get('PAYPAL_CAPTURE'),
                'payment_date' => pSQL($transaction['payment_date']),
                'payment_method' => (int) \Configuration::get('PAYPAL_PAYMENT_METHOD'),
                'payment_status' => pSQL($transaction['payment_status']),
            ]
        );
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

        $sql = 'UPDATE `'._DB_PREFIX_.'paypal_order`
			SET `payment_status` = \''.pSQL($transaction['payment_status']).'\'
			WHERE `id_order` = \''.(int) $idOrder.'\'
				AND `id_transaction` = \''.pSQL($transaction['id_transaction']).'\'
				AND `currency` = \''.pSQL($transaction['currency']).'\'';
        if (!\Configuration::get('PAYPAL_SANDBOX')) {
            $sql .= 'AND `total_paid` = \''.$transaction['total_paid'].'\'
				AND `shipping` = \''.(float) $transaction['shipping'].'\';';
        }

        \Db::getInstance()->execute($sql);
    }
}
