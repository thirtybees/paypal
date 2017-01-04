<?php
/**
 * 2007-2016 PrestaShop
 * 2007 Thirty Bees
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
 *  @copyright 2007-2016 PrestaShop SA
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    die(header('HTTP/1.0 404 Not Found'));
}

/**
 * Description of totRules
 *
 * @author 202-ecommerce
 */
class PaypalLoginUser extends ObjectModel
{

    public $id_customer;
    public $token_type;
    public $expires_in;
    public $refresh_token;
    public $id_token;
    public $access_token;
    public $account_type;
    public $user_id;
    public $verified_account;
    public $zoneinfo;
    public $age_range;

    protected $table = 'paypal_login_user';
    protected $identifier = 'id_paypal_login_user';

    /**
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected $fieldsRequired = array(
        'id_customer',
        'token_type',
        'expires_in',
        'refresh_token',
        'id_token',
        'access_token',
        'user_id',
        'verified_account',
        'zoneinfo',
    );

    /**
     * @var array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected $fieldsValidate = array(
        'id_customer' => 'isInt',
        'token_type' => 'isString',
        'expires_in' => 'isString',
        'refresh_token' => 'isString',
        'id_token' => 'isString',
        'access_token' => 'isString',
        'account_type' => 'isString',
        'user_id' => 'isString',
        'verified_account' => 'isString',
        'zoneinfo' => 'isString',
        'age_range' => 'isString',

    );

    /**
     * PaypalLoginUser constructor.
     *
     * @param bool $id
     * @param bool $id_lang
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct($id = false, $id_lang = false)
    {
        parent::__construct($id, $id_lang);
    }

    /**
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getFields()
    {
        parent::validateFields();
        $fields = array();
        foreach (array_keys($this->fieldsValidate) as $field) {
            $fields[$field] = $this->$field;
        }

        return $fields;
    }

    /**
     * @param bool $id_paypal_login_user
     * @param bool $id_customer
     * @param bool $refresh_token
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaypalLoginUsers($id_paypal_login_user = false, $id_customer = false, $refresh_token = false)
    {
        $sql = "
			SELECT `id_paypal_login_user`
			FROM `"._DB_PREFIX_."paypal_login_user`
			WHERE 1
		";

        if ($id_paypal_login_user && Validate::isInt($id_paypal_login_user)) {
            $sql .= " AND `id_paypal_login_user` = '".(int) $id_paypal_login_user."' ";
        }

        if ($id_customer && Validate::isInt($id_customer)) {
            $sql .= " AND `id_customer` = '".(int) $id_customer."' ";
        }

        if ($refresh_token) {
            $sql .= " AND `refresh_token` = '".$refresh_token."' ";
        }

        $results = DB::getInstance()->executeS($sql);
        $logins = array();

        if ($results && count($results)) {
            foreach ($results as $result) {
                $logins[$result['id_paypal_login_user']] = new PaypalLoginUser((int) $result['id_paypal_login_user']);
            }

        }

        return $logins;
    }

    /**
     * @param $id_customer
     *
     * @return array|bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getByIdCustomer($id_customer)
    {
        $login = self::getPaypalLoginUsers(false, $id_customer);

        if ($login && count($login)) {
            $login = current($login);
        } else {
            $login = false;
        }

        return $login;
    }
}
