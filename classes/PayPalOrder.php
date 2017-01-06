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

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'paypal_order',
        'primary' => 'id_paypal_order',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'),
            'id_transaction' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'id_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'currency' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(10)'),
            'total_paid' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'db_type' => 'DECIMAL(15,5)'),
            'shipping' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(50)'),
            'capture' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2)'),
            'payment_date' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true, 'db_type' => 'DATETIME'),
            'payment_method' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'db_type' => 'INT(2) UNSIGNED'),
            'payment_status' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
        ),
    );

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
     * @param bool $ppec
     * @param bool $paymentStatus
     *
     * @return array
     */
    public static function getTransactionDetails($ppec = false, $paymentStatus = false)
    {
        if ($ppec && $paymentStatus) {
            $transactionId = pSQL($ppec->result['PAYMENTINFO_0_TRANSACTIONID']);
            return array(
                'currency' => pSQL($ppec->result['PAYMENTINFO_0_CURRENCYCODE']),
                'id_invoice' => null,
                'id_transaction' => $transactionId,
                'transaction_id' => $transactionId,
                'total_paid' => (float) $ppec->result['PAYMENTINFO_0_AMT'],
                'shipping' => (float) $ppec->result['PAYMENTREQUEST_0_SHIPPINGAMT'],
                'payment_date' => pSQL($ppec->result['PAYMENTINFO_0_ORDERTIME']),
                'payment_status' => pSQL($paymentStatus),
            );
        } else {
            $transactionId = pSQL(\Tools::getValue(self::ID_TRANSACTION));
            return array(
                'currency' => pSQL(\Tools::getValue(self::CURRENCY)),
                'id_invoice' => pSQL(\Tools::getValue(self::ID_INVOICE)),
                'id_transaction' => $transactionId,
                'transaction_id' => $transactionId,
                'total_paid' => (float) \Tools::getValue(self::TOTAL_PAID),
                'shipping' => (float) \Tools::getValue(self::SHIPPING),
                'payment_date' => pSQL(\Tools::getValue(self::PAYMENT_DATE)),
                'payment_status' => pSQL($paymentStatus),
            );
        }
    }

    /**
     * @param $idOrder
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
     * @param $idTransaction
     *
     * @return int
     */
    public static function getIdOrderByTransactionId($idTransaction)
    {
        $sql = 'SELECT `id_order`
			FROM `'._DB_PREFIX_.'paypal_order`
			WHERE `id_transaction` = \''.pSQL($idTransaction).'\'';

        $result = \Db::getInstance()->getRow($sql);

        if ($result != false) {
            return (int) $result['id_order'];
        }

        return 0;
    }

    /**
     * @param $idOrder
     * @param $transaction
     */
    public static function saveOrder($idOrder, $transaction)
    {
        $order = new \Order((int) $idOrder);
        $total_paid = (float) $transaction['total_paid'];

        if (!isset($transaction['payment_status']) || !$transaction['payment_status']) {
            $transaction['payment_status'] = 'NULL';
        }

        \Db::getInstance()->Execute(
            'INSERT INTO `'._DB_PREFIX_.'paypal_order`
			(`id_order`, `id_transaction`, `id_invoice`, `currency`, `total_paid`, `shipping`, `capture`, `payment_date`, `payment_method`, `payment_status`)
			VALUES ('.(int) $idOrder.', \''.pSQL($transaction['id_transaction']).'\', \''.pSQL($transaction['id_invoice']).'\',
				\''.pSQL($transaction['currency']).'\',
				\''.$total_paid.'\',
				\''.(float) $transaction['shipping'].'\',
				\''.(int) \Configuration::get('PAYPAL_CAPTURE').'\',
				\''.pSQL($transaction['payment_date']).'\',
				\''.(int) \Configuration::get('PAYPAL_PAYMENT_METHOD').'\',
				\''.pSQL($transaction['payment_status']).'\')'
        );
    }

    /**
     * @param int   $idOrder
     * @param array $transaction
     */
    public static function updateOrder($idOrder, $transaction)
    {
        $totalPaid = (float) $transaction['total_paid'];

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
