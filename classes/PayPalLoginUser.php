<?php
/**
 * 2007 Thirty Bees
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPalLoginUser extends PayPalObjectModel
{
    /** @var int $id_customer */
    public $id_customer;

    /** @var string $token_type */
    public $token_type;

    /** @var string $expires_in */
    public $expires_in;

    /** @var string $refresh_token */
    public $refresh_token;

    /** @var string $id_token */
    public $id_token;

    /** @var string $access_token */
    public $access_token;

    /** @var string $account_type */
    public $account_type;

    /** @var string $user_id */
    public $user_id;

    /** @var string $verified_account */
    public $verified_account;

    /** @var string $zoneinfo */
    public $zoneinfo;

    /** @var string $age_range */
    public $age_range;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'paypal_login_user',
        'primary' => 'id_paypal_login_user',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'),
            'token_type' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'expires_in' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'refresh_token' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'id_token' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'access_token' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'account_type' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'user_id' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'verified_account' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'zoneinfo' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
            'age_range' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'db_type' => 'VARCHAR(255)'),
        ),
    );

    /**
     * @param bool $idPaypalLoginUser
     * @param bool $idCustomer
     * @param bool $refreshToken
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaypalLoginUsers($idPaypalLoginUser = false, $idCustomer = false, $refreshToken = false)
    {
        $sql = "
			SELECT `id_paypal_login_user`
			FROM `"._DB_PREFIX_."paypal_login_user`
			WHERE 1
		";

        if ($idPaypalLoginUser && Validate::isInt($idPaypalLoginUser)) {
            $sql .= " AND `id_paypal_login_user` = '".(int) $idPaypalLoginUser."' ";
        }

        if ($idCustomer && Validate::isInt($idCustomer)) {
            $sql .= " AND `id_customer` = '".(int) $idCustomer."' ";
        }

        if ($refreshToken) {
            $sql .= " AND `refresh_token` = '".$refreshToken."' ";
        }

        $results = DB::getInstance()->executeS($sql);
        $logins = array();

        if ($results && count($results)) {
            foreach ($results as $result) {
                $logins[$result['id_paypal_login_user']] = new PayPalLoginUser((int) $result['id_paypal_login_user']);
            }

        }

        return $logins;
    }

    /**
     * @param $idCustomer
     *
     * @return array|bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getByIdCustomer($idCustomer)
    {
        $login = self::getPaypalLoginUsers(false, $idCustomer);

        if ($login && count($login)) {
            $login = current($login);
        } else {
            $login = false;
        }

        return $login;
    }
}
