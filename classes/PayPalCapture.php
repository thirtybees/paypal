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

use Cart;
use Db;
use DbQuery;
use ObjectModel;
use Order;
use PDOStatement;
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

    /**
     * Get the total amount that has been captured for the given Order
     *
     * @param int $idOrder
     *
     * @return float Total amount captured
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getTotalAmountCapturedByIdOrder($idOrder)
    {
        return Tools::ps_round(Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('SUM(`capture_amount`)')
                ->from(self::$definition['table'])
                ->where('`id_order` = '.(int) $idOrder)
                ->where('`result` = \'Completed\'')
        ), 2);
    }

    /**
     * @param Order $order
     *
     * @return float
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getRestToCapture($idOrder)
    {
        $cart = Cart::getCartByOrderId($idOrder);

        $total = Tools::ps_round($cart->getOrderTotal(), 2) - Tools::ps_round(self::getTotalAmountCapturedByIdOrder($idOrder), 2);

        if ($total > Tools::ps_round(0, 2)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array|bool|PDOStatement|null
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getListCaptured()
    {
        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_order` = '.$this->id_order)
                ->orderBy('`date_add` DESC')
        );

        return $result;
    }

    /**
     * @param float $price
     *
     * @return bool|float
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function parsePrice($price)
    {
        $regexp = "/^([0-9\s]{0,10})((\.|,)[0-9]{0,2})?$/isD";

        if (preg_match($regexp, $price)) {
            $arrayRegexp = ['#,#isD', '# #isD'];
            $arrayReplace = ['.', ''];
            $price = preg_replace($arrayRegexp, $arrayReplace, $price);

            return Tools::ps_round($price, 2);
        } else {
            return false;
        }

    }
}
