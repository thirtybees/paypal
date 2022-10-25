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

use PrestaShopException;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalCustomer
 *
 * @package PayPalModule
 */
class PayPalCustomer extends \ObjectModel
{

    /** @var int $id_customer */
    public $id_customer;

    /** @var string $paypal_email */
    public $paypal_email;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'paypal_customer',
        'primary' => 'id_paypal_customer',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'paypal_email' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'],
        ],
    ];

    /**
     * Get PayPalCustomer ID by email
     *
     * @param string $email
     *
     * @return false|null|string
     * @throws PrestaShopException
     */
    public static function getPayPalCustomerIdByEmail($email)
    {
        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('pc.`id_customer`')
                ->from(bqSQL(self::$definition['table']), 'pc')
                ->where('pc.`paypal_email` = \''.pSQL($email).'\'')
        );
    }
}
