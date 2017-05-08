<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/autoload.php';

use PayPalModule\PayPalCapture;
use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalLogin;
use PayPalModule\PayPalLoginUser;
use PayPalModule\PayPalLogos;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalRestApi;
use PayPalModule\PayPalTools;

/**
 * Class PayPal
 */
class PayPal extends \PaymentModule
{
    const MIN_PHP_VERSION = 50500;
    const LIVE = 'PAYPAL_LIVE';
    const IMMEDIATE_CAPTURE = 'PAYPAL_CAPTURE';
    const STORE_COUNTRY = 'PAYPAL_COUNTRY_DEFAULT';
    const CLIENT_ID = 'PAYPAL_CLIENT_ID';
    const SECRET = 'PAYPAL_SECRET';
    const STANDARD_WEBSITE_PROFILE_ID = 'PAYPAL_WEB_PROFILE_ID';
    const EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID = 'PAYPAL_EC_WEB_PROFILE_ID';
    const PLUS_WEBSITE_PROFILE_ID = 'PAYPAL_WPP_WEB_PROFILE_ID';
    const STANDARD_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_WEB_PROFILE_ID_LIVE';
    const EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_EC_WEB_PROFILE_ID_LIVE';
    const PLUS_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_WPP_WEB_PROFILE_ID_LIVE';

    const WPS = 1;
    const EC = 4;
    const WPP = 5;

    const TRACKING_INTEGRAL = '';
    const TRACKING_OPTION_PLUS = '';
    const TRACKING_PAYPAL_PLUS = '';
    const TRACKING_EXPRESS_CHECKOUT_SEAMLESS = '';
    const TRACKING_CODE = '';
    const SMARTPHONE_TRACKING_CODE = '';
    const TABLET_TRACKING_CODE = ''; // Website Payments Standard
    const APAC_TRACKING_INTEGRAL = ''; // Express Checkout
    const APAC_TRACKING_OPTION_PLUS = ''; // Website Payments Plus

    /* Tracking */
    const APAC_TRACKING_PAYPAL_PLUS = '';
    const APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS = '';
    const APAC_TRACKING_CODE = '';
    const APAC_SMARTPHONE_TRACKING_CODE = '';
    const APAC_TABLET_TRACKING_CODE = '';
    const _PAYPAL_LOGO_XML_ = 'logos.xml';
    const _PAYPAL_MODULE_DIRNAME_ = 'paypal';

    /* Tracking APAC */
    const _PAYPAL_TRANSLATIONS_XML_ = 'translations.xml';
    const WEBSITE_PAYMENTS_STANDARD_ENABLED = 'PAYPAL_WPS_ENABLED';
    const WEBSITE_PAYMENTS_PLUS_ENABLED = 'PAYPAL_WPP_ENABLED';
    const EXPRESS_CHECKOUT_ENABLED = 'PAYPAL_EC_ENABLED';
    const LOGIN_ENABLED = 'PAYPAL_LOGIN_ENABLED';
    const LOGIN_THEME = 'PAYPAL_LOGIN_TPL';
    const TLS_OK = 'PAYPAL_TLS_OK';
    const TLS_LAST_CHECK = 'PAYPAL_TLS_LAST_CHECK';
    const ENUM_TLS_OK = 1;
    const ENUM_TLS_ERROR = -1;
    /** @var array $errors */
    public $errors = [];
    /**
     * Indicates whether the module is compatible with the Advanced EU Checkout
     *
     * @var int $is_eu_compatible
     */
    public $is_eu_compatible = 1;
    /** @var \Context $context */
    public $context;
    /** @var string $iso_code */
    public $iso_code;
    public $defaultCountry;
    public $module_key = '336225a5988ad434b782f2d868d7bfcd';
    /** @var string $moduleUrl */
    public $moduleUrl;
    /**
     * Indicates whether the module supports Bootstrap configuration forms
     *
     * @var bool $bootstrap
     */
    public $bootstrap = true;
    /** @var string $html */
    protected $html = '';

    /**
     * PayPal constructor.
     */
    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '5.0.0';
        $this->author = 'thirty bees';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayPal');
        $this->description = $this->l('Accepts payments by credit cards (CB, Visa, MasterCard, Amex, Aurore, Cofinoga, 4 stars) with PayPal.');

        $this->controllers = [
            'expresscheckout',
            'expresscheckoutsubmit',
            'standardcancel',
            'incontextajax',
            'incontextvalidate',
            'incontextconfirm',
            'pluseu',
            'plussubmit',
            'pluscancel',
            'logintoken',
            'orderconfirmation',
        ];

        // Only check from Back Office
        if (isset(Context::getContext()->employee->id) && Context::getContext()->employee->id) {
            if ($this->active && extension_loaded('curl') == false) {
                $this->context->controller->errors[] = $this->displayName.': '.$this->l('You have to enable the cURL extension on your server in order to use this module');
                $this->disable();

                return;
            }
            if (PHP_VERSION_ID < self::MIN_PHP_VERSION) {
                $this->context->controller->errors[] = $this->displayName.': '.$this->l('Your PHP version is not supported. Please upgrade to PHP 5.5.0 or higher.');
                $this->disable();

                return;
            }

            $this->moduleUrl = $this->context->link->getAdminLink('AdminModules', true).'&'.http_build_query([
                'configure'   => $this->name,
                'tab_module'  => $this->tab,
                'module_name' => $this->name,
            ]);
        }
    }

    /**
     * @param int $idCustomer
     *
     * @return mixed
     */
    public static function getPayPalEmailByIdCustomer($idCustomer)
    {
        $sql = new \DbQuery();
        $sql->select('pc.`paypay_email`');
        $sql->from('paypal_customer', 'pc');
        $sql->where('pc.`id_customer` = '.(int) $idCustomer);

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Install the module
     *
     * @return bool Indicates whether the module was installed successfully
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('shoppingCartExtra')
            || !$this->registerHook('backBeforePayment')
            || !$this->registerHook('rightColumn')
            || !$this->registerHook('cancelProduct')
            || !$this->registerHook('productFooter')
            || !$this->registerHook('header')
            || !$this->registerHook('adminOrder')
            || !$this->registerHook('backOfficeHeader')
        ) {
            return false;
        }

        // Optional hooks
        $this->registerHook('displayPaymentEU');
        $this->registerHook('actionPSCleanerGetModulesTables');

        if (!(PayPalCapture::createDatabase()
            && PayPalCustomer::createDatabase()
            && PayPalLoginUser::createDatabase()
            && PayPalOrder::createDatabase())
        ) {
            return false;
        }

        $this->updateConfiguration();
        $this->createOrderState();

        $paypalTools = new PayPalTools($this->name);
        $paypalTools->moveTopPayments(1);
        $paypalTools->moveRightColumn(3);

        return true;
    }

    /**
     * Set configuration table
     */
    public function updateConfiguration()
    {
        \Configuration::updateValue(self::LIVE, false);

        \Configuration::updateValue(self::IMMEDIATE_CAPTURE, true);
    }

    /**
     * Create a new order state
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createOrderState()
    {
        if (!\Configuration::get('PAYPAL_OS_AUTHORIZATION')) {
            $orderState = new \OrderState();
            $orderState->name = [];

            foreach (\Language::getLanguages() as $language) {
                if (\Tools::strtolower($language['iso_code']) == 'fr') {
                    $orderState->name[$language['id_lang']] = 'Autorisation acceptée par PayPal';
                } else {
                    $orderState->name[$language['id_lang']] = 'Authorization accepted from PayPal';
                }

            }

            $orderState->send_email = false;
            $orderState->color = '#DDEEFF';
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = true;
            $orderState->invoice = true;

            if ($orderState->add()) {
                $source = dirname(__FILE__).'/../../img/os/'.\Configuration::get('PS_OS_PAYPAL').'.gif';
                $destination = dirname(__FILE__).'/../../img/os/'.(int) $orderState->id.'.gif';
                copy($source, $destination);
            }
            \Configuration::updateValue('PAYPAL_OS_AUTHORIZATION', (int) $orderState->id);
        }
    }

    /**
     * Uninstall the module
     *
     * @return bool Indicates whether the module was uninstalled successfully
     */
    public function uninstall()
    {
        $this->deleteConfiguration();

        return parent::uninstall();
    }

    /**
     * Delete PayPal configuration
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function deleteConfiguration()
    {
        \Configuration::deleteByName(self::LIVE);

        \Configuration::deleteByName(self::IMMEDIATE_CAPTURE);
        \Configuration::deleteByName(self::DEBUG_MODE);
        \Configuration::deleteByName(self::STORE_COUNTRY);

        \Configuration::deleteByName(self::LOGIN_ENABLED);
        \Configuration::deleteByName(self::CLIENT_ID);
        \Configuration::deleteByName(self::SECRET);
        \Configuration::deleteByName(self::LOGIN_THEME);
    }

    /**
     * Get module configuration page
     *
     * @return string HTML
     */
    public function getContent()
    {
        $output = '';

        $this->postProcess();

        $this->context->smarty->assign(
            [
                'module_url' => $this->moduleUrl,
                'tls_ok'     => (int) Configuration::get(self::TLS_OK),
            ]
        );

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->display(__FILE__, 'views/templates/admin/tlscheck.tpl');

        return $output.$this->renderMainForm();
    }

    /**
     * Post process
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('checktls') && (bool) Tools::getValue('checktls')) {
            $this->tlsCheck();
        }

        if (\Tools::isSubmit('submit'.$this->name)) {
            // General
            \Configuration::updateValue(self::STORE_COUNTRY, (int) \Tools::getValue(self::STORE_COUNTRY));
            \Configuration::updateValue(self::LIVE, (int) \Tools::getValue(self::LIVE));
            \Configuration::updateValue(self::IMMEDIATE_CAPTURE, (int) \Tools::getValue(self::IMMEDIATE_CAPTURE));

            // REST API
            \Configuration::updateValue(self::CLIENT_ID, \Tools::getValue(self::CLIENT_ID));
            \Configuration::updateValue(self::SECRET, \Tools::getValue(self::SECRET));

            if (\Tools::getValue(self::CLIENT_ID) && \Tools::getValue(self::SECRET)) {
                $rest = new PayPalRestApi(\Tools::getValue(self::CLIENT_ID), \Tools::getValue(self::SECRET));
                $rest->getListProfile();
                $standardProfile = $rest->getWebProfile(PayPalRestApi::STANDARD_PROFILE);
                $plusProfile = $rest->getWebProfile(PayPalRestApi::PLUS_PROFILE);
                $expressCheckoutProfile = $rest->getWebProfile(PayPalRestApi::EXPRESS_CHECKOUT_PROFILE);

                if (\Tools::getValue(self::LIVE)) {
                    if ($standardProfile) {
                        \Configuration::updateValue(self::STANDARD_WEBSITE_PROFILE_ID_LIVE, $standardProfile);
                    }
                    if ($plusProfile) {
                        \Configuration::updateValue(self::PLUS_WEBSITE_PROFILE_ID_LIVE, $plusProfile);
                    }
                    if ($expressCheckoutProfile) {
                        \Configuration::updateValue(self::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE, $expressCheckoutProfile);
                    }
                } else {
                    if ($standardProfile) {
                        \Configuration::updateValue(self::STANDARD_WEBSITE_PROFILE_ID, $standardProfile);
                    }
                    if ($plusProfile) {
                        \Configuration::updateValue(self::PLUS_WEBSITE_PROFILE_ID, $plusProfile);
                    }
                    if ($expressCheckoutProfile) {
                        \Configuration::updateValue(self::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID, $expressCheckoutProfile);
                    }
                }
            }

            // Website Payments Standard
            \Configuration::updateValue(self::WEBSITE_PAYMENTS_STANDARD_ENABLED, \Tools::getValue(self::WEBSITE_PAYMENTS_STANDARD_ENABLED));

            // Website Payments Plus
            \Configuration::updateValue(self::WEBSITE_PAYMENTS_PLUS_ENABLED, \Tools::getValue(self::WEBSITE_PAYMENTS_PLUS_ENABLED));

            // Express Checkout
            \Configuration::updateValue(self::EXPRESS_CHECKOUT_ENABLED, \Tools::getValue(self::EXPRESS_CHECKOUT_ENABLED));

            // PayPal Login
            \Configuration::updateValue(self::LOGIN_ENABLED, (int) \Tools::getValue(self::LOGIN_ENABLED));
            \Configuration::updateValue(self::LOGIN_THEME, (int) \Tools::getValue(self::LOGIN_THEME));
        }
    }

    /**
     * Check if server supports TLSv1.2
     */
    protected function tlsCheck()
    {
        $guzzle = new \GuzzleHttp\Client(
            [
                'timeout'     => 10.0,
                'verify'      => _PS_TOOL_DIR_.'cacert.pem',
                'http_errors' => false,
            ]
        );
        $response = $guzzle->get('https://tlstest.paypal.com/');

        if ((string) $response->getBody() === 'PayPal_Connection_OK') {
            $this->updateAllValue(self::TLS_OK, self::ENUM_TLS_OK);
        } else {
            $this->updateAllValue(self::TLS_OK, self::ENUM_TLS_ERROR);
        }
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * @return string
     */
    protected function renderMainForm()
    {
        $helper = new \HelperForm();

        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = \AdminController::$currentIndex.'&'.http_build_query(
                [
                    'configure' => $this->name,
                ]
            );

        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getMainFormValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm(
            [
                $this->getGeneralForm(),
                $this->getRestApiForm(),
                $this->getWebsitePaymentsStandardForm(),
                $this->getWebsitePaymentsPlusForm(),
                $this->getExpressCheckoutForm(),
                $this->getLoginForm(),
            ]
        );
    }

    protected function getMainFormValues()
    {
        return [
            self::STORE_COUNTRY               => (int) \Configuration::get(self::STORE_COUNTRY),
            self::LIVE                        => \Configuration::get(self::LIVE),
            self::STANDARD_WEBSITE_PROFILE_ID => \Configuration::get(self::STANDARD_WEBSITE_PROFILE_ID),

            self::WEBSITE_PAYMENTS_STANDARD_ENABLED => \Configuration::get(self::WEBSITE_PAYMENTS_STANDARD_ENABLED),
            self::WEBSITE_PAYMENTS_PLUS_ENABLED     => \Configuration::get(self::WEBSITE_PAYMENTS_PLUS_ENABLED),
            self::EXPRESS_CHECKOUT_ENABLED          => \Configuration::get(self::EXPRESS_CHECKOUT_ENABLED),
            self::LOGIN_ENABLED                     => \Configuration::get(self::LOGIN_ENABLED),
            self::LOGIN_THEME                       => \Configuration::get(self::LOGIN_THEME),

            self::CLIENT_ID => \Configuration::get(self::CLIENT_ID),
            self::SECRET    => \Configuration::get(self::SECRET),
        ];
    }

    /**
     * @return array
     */
    protected function getGeneralForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('General'),
                    'icon'  => 'icon-puzzle-piece',
                ],
                'input'  => [
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Your country'),
                        'name'     => self::STORE_COUNTRY,
                        'required' => true,
                        'options'  => [
                            'query' => \Country::getCountries($this->context->language->id),
                            'id'    => 'id_country',
                            'name'  => 'name',
                        ],
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Go Live'),
                        'name'   => self::LIVE,
                        'values' => [
                            [
                                'id'    => 'flushdb_on',
                                'value' => 1,
                                'label' => $this->l('Sandbox'),
                            ],
                            [
                                'id'    => 'flushdb_off',
                                'value' => 0,
                                'label' => $this->l('Live'),
                            ],
                        ],
                        'desc'   => $this->l('Enable this options to go live, otherwise the sandbox is used, which you can use to test your store.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getRestApiForm()
    {
        if (\Configuration::get(self::LIVE)) {
            $standardProfile = (string) \Configuration::get(self::STANDARD_WEBSITE_PROFILE_ID_LIVE);
            $plusProfile = (string) \Configuration::get(self::PLUS_WEBSITE_PROFILE_ID_LIVE);
            $expressCheckoutProfile = (string) \Configuration::get(self::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE);
        } else {
            $standardProfile = (string) \Configuration::get(self::STANDARD_WEBSITE_PROFILE_ID);
            $plusProfile = (string) \Configuration::get(self::PLUS_WEBSITE_PROFILE_ID);
            $expressCheckoutProfile = (string) \Configuration::get(self::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID);
        }

        if (!$this->context->smarty->getTemplateVars('standardProfile')) {
            $this->context->smarty->assign(
                [
                    'standardProfile'        => $standardProfile,
                    'plusProfile'            => $plusProfile,
                    'expressCheckoutProfile' => $expressCheckoutProfile,
                ]
            );
        }

        if ($standardProfile && $plusProfile && $expressCheckoutProfile) {
            $profileType = 'confirmation';
            $profileText = $this->display(__FILE__, 'views/templates/admin/profiles_correct.tpl');
        } else {
            $profileType = 'error';
            $profileText = $this->display(__FILE__, 'views/templates/admin/profiles_missing.tpl');
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Api Settings'),
                    'icon'  => 'icon-server',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('CLIENT ID'),
                        'name'  => self::CLIENT_ID,
                        'desc'  => $this->l('Enter the CLIENT ID'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('SECRET'),
                        'name'  => self::SECRET,
                        'desc'  => $this->l('Enter the SECRET'),
                    ],
                    [
                        'type'  => $profileType,
                        'label' => $this->l('Website Profiles'),
                        'name'  => self::STANDARD_WEBSITE_PROFILE_ID,
                        'text'  => $profileText,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getWebsitePaymentsStandardForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Website Payments Standard'),
                    'icon'  => 'icon-credit-card',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Website Payments Standard'),
                        'name'   => self::WEBSITE_PAYMENTS_STANDARD_ENABLED,
                        'values' => [
                            [
                                'id'    => 'flushdb_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'flushdb_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getWebsitePaymentsPlusForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Website Payments Plus'),
                    'icon'  => 'icon-credit-card',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Website Payments Plus'),
                        'name'   => self::WEBSITE_PAYMENTS_PLUS_ENABLED,
                        'values' => [
                            [
                                'id'    => 'flushdb_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'flushdb_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getExpressCheckoutForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Express Checkout'),
                    'icon'  => 'icon-credit-card',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Express Checkout'),
                        'name'   => self::EXPRESS_CHECKOUT_ENABLED,
                        'values' => [
                            [
                                'id'    => 'flushdb_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'flushdb_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getLoginForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('PayPal Login'),
                    'icon'  => 'icon-key',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable PayPal Login'),
                        'name'   => self::LOGIN_ENABLED,
                        'values' => [
                            [
                                'id'    => 'flushdb_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'flushdb_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type'    => 'radio',
                        'label'   => $this->l('Theme'),
                        'desc'    => $this->l('Choose the button style'),
                        'name'    => self::LOGIN_THEME,
                        'is_bool' => true,
                        'values'  => [
                            [
                                'id'    => 'neutral',
                                'value' => 0,
                                'label' => $this->l('Neutral'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypal_login_grey.png'),
                            ],
                            [
                                'id'    => 'blue',
                                'value' => 1,
                                'label' => $this->l('Blue'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypal_login_blue.png'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the main configuration page
     *
     * @return string
     */
    public function getMainConfigurationPage()
    {
        return $this->renderMainForm();
    }

    /**
     * Hooks methods
     *
     * @return string
     */
    public function hookHeader()
    {
        // TODO: check if this module should be hooked
        if (isset($this->context->cart) && $this->context->cart->id) {
            $this->context->smarty->assign('id_cart', (int) $this->context->cart->id);
        }

        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

        $smarty = $this->context->smarty;
        $smarty->assign(
            [
                self::LIVE         => \Configuration::get(self::LIVE),
                'confirmationPage' => $this->context->link->getPageLink('order-confirmation', true).'&id_cart='.$this->context->cart->id.'&id_module='.$this->id.'&key='.$this->context->cart->secure_key,
                'incontextType'    => (Tools::getValue('controller') == 'product') ? 'product' : 'cart',
            ]
        );

        $process = $this->display(__FILE__, 'views/templates/front/paypaljs.tpl');
        $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/api/checkout.js"></script>';

        if ((
                (method_exists($smarty, 'getTemplateVars') && ($smarty->getTemplateVars('page_name')
                        == 'authentication' || $smarty->getTemplateVars('page_name') == 'order-opc'))
                || (isset($smarty->_tpl_vars) && ($smarty->_tpl_vars['page_name']
                        == 'authentication' || $smarty->_tpl_vars['page_name'] == 'order-opc')))
            && \Configuration::get(self::LOGIN_ENABLED)
        ) {
            $this->context->smarty->assign(
                [
                    'paypal_locale' => $this->getLocale(),
                    'client_id'     => \Configuration::get(self::CLIENT_ID),
                    'login_theme'   => \Configuration::get(self::LOGIN_THEME),
                    'live'          => \Configuration::get(self::LIVE),
                    'return_link'   => PayPalLogin::getReturnLink(),
                ]
            );

            $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/js/external/api.js"></script>';
            $process .= '<script async defer type="text/javascript" src="'.\Media::getJSPath($this->_path.'views/js/login.js').'"></script>';
            $process .= $this->display(__FILE__, 'views/templates/front/paypal_loginjs.tpl');
        }

        return $process;
    }

    public function getLocale()
    {
        switch (\Language::getIsoById($this->context->language->id)) {
            case 'fr':
                return 'fr-fr';
            case 'hk':
                return 'zh-hk';
            case 'cn':
                return 'zh-cn';
            case 'tw':
                return 'zh-tw';
            case 'xc':
                return 'zh-xc';
            case 'dk':
                return 'da-dk';
            case 'nl':
                return 'nl-nl';
            case 'gb':
                return 'en-gb';
            case 'de':
                return 'de-de';
            case 'il':
                return 'he-il';
            case 'id':
                return 'id-id';
            case 'jp':
                return 'ja-jp';
            case 'no':
                return 'no-no';
            case 'pt':
                return 'pt-pt';
            case 'pl':
                return 'pl-pl';
            case 'ru':
                return 'ru-ru';
            case 'es':
                return 'es-es';
            case 'se':
                return 'sv-se';
            case 'th':
                return 'th-th';
            case 'tr':
                return 'tr-tr';
            default:
                return 'en-gb';
        }
    }

    /**
     * @return bool
     */
    public function canBeUsed()
    {
        return true;
    }

    /**
     * Product Footer hook
     *
     * @return null|string Hook HTML
     */
    public function hookProductFooter()
    {
        if (!\Configuration::get(self::EXPRESS_CHECKOUT_ENABLED)) {
            return null;
        }

        return $this->renderExpressCheckoutForm('product');
    }

    /**
     * @param string $type
     *
     * @return null|string
     */
    public function renderExpressCheckoutForm($type)
    {
        $idProduct = (int) \Tools::getValue('id_product');
        $idProductAttribute = (int) \Product::getDefaultAttribute($idProduct);
        if ($idProductAttribute) {
            $minimalQuantity = \Attribute::getAttributeMinimalQty($idProductAttribute);
        } else {
            $product = new \Product($idProduct);
            $minimalQuantity = $product->minimal_quantity;
        }

        $this->context->smarty->assign(
            [
                'PayPal_payment_type'           => $type,
                'PayPal_current_page'           => $this->getCurrentUrl(),
                'id_product_attribute_ecs'      => $idProductAttribute,
                'product_minimal_quantity'      => $minimalQuantity,
                'express_checkout_payment_link' => $this->context->link->getModuleLink('paypal', 'expresscheckout', [], true),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * Get current URL
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        $protocolLink = \Tools::usingSecureMode() ? 'https://' : 'http://';
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');

        if (($pos !== false) && ($pos >= 0)) {
            $request = \Tools::substr($request, 0, $pos);
        }

        $params = urlencode($_SERVER['QUERY_STRING']);

        return $protocolLink.\Tools::getShopDomainSsl().$request.'?'.$params;
    }

    /**
     * DisplayPayment Hook
     *
     * @return null|string
     */
    public function hookPayment()
    {
        $isoLang = [
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'nl' => 'nl_NL',
        ];

        $this->context->smarty->assign(
            [
                'logos'            => PayPalLogos::getLogos($this->getLocale()),
                self::LIVE         => \Configuration::get(self::LIVE),
                'use_mobile'       => true,
                'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code])) ? $isoLang[$this->context->language->iso_code] : 'en_US',
            ]
        );

        $output = '';
        if (Configuration::get(self::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            $output .= $this->display(__FILE__, 'express_checkout_payment.tpl');
        }

        if (Configuration::get(self::WEBSITE_PAYMENTS_PLUS_ENABLED)) {
            $rest = new PayPalRestApi();
            $payment = $rest->createPayment(
                $this->context->link->getModuleLink($this->name, 'plussubmit', [], true),
                $this->context->link->getModuleLink($this->name, 'pluscancel', [], true),
                PayPalRestApi::PLUS_PROFILE
            );

            $approvalUrl = '';
            if (isset($payment->id) && $payment->id) {
                foreach ($payment->links as $link) {
                    if ($link->rel === 'approval_url') {
                        $approvalUrl = $link->href;
                        break;
                    }
                }
            }

            if ($approvalUrl) {
                $this->context->smarty->assign(
                    [
                        'approval_url' => $approvalUrl,
                        'mode'         => \Configuration::get(self::LIVE) ? 'live' : 'sandbox',
                        'language'     => $this->getLocalePayPalPlus(),
                        'country'      => $this->getCountryCode(),
                    ]
                );

                $output .= '<script async defer src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js" type="text/javascript"></script>';
                $output .= $this->display(__FILE__, 'paypal_plus_payment.tpl');
            }
        }

        return $output;
    }

    public function getLocalePayPalPlus()
    {
        switch (\Tools::strtolower($this->getCountryCode())) {
            case 'fr':
                return 'fr_FR';
            case 'hk':
                return 'zh_HK';
            case 'cn':
                return 'zh_CN';
            case 'tw':
                return 'zh_TW';
            case 'xc':
                return 'zh_XC';
            case 'dk':
                return 'da_DK';
            case 'nl':
                return 'nl_NL';
            case 'gb':
                return 'en_GB';
            case 'de':
                return 'de_DE';
            case 'il':
                return 'he_IL';
            case 'id':
                return 'id_ID';
            case 'it':
                return 'it_IT';
            case 'jp':
                return 'ja_JP';
            case 'no':
                return 'no_NO';
            case 'pt':
                return 'pt_PT';
            case 'pl':
                return 'pl_PL';
            case 'ru':
                return 'ru_RU';
            case 'es':
                return 'es_ES';
            case 'se':
                return 'sv_SE';
            case 'th':
                return 'th_TH';
            case 'tr':
                return 'tr_TR';
            default:
                return 'en_GB';
        }
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        $cart = new \Cart((int) $this->context->cookie->id_cart);
        $address = new \Address((int) $cart->id_address_invoice);
        $country = new \Country((int) $address->id_country);

        return $country->iso_code;
    }

    /**
     * Hook for Advanced EU checkout
     *
     * @return array|null
     */
    public function hookDisplayPaymentEU()
    {
        if (!$this->active) {
            return null;
        }

        if (isset($this->context->cookie->express_checkout)) {
            $this->redirectToConfirmation();
        }

        $paymentOptions = [];

        if (\Configuration::get(self::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            $paymentOptions[] = [
                'cta_text' => $this->l('PayPal or credit card'),
                'logo'     => Media::getMediaPath($this->_path.'views/img/default_logos/default_horizontal_large.png'),
                'action'   => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
            ];
        }

        if (\Configuration::get(self::WEBSITE_PAYMENTS_PLUS_ENABLED)) {
            $paymentOptions[] = [
                'cta_text' => $this->l('PayPal or credit card'),
                'logo'     => Media::getMediaPath($this->_path.'views/img/default_logos/default_horizontal_large.png'),
                'action'   => $this->context->link->getModuleLink($this->name, 'pluseu', [], true),
            ];
        }

        return $paymentOptions;
    }

    /**
     * Redirect to confirmation page
     */
    public function redirectToConfirmation()
    {
        // Check if user went through the payment preparation detail and completed it
        $detail = unserialize($this->context->cookie->express_checkout);

        if (!empty($detail['payer_id']) && !empty($detail['token'])) {
            $values = ['get_confirmation' => true];
            \Tools::redirect(\Context::getContext()->link->getModuleLink('paypal', 'incontextconfirm', $values));
        }
    }

    /**
     * @return null|string
     */
    public function hookShoppingCartExtra()
    {
        if (!$this->active
            || !\Configuration::get(self::EXPRESS_CHECKOUT_ENABLED)
        ) {
            return null;
        }

        $paypalLogos = PayPalLogos::getLogos($this->getLocale());

        $this->context->smarty->assign(
            [
                'PayPal_payment_type'                   => 'cart',
                'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton']) ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
                'PayPal_current_page'                   => $this->getCurrentUrl(),
                'PayPal_lang_code'                      => $this->context->language->iso_code ? $this->context->language->iso_code : 'en_US',
                'PayPal_tracking_code'                  => $this->getTrackingCode((int) \Configuration::get('PAYPAL_PAYMENT_METHOD')),
                'include_form'                          => true,
                'template_dir'                          => dirname(__FILE__).'/views/templates/hook/',
                'express_checkout_payment_link'         => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * @param $method
     *
     * @return string
     */
    public function getTrackingCode($method)
    {
        $isApacCountry = $this->isCountryAPAC();

        //Get Seamless checkout
        $loginUser = false;
        if (\Configuration::get(self::LOGIN_ENABLED)) {
            $loginUser = PayPalLoginUser::getByIdCustomer((int) $this->context->customer->id);

            if ($loginUser && $loginUser->expires_in <= time()) {
                $obj = new PayPalLogin();
                $loginUser = $obj->getRefreshToken();
            }
        }

        if ($method == self::WPS) {
            if ($loginUser) {
                return $isApacCountry ? self::APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS : self::TRACKING_EXPRESS_CHECKOUT_SEAMLESS;
            } else {
                return $isApacCountry ? self::APAC_TRACKING_INTEGRAL : self::TRACKING_INTEGRAL;
            }

        }

        if ($method == self::EC) {
            if ($loginUser) {
                return $isApacCountry ? self::APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS : self::TRACKING_EXPRESS_CHECKOUT_SEAMLESS;
            } else {
                return $isApacCountry ? self::APAC_TRACKING_OPTION_PLUS : self::TRACKING_OPTION_PLUS;
            }

        }
        if ($method == self::WPP) {
            return $isApacCountry ? self::APAC_TRACKING_PAYPAL_PLUS : self::TRACKING_PAYPAL_PLUS;
        }

        return self::TRACKING_CODE;
    }

    /**
     * @return bool
     */
    public function isCountryAPAC()
    {
        return true;
    }

    /**
     * @return null|string
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }

        /** @var Order $order */
        $order = $params['order'];

        $currency = new Currency($order->id_currency);

        if (isset($order->reference) && $order->reference) {
            $totalToPay = (float) $order->getTotalPaid($currency);
            $reference = $order->reference;
        } else {
            $totalToPay = $order->total_paid_tax_incl;
            $reference = $this->l('Unknown');
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->context->smarty->assign('status', 'ok');
        }

        $this->context->smarty->assign(
            [
                'id_order'  => $order->id,
                'reference' => $reference,
                'params'    => $params,
                'total'     => Tools::displayPrice($totalToPay, $currency, false),
            ]
        );

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    /**
     * @return string
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @return string
     */
    public function hookRightColumn()
    {
        $this->context->smarty->assign('logo', PayPalLogos::getCardsLogo($this->getLocale(), true));

        return $this->display(__FILE__, 'column.tpl');
    }

    /**
     * @param array $params
     *
     * @return bool|null
     */
    public function hookBackBeforePayment($params)
    {
        if (!$this->active) {
            return null;
        }

        /* Only execute if you use PayPal API for payment */
        if ($this->isPayPalAPIAvailable()) {
            if ($params['module'] != $this->name || !$this->context->cookie->paypal_token
                || !$this->context->cookie->paypal_payer_id
            ) {
                return false;
            }

            \Tools::redirect($this->context->link->getModuleLink($this->name, 'plussubmit', [], true).'?confirm=1&token='.$this->context->cookie->paypal_token.'&payerID='.$this->context->cookie->paypal_payer_id);
        }

        return null;
    }

    /**
     * @return bool Indicates whether the PayPal API is available
     */
    public function isPayPalAPIAvailable()
    {
        if (\Configuration::get(self::CLIENT_ID) && \Configuration::get(self::SECRET)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookAdminOrder($params)
    {
        if (\Tools::isSubmit('submitPayPalCapture')) {
            if ($captureAmount = \Tools::getValue('totalCaptureMoney')) {
                if ($captureAmount = PayPalCapture::parsePrice($captureAmount)) {
                    if (\Validate::isFloat($captureAmount)) {
                        $captureAmount = \Tools::ps_round($captureAmount, '6');
                        $ord = new \Order((int) $params['id_order']);
                        $cpt = new PayPalCapture();

                        if (($captureAmount > \Tools::ps_round(0, '6')) && (\Tools::ps_round($cpt->getRestToPaid($ord), '6') >= $captureAmount)) {
                            $complete = false;

                            if ($captureAmount > \Tools::ps_round((float) $ord->total_paid, '6')) {
                                $captureAmount = \Tools::ps_round((float) $ord->total_paid, '6');
                                $complete = true;
                            }
                            if ($captureAmount == \Tools::ps_round($cpt->getRestToPaid($ord), '6')) {
                                $complete = true;
                            }

                            // TODO: implement manual capture
//                            $this->doCapture($params['id_order'], $captureAmount, $complete);
                        }
                    }
                }
            }
        }
        // TODO: implement refunds
//        elseif (\Tools::isSubmit('submitPayPalRefund')) {
//            $ppo = PayPalOrder::getOrderById($params['id_order']);
//            $this->doFullRefund($ppo['id_payment']);
//        }

        $adminTemplates = [];
        if ($this->isPayPalAPIAvailable()) {
            if ($this->needsValidation((int) $params['id_order'])) {
                $adminTemplates[] = 'validation';
            }

            if ($this->needsCapture((int) $params['id_order'])) {
                $adminTemplates[] = 'capture';
            }

            if ($this->canRefund((int) $params['id_order'])) {
                $adminTemplates[] = 'refund';
            }

        }

        if (count($adminTemplates) > 0) {
            $order = new \Order((int) $params['id_order']);
            $currency = new \Currency($order->id_currency);
            $cpt = new PayPalCapture();
            $cpt->id_order = (int) $order->id;
            $orderState = $order->current_state;

            $this->context->smarty->assign([
                'authorization'   => (int) \Configuration::get('PAYPAL_OS_AUTHORIZATION'),
                'base_url'        => _PS_BASE_URL_.__PS_BASE_URI__,
                'module_name'     => $this->name,
                'order_state'     => $orderState,
                'params'          => $params,
                'id_currency'     => $currency->getSign(),
                'rest_to_capture' => \Tools::ps_round($cpt->getRestToPaid($order), '6'),
                'list_captures'   => $cpt->getListCaptured(),
                'ps_version'      => _PS_VERSION_,
            ]);

            foreach ($adminTemplates as $adminTemplate) {
                $this->html .= $this->display(__FILE__, 'views/templates/admin/admin_order/'.$adminTemplate.'.tpl');
                $this->postProcess();
                $this->html .= '</fieldset>';
            }
        }

        return $this->html;
    }

    /**
     * Do a full refund
     *
     * @param string $idPayment
     *
     * @return bool Indicates whether the full refund was successful
     */
    protected function doFullRefund($idPayment)
    {
        return false;

//        $rest = new PayPalRestApi();
//        $payment = $rest->lookUpPayment($idPayment);
//
//        if (isset($payment->transactions[0]->related_resources[0]->sale->id)) {
//            $saleId = $payment->transactions[0]->related_resources[0]->sale->id;
//
//            // TODO: validate
//            if ($rest->executeRefund($saleId, new \stdClass())) {
//                return true;
//            }
//        }
//
//        return false;
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     */
    protected function needsValidation($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $sql = new \DbQuery();
        $sql->select('po.`payment_method`, po.`payment_status`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.(int) $idOrder);

        $order = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return $order && $order['payment_status']
            == 'Pending_validation';
    }

    /**
     * @param $idOrder
     *
     * @return bool
     */
    protected function needsCapture($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $sql = new \DbQuery();
        $sql->select('po.`payment_method`, po.`payment_status`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.(int) $idOrder);
        $sql->where('po.`capture` = 1');

        $result = \Db::getInstance()->getRow($sql);

        return $result && $result['payment_status'] == 'Pending_capture';
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     */
    protected function canRefund($idOrder)
    {
        if (!(bool) $idOrder) {
            return false;
        }

        $sql = new \DbQuery();
        $sql->select('po.`payment_status`, po.`capture`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.(int) $idOrder);

        $paypalOrder = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return $paypalOrder && ($paypalOrder['payment_status'] == 'Completed' || $paypalOrder['payment_status'] == 'approved') && $paypalOrder['capture'] == 0;
    }

    /**
     * @param array $params
     *
     * @return bool|null
     */
    public function hookCancelProduct($params)
    {
        /** @var \Order $order */
        if (\Tools::isSubmit('generateDiscount') || !$this->isPayPalAPIAvailable()
            || \Tools::isSubmit('generateCreditSlip')
        ) {
            return false;
        } elseif ($params['order']->module != $this->name || !($order = $params['order'])
            || !\Validate::isLoadedObject($order)
        ) {
            return false;
        } elseif (!$order->hasBeenPaid()) {
            return false;
        }

        $orderDetail = new \OrderDetail((int) $params['id_order_detail']);
        if (!$orderDetail || !\Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $paypalOrder = PayPalOrder::getOrderById((int) $order->id);
        if (!$paypalOrder) {
            return false;
        }

        $products = $order->getProducts();
        $cancelQuantity = \Tools::getValue('cancelQuantity');
        $message = $this->l('Cancel products result:').'<br>';

        $amount = (float) ($products[(int) $orderDetail->id]['product_price_wt'] * (int) $cancelQuantity[(int) $orderDetail->id]);
        $refund = $this->doRefund($paypalOrder['id_payment'], $order, $amount);
        $this->formatMessage($refund, $message);
        $this->addNewPrivateMessage((int) $order->id, $message);

        return null;
    }

    /**
     * @param string     $idPayment
     * @param \Order     $order
     * @param bool|float $amount Amount
     *
     * @return bool
     */
    protected function doRefund($idPayment, $order, $amount = false)
    {
        if (!$amount) {
            return $this->doFullRefund($idPayment);
        }

        $details = new \stdClass();
        $details->amount = (float) $amount;
        $details->currency = \Tools::strtoupper(\Currency::getCurrencyInstance($order->id_currency)->iso_code);

        // TODO: check if succeeded
        $rest = new PayPalRestApi();
        if ($rest->executeRefund($idPayment, $details)) {
            return true;
        }

        return false;
    }

    /**
     * Format message
     *
     * @param array  $response
     * @param string $message
     */
    public function formatMessage($response, &$message)
    {
        foreach ($response as $key => $value) {
            $message .= $key.': '.$value.'<br>';
        }

    }

    /**
     * Add new private message
     *
     * @param int    $idOrder
     * @param string $message
     *
     * @return bool
     */
    public function addNewPrivateMessage($idOrder, $message)
    {
        if (!(bool) $idOrder) {
            return false;
        }

        $newMessage = new \Message();
        $message = strip_tags($message, '<br>');

        if (!\Validate::isCleanHtml($message)) {
            $message = $this->l('Payment message is not valid, please check your module.');
        }

        $newMessage->message = $message;
        $newMessage->id_order = (int) $idOrder;
        $newMessage->private = 1;

        return $newMessage->add();
    }

    /**
     * @return array
     */
    public function hookActionPSCleanerGetModulesTables()
    {
        return ['paypal_customer', 'paypal_order'];
    }

    /**
     * @return null|string
     */
    public function hookBackOfficeHeader()
    {
        if ((strcmp(\Tools::getValue('configure'), $this->name) === 0) ||
            (strcmp(\Tools::getValue('module_name'), $this->name) === 0)
        ) {
            $this->context->controller->addJquery();
            $this->context->controller->addJQueryPlugin('fancybox');
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

            $this->context->smarty->assign(
                [
                    'PayPal_module_dir' => _MODULE_DIR_.$this->name,
                    'PayPal_WPS'        => (int) self::WPS,
                    'PayPal_ECS'        => (int) self::EC,
                    'PayPal_PPP'        => (int) self::WPP,
                ]
            );

            //return (isset($output) ? $output : null).$this->display(__FILE__, 'views/templates/admin/header.tpl');
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return null|string
     */
    public function renderExpressCheckoutButton($type)
    {
        if ((!\Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT') && !$this->context->getMobileDevice())) {
            return null;
        }

        $paypalLogos = PayPalLogos::getLogos($this->getLocale());
        $isoLang = [
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
        ];

        $this->context->smarty->assign(
            [
                'use_mobile'                            => (bool) $this->context->getMobileDevice(),
                'PayPal_payment_type'                   => $type,
                'PayPal_current_page'                   => $this->getCurrentUrl(),
                'PayPal_lang_code'                      => (isset($isoLang[$this->context->language->iso_code])) ? $isoLang[$this->context->language->iso_code] : 'en_US',
                'PayPal_tracking_code'                  => $this->getTrackingCode((int) \Configuration::get('PAYPAL_PAYMENT_METHOD')),
                'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton']) ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
                'express_checkout_payment_link'         => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * @return bool
     */
    public function getTranslations()
    {
        $file = dirname(__FILE__).'/'.self::_PAYPAL_TRANSLATIONS_XML_;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            if (isset($xml) && $xml) {
                $index = -1;
                $content = $default = [];

                while (isset($xml->country[++$index])) {
                    $country = $xml->country[$index];
                    $countryIso = $country->attributes()->iso_code;

                    if (($this->iso_code != 'default') && ($countryIso == $this->iso_code)) {
                        $content = (array) $country;
                    } elseif ($countryIso == 'default') {
                        $default = (array) $country;
                    }

                }

                $content += $default;
                $this->context->smarty->assign('PayPal_content', $content);

                return true;
            }
        }

        return false;
    }

    /**
     * @return string PayPal Website Payments Standard or Express Checkout URL
     */
    public function getPaypalStandardUrl()
    {
        return 'https://'.$this->getPayPalURL().'/cgi-bin/webscr';
    }

    /**
     * @return string PayPal base URL
     */
    public function getPayPalURL()
    {
        return 'www'.(!\Configuration::get(self::LIVE) ? '.sandbox' : '').'.paypal.com';
    }

    /**
     * Get PayPal API URL
     *
     * @return string
     */
    public function getAPIURL()
    {
        return 'api-3t'.(!\Configuration::get(self::LIVE) ? '.sandbox' : '').'.paypal.com';
    }

    /**
     * Get API Script
     *
     * @return string API Script
     */
    public function getAPIScript()
    {
        return '/nvp';
    }

    /**
     * @param string $message
     * @param bool   $log
     *
     * @return string
     */
    public function displayPayPalAPIError($message, $log = false)
    {
        $send = true;
        // Sanitize log
        if (is_array($log)) {
            foreach ($log as $key => $string) {
                if ($string == 'ACK -> Success') {
                    $send = false;
                } elseif (\Tools::substr($string, 0, 6) == 'METHOD') {
                    $values = explode('&', $string);
                    foreach ($values as $key2 => $value) {
                        $values2 = explode('=', $value);
                        foreach ($values2 as $key3 => $value2) {
                            if ($value2 == 'PWD' || $value2 == 'SIGNATURE') {
                                $values2[$key3 + 1] = '*********';
                            }
                        }

                        $values[$key2] = implode('=', $values2);
                    }
                    $log[$key] = implode('&', $values);
                }
            }
        }

        $this->context->smarty->assign(['message' => $message, 'logs' => $log]);

        if ($send) {
            $idLang = (int) $this->context->language->id;
            $isoLang = \Language::getIsoById($idLang);

            if (!is_dir(dirname(__FILE__).'/mails/'.\Tools::strtolower($isoLang))) {
                $idLang = \Language::getIdByIso('en');
            }

            \Mail::Send(
                $idLang,
                'error_reporting',
                \Mail::l('Error reporting from your PayPal module', (int) $this->context->language->id),
                ['{logs}' => implode('<br />', $log)],
                \Configuration::get('PS_SHOP_EMAIL'),
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_.$this->name.'/mails/'
            );
        }

        return $this->display(__FILE__, 'error.tpl');
    }

    /**
     * Validate the order
     *
     * @param int        $idCart
     * @param int        $idOrderState
     * @param float      $amountPaid
     * @param string     $paymentMethod
     * @param null       $message
     * @param array      $transaction
     * @param null       $currencySpecial
     * @param bool       $dontTouchAmount
     * @param bool       $secureKey
     * @param \Shop|null $shop
     *
     * @return bool
     */
    public function validateOrder(
        $idCart,
        $idOrderState,
        $amountPaid,
        $paymentMethod = 'Unknown',
        $message = null,
        $transaction = [],
        $currencySpecial = null,
        $dontTouchAmount = false,
        $secureKey = false,
        \Shop $shop = null
    ) {
        if ($this->active) {
            // Set transaction details if pcc is defined in PaymentModule class_exists
            if (isset($this->pcc)) {
                $this->pcc->transaction_id = (isset($transaction['transaction_id']) ? $transaction['transaction_id'] : '');
            }

            parent::validateOrder(
                (int) $idCart,
                (int) $idOrderState,
                (float) $amountPaid,
                $paymentMethod,
                $message,
                $transaction,
                $currencySpecial,
                $dontTouchAmount,
                $secureKey,
                $shop
            );

            if (count($transaction) > 0) {
                PayPalOrder::saveOrder((int) $this->currentOrder, $transaction);
            }
        }

        return true;
    }

    /**
     * Get gift wrapping price
     *
     * @return float
     */
    public function getGiftWrappingPrice()
    {
        $wrappingFeesTaxInc = $this->context->cart->getGiftWrappingPrice();

        return (float) \Tools::convertPrice($wrappingFeesTaxInc, $this->context->currency);
    }

    /**
     * Assign cart summary
     */
    public function assignCartSummary()
    {
        $currency = new \Currency((int) $this->context->cart->id_currency);

        $this->context->smarty->assign(
            [
                'total'            => \Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
                'logos'            => PayPalLogos::getLogos($this->getLocale()),
                'use_mobile'       => (bool) $this->context->getMobileDevice(),
                'address_shipping' => new \Address($this->context->cart->id_address_delivery),
                'address_billing'  => new \Address($this->context->cart->id_address_invoice),
                'cart'             => $this->context->cart,
                'patternRules'     => ['avoid' => []],
                'cart_image_size'  => 'cart_default',
                'useStyle14'       => false,
                'useStyle15'       => false,
            ]
        );

        $this->context->smarty->assign(
            [
                'paypal_cart_summary' => $this->display(__FILE__, 'views/templates/hook/paypal_cart_summary.tpl'),
            ]
        );
    }

    /**
     * Do a capture
     *
     * @param int        $idOrder
     * @param bool|float $captureAmount
     * @param bool       $isComplete
     *
     * @return bool
     */
    protected function doCapture($idOrder, $captureAmount = false, $isComplete = false)
    {
        // FIXME: not implemented

        return null;
    }

    protected function warningsCheck()
    {
        /* Check preactivation warning */
        if (\Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING')) {
            $this->warning .= (!empty($this->warning)) ? ', ' : \Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING').'<br />';
        }

        if (!function_exists('curl_init')) {
            $this->warning .= $this->l('In order to use your module, please activate cURL (PHP extension)');
        }

    }

    /**
     * Check cart currency
     *
     * @param \Cart $cart
     *
     * @return bool Indicates whether this module can accept the currency
     */
    protected function checkCurrency(\Cart $cart)
    {
        $currencyModule = $this->getCurrency((int) $cart->id_currency);

        if ((int) $cart->id_currency == (int) $currencyModule->id) {
            return true;
        } else {
            return false;
        }
    }
}
