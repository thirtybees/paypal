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

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

use PayPalModule\Exception\Auth\TokenException;
use PayPalModule\Exception\Payment\PaymentException;
use PayPalModule\PayPalCapture;
use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalLogin;
use PayPalModule\PayPalLoginUser;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalRestApi;
use PayPalModule\PayPalTools;

/**
 * Class PayPal
 */
class PayPal extends PaymentModule
{
    const LIVE = 'PAYPAL_LIVE';
    const IMMEDIATE_CAPTURE = 'PAYPAL_CAPTURE';
    const STORE_COUNTRY = 'PAYPAL_COUNTRY_DEFAULT';
    const CLIENT_ID = 'PAYPAL_CLIENT_ID';
    const SECRET = 'PAYPAL_SECRET';
    const ACCESS_TOKEN = 'PAYPAL_ACCESS_TOKEN';
    const ACCESS_TOKEN_EXPIRE = 'PAYPAL_ACCESS_TOKEN_EXPIRE';

    const STANDARD_WEBSITE_PROFILE_ID = 'PAYPAL_WEB_PROFILE_ID';
    const EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID = 'PAYPAL_EC_WEB_PROFILE_ID';
    const PLUS_WEBSITE_PROFILE_ID = 'PAYPAL_WPP_WEB_PROFILE_ID';
    const STANDARD_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_WEB_PROFILE_ID_LIVE';
    const EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_EC_WEB_PROFILE_ID_LIVE';
    const PLUS_WEBSITE_PROFILE_ID_LIVE = 'PAYPAL_WPP_WEB_PROFILE_ID_LIVE';

    const WEBHOOK_CHECK_INTERVAL = 86400;
    const WEBHOOK_LAST_CHECK = 'PAYPAL_WEBHOOK_UPD';
    const WEBHOOK_ID = 'PAYPAL_WEBHOOK_ID'; //daily check

    const WPS = 1;
    const EC = 4;
    const WPP = 5;

    const WEBSITE_PAYMENTS_STANDARD_ENABLED = 'PAYPAL_WPS_ENABLED';
    const WEBSITE_PAYMENTS_PLUS_ENABLED = 'PAYPAL_WPP_ENABLED';
    const EXPRESS_CHECKOUT_ENABLED = 'PAYPAL_EC_ENABLED';
    const EXPRESS_CHECKOUT_CREDIT = 'PAYPAL_EC_CREDIT';
    const EXPRESS_CHECKOUT_CARDS = 'PAYPAL_EC_CARDS';
    const EXPRESS_CHECKOUT_SEPA = 'PAYPAL_EC_SEPA';
    const EXPRESS_CHECKOUT_SHAPE = 'PAYPAL_EC_SHAPE';
    const EXPRESS_CHECKOUT_COLOR = 'PAYPAL_EC_COLOR';

    const EXPRESS_CHECKOUT_SHAPE_RECT = 'rect';
    const EXPRESS_CHECKOUT_SHAPE_PILL = 'pill';

    const EXPRESS_CHECKOUT_COLOR_GOLD = 'gold';
    const EXPRESS_CHECKOUT_COLOR_BLUE = 'blue';
    const EXPRESS_CHECKOUT_COLOR_SILVER = 'silver';
    const EXPRESS_CHECKOUT_COLOR_BLACK = 'black';

    const PAYPAL_LOGIN_COLOR_NEUTRAL = 'neutral';
    const PAYPAL_LOGIN_COLOR_BLUE = 'blue';

    const LOGIN_ENABLED = 'PAYPAL_LOGIN_ENABLED';
    const LOGIN_THEME = 'PAYPAL_LOGIN_TPL';
    const TLS_OK = 'PAYPAL_TLS_OK';
    const TLS_LAST_CHECK = 'PAYPAL_TLS_LAST_CHECK';
    const ENUM_TLS_OK = 1;
    const ENUM_TLS_ERROR = -1;

    const CONNECTION_TIMEOUT = 20;

    // @codingStandardsIgnoreStart
    /** @var array $checkoutButtonTypes Available express checkout button types */
    public static $checkoutButtonTypes = ['cart', 'product'];
    /** @var array $postalCodeRequired */
    public static $postalCodeRequired = ['AR', 'AU', 'AT', 'BT', 'BR', 'CA', 'C2', 'DK', 'FK', 'FO', 'FR', 'GM', 'DE', 'GL', 'IT', 'JP', 'KI', 'KG', 'MW', 'MR', 'YT', 'MX', 'NR', 'NL', 'NE', 'NU', 'NF', 'NO', 'PN', 'PL', 'RU', 'SG', 'ES', 'SH', 'PM', 'SR', 'SJ', 'SE', 'CH', 'TV', 'GB', 'US', 'VA', 'WF'];
    /** @var array $hooks */
    public $hooks = [
        'payment',
        'paymentReturn',
        'shoppingCartExtra',
        'backBeforePayment',
        'rightColumn',
        'cancelProduct',
        'productActions',
        'header',
        'adminOrder',
        'backOfficeHeader',
        'displayPaymentEU',
        'actionPSCleanerGetModulesTables',
    ];
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
    // @codingStandardsIgnoreEnd

    /**
     * PayPal constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '6.0.0';
        $this->author = 'thirty bees';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayPal');
        $this->description = $this->l('Accepts payments by credit cards (CB, Visa, MasterCard, Amex, Aurore, Cofinoga, 4 stars) with PayPal.');

        $this->controllers = [
            'expresscheckout',
            'expresscheckoutconfirm',
            'standardcancel',
            'incontextajax',
            'incontextvalidate',
            'pluseu',
            'pluscancel',
            'logintoken',
        ];

        // Only check from Back Office
        if (isset(Context::getContext()->employee->id) && Context::getContext()->employee->id) {
            $adminModulesLocation = $this->context->link->getAdminLink('AdminModules', true);
            $this->moduleUrl = $adminModulesLocation.'&'.http_build_query([
                    'configure'   => $this->name,
                    'tab_module'  => $this->tab,
                    'module_name' => $this->name,
                ]);

            $this->checkWebhooks();
        }

        PayPalRestApi::setCredentials(Configuration::get(static::CLIENT_ID), Configuration::get(static::SECRET));
    }

    /**
     * @param int $idCustomer
     *
     * @return mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPayPalEmailByIdCustomer($idCustomer)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('pc.`paypal_email`')
                ->from('paypal_customer', 'pc')
                ->where('pc.`id_customer` = '.(int) $idCustomer)
        );
    }

    /**
     * Install the module
     *
     * @return bool Indicates whether the module was installed successfully
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        foreach ($this->hooks as $hook) {
            $this->registerHook($hook);
        }

        if (!(PayPalCapture::createDatabase()
            && PayPalCustomer::createDatabase()
            && PayPalLoginUser::createDatabase()
            && PayPalOrder::createDatabase())
        ) {
            return false;
        }

        Configuration::updateValue(static::LIVE, false);
        Configuration::updateValue(static::IMMEDIATE_CAPTURE, true);

        $this->createOrderStates();

        $paypalTools = new PayPalTools($this->name);
        $paypalTools->moveTopPayments(1);
        $paypalTools->moveRightColumn(3);

        return true;
    }

    /**
     * Create new order states
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createOrderStates()
    {
        if (!Configuration::get('PAYPAL_OS_AUTHORIZATION')) {
            $orderState = new OrderState();
            $orderState->name = [];

            foreach (Language::getLanguages() as $language) {
                if (strtolower($language['iso_code']) == 'fr') {
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
                $source = dirname(__FILE__).'/../../img/os/'.Configuration::get('PS_OS_PAYPAL').'.gif';
                $destination = dirname(__FILE__).'/../../img/os/'.(int) $orderState->id.'.gif';
                copy($source, $destination);
                Configuration::updateValue('PAYPAL_OS_AUTHORIZATION', (int) $orderState->id);
            } else {
                // TODO: check if order states already exist
                throw new PrestaShopException('Unable to create PayPal order states');
            }
        }
    }

    /**
     * Uninstall the module
     *
     * @return bool Indicates whether the module was uninstalled successfully
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $this->deleteConfiguration();

        return parent::uninstall();
    }

    /**
     * Delete PayPal configuration
     *
     * @throws PrestaShopException
     */
    public function deleteConfiguration()
    {
        Configuration::deleteByName(static::LIVE);

        Configuration::deleteByName(static::IMMEDIATE_CAPTURE);
        Configuration::deleteByName(static::DEBUG_MODE);
        Configuration::deleteByName(static::STORE_COUNTRY);

        Configuration::deleteByName(static::LOGIN_ENABLED);
        Configuration::deleteByName(static::CLIENT_ID);
        Configuration::deleteByName(static::SECRET);
        Configuration::deleteByName(static::LOGIN_THEME);
    }

    /**
     * Get module configuration page
     *
     * @return string HTML
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        if (!Configuration::get('PS_SHOP_ENABLE')) {
            $this->context->controller->warnings[] = $this->l('Maintenance mode has been enabled. Note that you will not be able to do test payments as long as maintenance mode is enabled.');
        }

        $output = '';

        $this->postProcess();

        $this->context->smarty->assign([
            'module_url' => $this->moduleUrl,
            'tls_ok'     => (int) Configuration::get(static::TLS_OK),
            'id_webhook' => Configuration::get(static::WEBHOOK_ID),
        ]);

        $this->context->controller->addCSS($this->_path.'views/css/back.css', 'all');
        $this->context->controller->addJS($this->_path.'views/js/back.js');

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->display(__FILE__, 'views/templates/admin/tlscheck.tpl');
        $output .= $this->display(__FILE__, 'views/templates/admin/webhookscheck.tpl');

        $output .= $this->renderMainForm();

        return $output;
    }

    /**
     * Post process
     *
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('checktls') && (bool) Tools::getValue('checktls')) {
            $this->tlsCheck();
        }

        if (Tools::isSubmit('checkWebhooks') && (bool) Tools::getValue('checkWebhooks')) {
            $this->checkWebhooks(true);
            $this->_confirmations[] = $this->l('Webhook check was successfully run. The result is shown in the corresponding panel.');
        }

        if (Tools::isSubmit('submit'.$this->name)) {
            // General
            Configuration::updateValue(static::STORE_COUNTRY, (int) Tools::getValue(static::STORE_COUNTRY));
            Configuration::updateValue(static::LIVE, (int) Tools::getValue(static::LIVE));
            Configuration::updateValue(static::IMMEDIATE_CAPTURE, (int) Tools::getValue(static::IMMEDIATE_CAPTURE));

            // REST API
            if (Configuration::get(static::CLIENT_ID) !== Tools::getValue(static::CLIENT_ID)) {
                // Client ID has changed, reset webhook status
                Configuration::updateValue(static::WEBHOOK_ID, null);
            }
            Configuration::updateValue(static::CLIENT_ID, Tools::getValue(static::CLIENT_ID));
            Configuration::updateValue(static::SECRET, Tools::getValue(static::SECRET));
            PayPalRestApi::setCredentials(Tools::getValue(static::CLIENT_ID), Tools::getValue(static::SECRET));

            if (Tools::getValue(static::CLIENT_ID) && Tools::getValue(static::SECRET)) {
                $rest = PayPalRestApi::getInstance();
                $rest->getWebProfiles();
                /** @var array $standardProfile */
                try {
                    $standardProfile = $rest->getWebProfile(PayPalRestApi::STANDARD_PROFILE);
                    /** @var array $plusProfile */
                    $plusProfile = $rest->getWebProfile(PayPalRestApi::PLUS_PROFILE);
                    /** @var array $expressCheckoutProfile */
                    $expressCheckoutProfile = $rest->getWebProfile(PayPalRestApi::EXPRESS_CHECKOUT_PROFILE);
                    if (Tools::getValue(static::LIVE)) {
                        if ($standardProfile) {
                            Configuration::updateValue(static::STANDARD_WEBSITE_PROFILE_ID_LIVE, $standardProfile['id']);
                        }
                        if ($plusProfile) {
                            Configuration::updateValue(static::PLUS_WEBSITE_PROFILE_ID_LIVE, $plusProfile['id']);
                        }
                        if ($expressCheckoutProfile) {
                            Configuration::updateValue(static::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE, $expressCheckoutProfile['id']);
                        }
                    } else {
                        if ($standardProfile) {
                            Configuration::updateValue(static::STANDARD_WEBSITE_PROFILE_ID, $standardProfile['id']);
                        }
                        if ($plusProfile) {
                            Configuration::updateValue(static::PLUS_WEBSITE_PROFILE_ID, $plusProfile['id']);
                        }
                        if ($expressCheckoutProfile) {
                            Configuration::updateValue(static::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID, $expressCheckoutProfile['id']);
                        }
                    }
                } catch (TokenException $e) {
                }
            }

            // Website Payments Standard
            Configuration::updateValue(static::WEBSITE_PAYMENTS_STANDARD_ENABLED, Tools::getValue(static::WEBSITE_PAYMENTS_STANDARD_ENABLED));

            // Website Payments Plus
            Configuration::updateValue(static::WEBSITE_PAYMENTS_PLUS_ENABLED, Tools::getValue(static::WEBSITE_PAYMENTS_PLUS_ENABLED));

            // Express Checkout
            Configuration::updateValue(static::EXPRESS_CHECKOUT_ENABLED, (bool) Tools::getValue(static::EXPRESS_CHECKOUT_ENABLED));
            Configuration::updateValue(static::EXPRESS_CHECKOUT_CARDS, (bool) Tools::getValue(static::EXPRESS_CHECKOUT_CARDS));
            Configuration::updateValue(static::EXPRESS_CHECKOUT_CREDIT, (bool) Tools::getValue(static::EXPRESS_CHECKOUT_CREDIT));
            Configuration::updateValue(static::EXPRESS_CHECKOUT_SEPA, (bool) Tools::getValue(static::EXPRESS_CHECKOUT_SEPA));
            Configuration::updateValue(static::EXPRESS_CHECKOUT_SHAPE, Tools::getValue(static::EXPRESS_CHECKOUT_SHAPE));
            Configuration::updateValue(static::EXPRESS_CHECKOUT_COLOR, Tools::getValue(static::EXPRESS_CHECKOUT_COLOR));

            // PayPal Login
            Configuration::updateValue(static::LOGIN_ENABLED, (int) Tools::getValue(static::LOGIN_ENABLED));
            Configuration::updateValue(static::LOGIN_THEME, (int) Tools::getValue(static::LOGIN_THEME));
        }
    }

    /**
     * Check if server supports TLSv1.2
     *
     * @throws PrestaShopException
     */
    protected function tlsCheck()
    {
        $guzzle = new \GuzzleHttp\Client([
            'timeout' => 10,
            'verify'  => _PS_TOOL_DIR_.'cacert.pem',
        ]);
        try {
            $response = $guzzle->get('https://tlstest.paypal.com/');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->context->controller->errors[] = "PayPal connection test error: {$e->getMessage()} -- {$responseBody}";
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $this->context->controller->errors[] = "PayPal connection test error: {$e->getMessage()}";
        }

        if ((string) $response->getBody() === 'PayPal_Connection_OK') {
            $this->updateAllValue(static::TLS_OK, static::ENUM_TLS_OK);
        } else {
            $this->updateAllValue(static::TLS_OK, static::ENUM_TLS_ERROR);
        }
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * Render the main form
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderMainForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = AdminController::$currentIndex.'&'.http_build_query([
                'configure' => $this->name,
            ]);
        $helper->token = Tools::getAdminTokenLite('AdminModules');
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

    /**
     * Get main form configuration values
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getMainFormValues()
    {
        return [
            static::STORE_COUNTRY               => (int) Configuration::get(static::STORE_COUNTRY),
            static::LIVE                        => Configuration::get(static::LIVE),
            static::STANDARD_WEBSITE_PROFILE_ID => Configuration::get(static::STANDARD_WEBSITE_PROFILE_ID),

            static::WEBSITE_PAYMENTS_STANDARD_ENABLED => Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED),
            static::WEBSITE_PAYMENTS_PLUS_ENABLED     => Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED),
            static::EXPRESS_CHECKOUT_ENABLED          => Configuration::get(static::EXPRESS_CHECKOUT_ENABLED),
            static::EXPRESS_CHECKOUT_CARDS            => Configuration::get(static::EXPRESS_CHECKOUT_CARDS),
            static::EXPRESS_CHECKOUT_CREDIT           => Configuration::get(static::EXPRESS_CHECKOUT_CREDIT),
            static::EXPRESS_CHECKOUT_SEPA             => Configuration::get(static::EXPRESS_CHECKOUT_SEPA),
            static::EXPRESS_CHECKOUT_SHAPE            => Configuration::get(static::EXPRESS_CHECKOUT_SHAPE),
            static::EXPRESS_CHECKOUT_COLOR            => Configuration::get(static::EXPRESS_CHECKOUT_COLOR),
            static::LOGIN_ENABLED                     => Configuration::get(static::LOGIN_ENABLED),
            static::LOGIN_THEME                       => Configuration::get(static::LOGIN_THEME),

            static::CLIENT_ID => Configuration::get(static::CLIENT_ID),
            static::SECRET    => Configuration::get(static::SECRET),
        ];
    }

    /**
     * Get the general form structure
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getGeneralForm()
    {
        $countries = Country::getCountries($this->context->language->id);
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('General'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Your country'),
                        'name'     => static::STORE_COUNTRY,
                        'required' => true,
                        'options'  => [
                            'query' => $countries,
                            'id'    => 'id_country',
                            'name'  => 'name',
                        ],
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Go Live'),
                        'name'   => static::LIVE,
                        'desc'   => $this->l('Enable this options to go live, otherwise the sandbox is used, which you can use to test your store.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the REST API form structure
     *
     * @return array
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getRestApiForm()
    {
        if (Configuration::get(static::LIVE)) {
            $standardProfile = (string) Configuration::get(static::STANDARD_WEBSITE_PROFILE_ID_LIVE);
            $plusProfile = (string) Configuration::get(static::PLUS_WEBSITE_PROFILE_ID_LIVE);
            $expressCheckoutProfile = (string) Configuration::get(static::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID_LIVE);
        } else {
            $standardProfile = (string) Configuration::get(static::STANDARD_WEBSITE_PROFILE_ID);
            $plusProfile = (string) Configuration::get(static::PLUS_WEBSITE_PROFILE_ID);
            $expressCheckoutProfile = (string) Configuration::get(static::EXPRESS_CHECKOUT_WEBSITE_PROFILE_ID);
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
                        'name'  => static::CLIENT_ID,
                        'desc'  => $this->l('Enter the CLIENT ID'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('SECRET'),
                        'name'  => static::SECRET,
                        'desc'  => $this->l('Enter the SECRET'),
                    ],
                    [
                        'type'  => $profileType,
                        'label' => $this->l('Website Profiles'),
                        'name'  => static::STANDARD_WEBSITE_PROFILE_ID,
                        'text'  => $profileText,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the Website Payments Standard form structure
     *
     * @return array
     */
    protected function getWebsitePaymentsStandardForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Website Payments Standard'),
                    'icon'  => 'icon-cc-paypal',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Website Payments Standard'),
                        'name'   => static::WEBSITE_PAYMENTS_STANDARD_ENABLED,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the Website Payments Plus form structure
     *
     * @return array
     */
    protected function getWebsitePaymentsPlusForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Website Payments Plus (Germany only)'),
                    'icon'  => 'icon-cc-paypal',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Website Payments Plus (Germany only)'),
                        'name'   => static::WEBSITE_PAYMENTS_PLUS_ENABLED,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the Express Checkout form structure
     *
     * @return array
     */
    protected function getExpressCheckoutForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Express Checkout'),
                    'icon'  => 'icon-cc-paypal',
                ],
                'input'  => [
                    [
                        'type'  => 'imageswitch',
                        'label' => $this->l('Enable Express Checkout'),
                        'name'  => static::EXPRESS_CHECKOUT_ENABLED,
                        'image' => [
                            'src'    => Media::getMediaPath(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-express.png"),
                            'width'  => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-express.png")[0],
                            'height' => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-express.png")[1],
                        ],
                    ],
                    [
                        'type' => 'hr',
                        'name' => '',
                    ],
                    [
                        'type'  => 'imageswitch',
                        'label' => $this->l('Show credit card icons'),
                        'name'  => static::EXPRESS_CHECKOUT_CARDS,
                        'image' => [
                            'src'    => Media::getMediaPath(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-cards.png"),
                            'width'  => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-cards.png")[0],
                            'height' => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-cards.png")[1],
                        ],
                        'desc'  => $this->l('NOTE: not every customer will see these icons'),
                    ],
                    [
                        'type' => 'hr',
                        'name' => '',
                    ],
                    [
                        'type'  => 'imageswitch',
                        'label' => $this->l('Show PayPal Credit icon'),
                        'name'  => static::EXPRESS_CHECKOUT_CREDIT,
                        'image' => [
                            'src'    => Media::getMediaPath(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-credit.png"),
                            'width'  => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-credit.png")[0],
                            'height' => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-credit.png")[1],
                        ],
                        'desc'  => $this->l('NOTE: this option is only shown to US customers'),
                    ],
                    [
                        'type' => 'hr',
                        'name' => '',
                    ],
                    [
                        'type'  => 'imageswitch',
                        'label' => $this->l('Show SEPA Lastschrift icon (Germany only)'),
                        'name'  => static::EXPRESS_CHECKOUT_SEPA,
                        'image' => [
                            'src'    => Media::getMediaPath(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-sepa.png"),
                            'width'  => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-sepa.png")[0],
                            'height' => getimagesize(_PS_MODULE_DIR_."{$this->name}/views/img/button-paypal-sepa.png")[1],
                        ],
                        'desc' => $this->l('NOTE: this payment method is only available to German customers'),
                    ],
                    [
                        'type' => 'hr',
                        'name' => '',
                    ],
                    [
                        'type'     => 'radio',
                        'label'    => $this->l('Shape'),
                        'desc'     => $this->l('Choose the button shape'),
                        'name'     => static::EXPRESS_CHECKOUT_SHAPE,
                        'is_bool'  => true,
                        'distance' => 80,
                        'margin'   => 15,
                        'values'   => [
                            [
                                'id'    => 'rect',
                                'value' => static::EXPRESS_CHECKOUT_SHAPE_RECT,
                                'label' => $this->l('Rectangle'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalgold.png'),
                            ],
                            [
                                'id'    => 'pill',
                                'value' => static::EXPRESS_CHECKOUT_SHAPE_PILL,
                                'label' => $this->l('Pill'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalpill.png'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'hr',
                        'name' => '',
                    ],
                    [
                        'type'     => 'radio',
                        'label'    => $this->l('Color'),
                        'desc'     => $this->l('Choose the button color'),
                        'name'     => static::EXPRESS_CHECKOUT_COLOR,
                        'is_bool'  => true,
                        'margin'   => 15,
                        'values'   => [
                            [
                                'id'    => 'gold',
                                'value' => static::EXPRESS_CHECKOUT_COLOR_GOLD,
                                'label' => $this->l('Gold'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalgold.png'),
                            ],
                            [
                                'id'    => 'blue',
                                'value' => static::EXPRESS_CHECKOUT_COLOR_BLUE,
                                'label' => $this->l('Blue'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalblue.png'),
                            ],
                            [
                                'id'    => 'silver',
                                'value' => static::EXPRESS_CHECKOUT_COLOR_SILVER,
                                'label' => $this->l('Silver'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalsilver.png'),
                            ],
                            [
                                'id'    => 'black',
                                'value' => static::EXPRESS_CHECKOUT_COLOR_BLACK,
                                'label' => $this->l('Black'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypalblack.png'),
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
     * Get the login form structure
     *
     * @return array
     */
    protected function getLoginForm()
    {
        return [
            'form' => [
                'legend'      => [
                    'title' => $this->l('PayPal Login'),
                    'icon'  => 'icon-key',
                ],
                'description' => sprintf($this->l('Use the following link for this store (repeat if you have multiple stores!) as the %sredirect_uri%s setting:'), '<code>', '</code>').' '.PayPalLogin::getReturnLink(),
                'input'       => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable PayPal Login'),
                        'name'   => static::LOGIN_ENABLED,
                    ],
                    [
                        'type'    => 'radio',
                        'label'   => $this->l('Theme'),
                        'desc'    => $this->l('Choose the button style'),
                        'name'    => static::LOGIN_THEME,
                        'is_bool' => true,
                        'margin'  => 6,
                        'values'  => [
                            [
                                'id'    => 'neutral',
                                'value' => static::PAYPAL_LOGIN_COLOR_NEUTRAL,
                                'label' => $this->l('Neutral'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypal_login_grey.png'),
                            ],
                            [
                                'id'    => 'blue',
                                'value' => static::PAYPAL_LOGIN_COLOR_BLUE,
                                'label' => $this->l('Blue'),
                                'image' => Media::getMediaPath($this->_path.'views/img/paypal_login_blue.png'),
                            ],
                        ],
                    ],
                ],
                'submit'      => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get the main configuration page
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getMainConfigurationPage()
    {
        return $this->renderMainForm();
    }

    /**
     * @return bool
     */
    public function canBeUsed()
    {
        return true;
    }

    /**
     * Hooks methods
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookHeader()
    {
        // TODO: check if this module should be hooked
//        if (isset($this->context->cart) && $this->context->cart->id) {
//            $this->context->smarty->assign('id_cart', (int) $this->context->cart->id);
//        }
//
//        $smarty = $this->context->smarty;
//        $smarty->assign([
//            static::LIVE    => Configuration::get(static::LIVE),
//            'incontextType' => (Tools::getValue('controller') == 'product') ? 'product' : 'cart',
//            'paypal_locale' => $this->getLocale(),
//        ]);
//
//        $process = $this->display(__FILE__, 'views/templates/front/paypaljs.tpl');
//
//        if (((method_exists($smarty, 'getTemplateVars') && ($smarty->getTemplateVars('page_name') == 'authentication' || $smarty->getTemplateVars('page_name') == 'order-opc')) || (isset($smarty->_tpl_vars) && ($smarty->_tpl_vars['page_name'] == 'authentication' || $smarty->_tpl_vars['page_name'] == 'order-opc')))
//            && Configuration::get(static::LOGIN_ENABLED)
//        ) {
//            $this->context->smarty->assign([
//                'client_id'   => Configuration::get(static::CLIENT_ID),
//                'login_theme' => Configuration::get(static::LOGIN_THEME),
//                'live'        => Configuration::get(static::LIVE),
//                'return_link' => PayPalLogin::getReturnLink(),
//            ]);
//        }
//
//        return $process;
    }

    /**
     * Get PayPal locale
     *
     * @return string
     */
    public function getLocale()
    {
        return static::getLocaleByIso(Language::getIsoById($this->context->language->id));
    }

    /**
     * @param string $iso
     *
     * @return string
     *
     * @since 5.3.0
     */
    public static function getLocaleByIso($iso)
    {
        switch (strtolower($iso)) {
            case 'fr':
                return 'fr_FR';
            case 'hk':
                return 'zh_HK';
            case 'cn':
                return 'zh_CM';
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
                return 'en_US';
        }
    }

    /**
     * Hook to product actions
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookProductActions($params)
    {
        $type = isset($params['buttonType']) && in_array($params['buttonType'], static::$checkoutButtonTypes) ? $params['buttonType'] : 'product';
        if (!empty($params['product']) && Validate::isLoadedObject($params['product'])) {
            $product = $params['product'];
        } elseif (!empty($params['idProduct'])) {
            $product = new Product($params['idProduct']);
        } else {
            $product = new Product(Tools::getValue('id_product'));
        }
        if (!Validate::isLoadedObject($product)) {
            return '';
        }
        $idDefaultProductAttribute = $product->getDefaultIdProductAttribute();
        $attributes = $product->getAttributeCombinations($this->context->language->id);
        $minimalQuantities = [];
        if (!$idDefaultProductAttribute) {
            $minimalQuantities[] = $product->minimal_quantity;
        } else {
            foreach ($attributes as $attribute) {
                $minimalQuantities[] = $attribute['minimal_quantity'];
            }
        }
        $locale = static::getLocaleByIso($this->context->language->iso_code);
        if ($locale !== 'en_US') {
            $label = 'checkout';
            $allowed = [];
            $disallowed = ['paypal.FUNDING.CARD', 'paypal.FUNDING.ELV', 'paypal.FUNDING.CREDIT'];
        } elseif ($locale === 'de_DE') {
            $label = 'checkout';
            $disallowed = [];
            $allowed = [];
            if (Configuration::get(static::EXPRESS_CHECKOUT_SEPA)) {
                $allowed[] = 'paypal.FUNDING.ELV';
            } else {
                $disallowed[] = 'paypal.FUNDING.ELV';
            }
            if (Configuration::get(static::EXPRESS_CHECKOUT_CARDS)) {
                $allowed[] = 'paypal.FUNDING.CARD';
            } else {
                $disallowed[] = 'paypal.FUNDING.CARD';
            }
            $disallowed[] = 'paypal.FUNDING.CREDIT';
        } else {
            $label = null;
            $allowed = [];
            $disallowed = [];
            if (Configuration::get(static::EXPRESS_CHECKOUT_CARDS)) {
                $allowed[] = 'paypal.FUNDING.CARD';
            } else {
                $disallowed[] = 'paypal.FUNDING.CARD';
            }
            if (Configuration::get(static::EXPRESS_CHECKOUT_CREDIT)
                && strtolower($this->context->currency->iso_code) === 'usd'
            ) {
                $allowed[] = 'paypal.FUNDING.CREDIT';
            } else {
                $disallowed[] = 'paypal.FUNDING.CREDIT';
            }
        }

        $this->context->smarty->assign('hookOutput', $this->hookDisplayPayPalExpressCheckoutButton([
            'live'               => (bool) Configuration::get(static::LIVE),
            'locale'             => $locale,
            'buttonType'         => $type,
            'idProduct'          => $product->id,
            'idProductAttribute' => $idDefaultProductAttribute,
            'minimalQuantities'  => $minimalQuantities,
            'label'              => $label,
            'layout'             => empty($allowed) ? 'horizontal' : 'vertical',
            'size'               => 'responsive',
            'shape'              => Configuration::get(static::EXPRESS_CHECKOUT_SHAPE),
            'color'              => Configuration::get(static::EXPRESS_CHECKOUT_COLOR),
            'fundingAllowed'     => $allowed,
            'fundingDisallowed'  => $disallowed,
        ]));

        return $this->display(__FILE__, 'expresscheckout/product_actions.tpl');
    }

    /**
     * Product Footer hook
     *
     * @param array $params
     *
     * @return string Hook HTML
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookProductFooter($params)
    {
        $type = isset($params['buttonType']) && in_array($params['buttonType'], static::$checkoutButtonTypes) ? $params['buttonType'] : 'product';
        if (!empty($params['product']) && Validate::isLoadedObject($params['product'])) {
            $product = $params['product'];
        } elseif (!empty($params['idProduct'])) {
            $product = new Product($params['idProduct']);
        } else {
            $product = new Product(Tools::getValue('id_product'));
        }
        if (!Validate::isLoadedObject($product)) {
            return '';
        }
        $idDefaultProductAttribute = $product->getDefaultIdProductAttribute();
        $attributes = $product->getAttributeCombinations($this->context->language->id);
        $minimalQuantities = [];
        if (!$idDefaultProductAttribute) {
            $minimalQuantities[] = $product->minimal_quantity;
        } else {
            foreach ($attributes as $attribute) {
                $minimalQuantities[] = $attribute['minimal_quantity'];
            }
        }
//        $locale = static::getLocaleByIso($this->context->language->iso_code);
        $locale = 'nl_NL';
        if ($locale !== 'en_US') {
            $label = 'checkout';
            $allowed = [];
            $disallowed = ['paypal.FUNDING.CARD', 'paypal.FUNDING.ELV', 'paypal.FUNDING.CREDIT'];
        } elseif ($locale === 'de_DE') {
            $label = 'checkout';
            $disallowed = [];
            $allowed = [];
            if (Configuration::get(static::EXPRESS_CHECKOUT_SEPA)) {
                $allowed[] = 'paypal.FUNDING.ELV';
            } else {
                $disallowed[] = 'paypal.FUNDING.ELV';
            }
            if (Configuration::get(static::EXPRESS_CHECKOUT_CARDS)) {
                $allowed[] = 'paypal.FUNDING.CARD';
            } else {
                $disallowed[] = 'paypal.FUNDING.CARD';
            }
            $disallowed[] = 'paypal.FUNDING.CREDIT';
        } else {
            $label = null;
            $allowed = [];
            $disallowed = [];
            if (Configuration::get(static::EXPRESS_CHECKOUT_CARDS)) {
                $allowed[] = 'paypal.FUNDING.CARD';
            } else {
                $disallowed[] = 'paypal.FUNDING.CARD';
            }
            if (Configuration::get(static::EXPRESS_CHECKOUT_CREDIT)
                && strtolower($this->context->currency->iso_code) === 'usd'
            ) {
                $allowed[] = 'paypal.FUNDING.CREDIT';
            } else {
                $disallowed[] = 'paypal.FUNDING.CREDIT';
            }
        }

        return $this->hookDisplayPayPalExpressCheckoutButton([
            'live'               => (bool) Configuration::get(static::LIVE),
            'locale'             => $locale,
            'buttonType'         => $type,
            'idProduct'          => $product->id,
            'idProductAttribute' => $idDefaultProductAttribute,
            'minimalQuantities'  => $minimalQuantities,
            'label'              => $label,
            'layout'             => empty($allowed) ? 'horizontal' : 'vertical',
            'size'               => 'large',
            'shape'              => Configuration::get(static::EXPRESS_CHECKOUT_SHAPE),
            'color'              => Configuration::get(static::EXPRESS_CHECKOUT_COLOR),
            'fundingAllowed'     => $allowed,
            'fundingDisallowed'  => $disallowed,
        ]);
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPayPalExpressCheckoutButton($params)
    {
        $function = __FUNCTION__;
        if (!is_array($params)) {
            Logger::addLog("PayPal module warning: called function `PayPal::$function`, but forgot to pass the parameters", 2);
            return '';
        }
        $diff = array_diff([
            'buttonType',
            'idProduct',
            'idProductAttribute',
            'minimalQuantities',
            'label',
            'layout',
            'size',
            'shape',
            'color'
        ], array_keys($params));
        if (!empty($diff)) {
            $diffString = implode(',', $diff);
            Logger::addLog("PayPal module warning: called function `PayPal::$function`, but the parameters: `$diffString` were missing", 2);
            return '';
        }

        $type = $params['buttonType'];
        $idProduct = $params['idProduct'];
        $idProductAttribute = $params['idProductAttribute'];
        $minimalQuantities = $params['minimalQuantities'];

        $type = strtolower($type);
        if (!in_array($type, static::$checkoutButtonTypes)) {
            Logger::addLog("PayPal module warning: tried to render the checkout button for non-existent type `{$type}`", 2);
            return '';
        }
        $idProductAttribute = (int) ($idProductAttribute ?: Product::getDefaultAttribute($idProduct));
        if (empty($minimalQuantities)) {
            $minimalQuantities = $idProductAttribute
                ? Attribute::getAttributeMinimalQty($idProductAttribute)
                : (new Product($idProduct))->minimal_quantity;
        }

        if (!isset($params['fundingAllowed'])) {
            $params['fundingAllowed'] = [];
        }
        if (!isset($params['fundingDisallowed'])) {
            $params['fundingDisallowed'] = ['paypal.FUNDING.CARD', 'paypal.FUNDING.CREDIT'];
        }
        if (!isset($params['live'])) {
            $params['live'] = (bool) Configuration::get(static::LIVE);
        }
        if (!isset($params['locale'])) {
            $params['locale'] = static::getLocale();
        }
        if (!isset($params['shape'])) {
            $params['shape'] = Configuration::get(static::EXPRESS_CHECKOUT_SHAPE);
        }
        if (!in_array($params['shape'], [
            static::EXPRESS_CHECKOUT_SHAPE_RECT,
            static::EXPRESS_CHECKOUT_SHAPE_PILL,
        ])) {
            $params['shape'] = static::EXPRESS_CHECKOUT_SHAPE_RECT;
        }
        if (!isset($params['color'])) {
            $params['color'] = Configuration::get(static::EXPRESS_CHECKOUT_COLOR);
        }
        if (!in_array($params['color'], [
            static::EXPRESS_CHECKOUT_COLOR_GOLD,
            static::EXPRESS_CHECKOUT_COLOR_BLUE,
            static::EXPRESS_CHECKOUT_COLOR_SILVER,
            static::EXPRESS_CHECKOUT_COLOR_BLACK,
        ])) {
            $params['color'] = static::EXPRESS_CHECKOUT_COLOR_GOLD;
        }

        $this->context->smarty->assign([
            'live'                   => $params['live'],
            'locale'                 => $params['locale'],
            'clientId'               => Configuration::get(static::CLIENT_ID),
            'currentPage'            => $this->getCurrentUrl(),
            'idProduct'              => (int) $idProduct,
            'idProductAttribute'     => (int) $idProductAttribute,
            'idProductPath'          => !empty($params['idProductPath']) ? $params['idProductPath'] : '',
            'idProductAttributePath' => !empty($params['idProductAttributePath']) ? $params['idProductAttributePath'] : '',
            'minimalQuantities'      => json_encode($minimalQuantities),
            'idButton'               => Tools::passwdGen(8),
            'label'                  => $params['label'],
            'layout'                 => $params['layout'],
            'size'                   => $params['size'],
            'shape'                  => $params['shape'],
            'color'                  => $params['color'],
            'fundingAllowed'         => $params['fundingAllowed'],
            'fundingDisallowed'      => $params['fundingDisallowed'],
        ]);

        return (string) $this->display(__FILE__, "expresscheckout/{$type}.tpl");
    }

    /**
     * Get current URL
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCurrentUrl()
    {
        $protocolLink = Tools::usingSecureMode() ? 'https://' : 'http://';
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');

        if (($pos !== false) && ($pos >= 0)) {
            $request = substr($request, 0, $pos);
        }

        $params = urlencode($_SERVER['QUERY_STRING']);

        return $protocolLink.Tools::getShopDomainSsl().$request.'?'.$params;
    }

    /**
     * DisplayPayment Hook
     *
     * @param array $params
     *
     * @return null|string
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment($params)
    {
        $this->context->smarty->assign(
            [
                static::LIVE       => Configuration::get(static::LIVE),
                'use_mobile'       => true,
                'PayPal_lang_code' => $this->getLocale(),
                'params'           => $params,
            ]
        );

        $output = '';
        if (Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            $output .= $this->display(__FILE__, 'express_checkout_payment.tpl');
        }

        if (Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED)) {
            $rest = PayPalRestApi::getInstance();
            try {
                $payment = $rest->createPayment(
                    $this->context->link->getModuleLink($this->name, 'expresscheckoutconfirm', [], true),
                    $this->context->link->getModuleLink($this->name, 'pluscancel', [], true),
                    PayPalRestApi::PLUS_PROFILE
                );
            } catch (Adapter_Exception $e) {
            } catch (PaymentException $e) {
            } catch (PrestaShopDatabaseException $e) {
            } catch (PrestaShopException $e) {
            }

            $approvalUrl = '';
            if (!empty($payment['id'])) {
                foreach ($payment['links'] as $link) {
                    if ($link['rel'] === 'approval_url') {
                        $approvalUrl = $link['href'];
                        break;
                    }
                }
            }

            if ($approvalUrl) {
                $this->context->smarty->assign(
                    [
                        'approval_url' => $approvalUrl,
                        'mode'         => Configuration::get(static::LIVE) ? 'live' : 'sandbox',
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

    /**
     * Get PayPal Plus locale
     *
     * @return string
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getLocalePayPalPlus()
    {
        switch (strtolower($this->getCountryCode())) {
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
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCountryCode()
    {
        $cart = new Cart((int) $this->context->cookie->id_cart);
        $address = new Address((int) $cart->id_address_invoice);
        $country = new Country((int) $address->id_country);

        return $country->iso_code;
    }

    /**
     * Hook for Advanced EU checkout
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU()
    {
        if (!$this->active) {
            return null;
        }

        $paymentOptions = [];
        if (Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            $paymentOptions[] = [
                'cta_text' => $this->l('PayPal or credit card'),
                'logo'     => Media::getMediaPath($this->_path.'views/img/default_logos/default_horizontal_large.png'),
                'action'   => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
            ];
        }
        if (Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED)) {
            $paymentOptions[] = [
                'cta_text' => $this->l('PayPal or credit card'),
                'logo'     => Media::getMediaPath($this->_path.'views/img/default_logos/default_horizontal_large.png'),
                'action'   => $this->context->link->getModuleLink($this->name, 'pluseu', [], true),
            ];
        }

        return $paymentOptions;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookShoppingCartExtra()
    {
        if (!$this->active
            || !Configuration::get(static::EXPRESS_CHECKOUT_ENABLED)
        ) {
            return '';
        }

        $this->context->smarty->assign([
            'PayPal_payment_type'                   => 'cart',
            'PayPal_current_page'                   => $this->getCurrentUrl(),
            'PayPal_lang_code'                      => $this->context->language->iso_code ? $this->context->language->iso_code : 'en_US',
            'PayPal_tracking_code'                  => '',
            'include_form'                          => true,
            'template_dir'                          => dirname(__FILE__).'/views/templates/hook/',
            'express_checkout_payment_link'         => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
        ]);

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * Payment return hook (confirmation page content)
     *
     * @param array $params Hook parameters
     *
     * @return string
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active || !isset($params['objOrder']) || !$params['objOrder'] instanceof Order) {
            return '';
        }

        /** @var Order $order */
        $order = $params['objOrder'];

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

        $params = [
            'id_order'  => $order->id,
            'reference' => $reference,
            'params'    => $params,
            'total'     => Tools::displayPrice($totalToPay, $currency, false),
        ];

        // Add the PayPal order details to the template
        $paypalOrder = PayPalOrder::getByOrderId($order->id);
        foreach ([
                     'id_transaction',
                     'id_payment',
                     'id_payer',
                     'id_invoice',
                     'currency',
                     'total_paid',
                     'shipping',
                     'capture',
                     'payment_date',
                     'payment_method',
                     'payment_status',
                 ] as $property) {
            if (isset($paypalOrder[$property])) {
                $params[$property] = $paypalOrder[$property];
            } else {
                $params[$property] = '';
            }
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
     * @return bool Indicates whether the PayPal API is available
     * @throws PrestaShopException
     */
    public function isPayPalAPIAvailable()
    {
        return Configuration::get(static::CLIENT_ID) && Configuration::get(static::SECRET);
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws \Predis\ClientException
     */
    public function hookAdminOrder($params)
    {
        if (Tools::isSubmit('submitPayPalCapture')) {
            if ($captureAmount = Tools::getValue('totalCaptureMoney')) {
                if ($captureAmount = PayPalCapture::parsePrice($captureAmount)) {
                    if (\Validate::isFloat($captureAmount)) {
                        $captureAmount = Tools::ps_round($captureAmount, '6');
                        $ord = new \Order((int) $params['id_order']);
                        $cpt = new PayPalCapture();

                        if (($captureAmount > Tools::ps_round(0, '6')) && (Tools::ps_round($cpt->getRestToPaid($ord), '6') >= $captureAmount)) {
                            $complete = false;

                            if ($captureAmount > Tools::ps_round((float) $ord->total_paid, '6')) {
                                $captureAmount = Tools::ps_round((float) $ord->total_paid, '6');
                                $complete = true;
                            }
                            if ($captureAmount == Tools::ps_round($cpt->getRestToPaid($ord), '6')) {
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
//        elseif (Tools::isSubmit('submitPayPalRefund')) {
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
                'authorization'   => (int) Configuration::get('PAYPAL_OS_AUTHORIZATION'),
                'base_url'        => _PS_BASE_URL_.__PS_BASE_URI__,
                'module_name'     => $this->name,
                'order_state'     => $orderState,
                'params'          => $params,
                'id_currency'     => $currency->getSign(),
                'rest_to_capture' => Tools::ps_round($cpt->getRestToPaid($order), '6'),
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
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function needsValidation($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $order = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('po.`payment_method`, po.`payment_status`')
                ->from('paypal_order', 'po')
                ->where('po.`id_order` = '.(int) $idOrder)
        );

        return $order && $order['payment_status'] === 'Pending_validation';
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function needsCapture($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }
        $result = Db::getInstance()->getRow(
            (new DbQuery())
                ->select('po.`payment_method`, po.`payment_status`')
                ->from(bqSQL(PayPalOrder::$definition['table']), 'po')
                ->where('po.`'.bqSQL(Order::$definition['table']).'` = '.(int) $idOrder)
                ->where('po.`capture` = 1')
        );

        return $result && $result['payment_status'] == 'Pending_capture';
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function canRefund($idOrder)
    {
        if (!(bool) $idOrder) {
            return false;
        }

        $paypalOrder = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('po.`payment_status`, po.`capture`')
                ->from(bqSQL(PayPalOrder::$definition['table']), 'po')
                ->where('po.`'.bqSQL(Order::$definition['table']).'` = '.(int) $idOrder)
        );

        return $paypalOrder && ($paypalOrder['payment_status'] == 'Completed'
                || $paypalOrder['payment_status'] == 'approved') && $paypalOrder['capture'] == 0;
    }

    /**
     * @param array $params
     *
     * @return bool|null
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookCancelProduct($params)
    {
        /** @var \Order $order */
        if (Tools::isSubmit('generateDiscount')
            || !$this->isPayPalAPIAvailable()
            || Tools::isSubmit('generateCreditSlip')
        ) {
            return false;
        } elseif ($params['order']->module != $this->name || !($order = $params['order'])
            || !Validate::isLoadedObject($order)
        ) {
            return false;
        } elseif (!$order->hasBeenPaid()) {
            return false;
        }

        $orderDetail = new OrderDetail((int) $params['id_order_detail']);
        if (!$orderDetail || !Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $paypalOrder = PayPalOrder::getByOrderId((int) $order->id);
        if (!Validate::isLoadedObject($paypalOrder)) {
            return false;
        }

        $products = $order->getProducts();
        $cancelQuantity = Tools::getValue('cancelQuantity');
        $message = $this->l('Cancel products result:').'<br>';

        $amount = (float) ($products[(int) $orderDetail->id]['product_price_wt'] * (int) $cancelQuantity[(int) $orderDetail->id]);
        $refund = $this->doRefund($paypalOrder->id_payment, $order, $amount);

        return null;
    }

    /**
     * @param string     $idPayment
     * @param \Order     $order
     * @param bool|float $amount Amount
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function doRefund($idPayment, $order, $amount = false)
    {
        if (!$amount) {
            return $this->doFullRefund($idPayment);
        }

        // TODO: check if succeeded
        $rest = PayPalRestApi::getInstance();
        if ($rest->executeRefund($idPayment, [
            'amount'   => (float) $amount,
            'currency' => strtoupper(Currency::getCurrencyInstance($order->id_currency)->iso_code),
        ])) {
            return true;
        }

        return false;
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
     * @return null|string
     */
    public function hookBackOfficeHeader()
    {
        if ((strcmp(Tools::getValue('configure'), $this->name) === 0) ||
            (strcmp(Tools::getValue('module_name'), $this->name) === 0)
        ) {
            $this->context->controller->addJquery();
            $this->context->controller->addJQueryPlugin('fancybox');

            $this->context->smarty->assign(
                [
                    'PayPal_module_dir' => _MODULE_DIR_.$this->name,
                    'PayPal_WPS'        => (int) static::WPS,
                    'PayPal_ECS'        => (int) static::EC,
                    'PayPal_PPP'        => (int) static::WPP,
                ]
            );
        }

        return null;
    }

    /**
     * Validate the order
     *
     * @param int       $idCart
     * @param int       $idOrderState
     * @param float     $amountPaid
     * @param string    $paymentMethod
     * @param null      $message
     * @param array     $transaction
     * @param null      $currencySpecial
     * @param bool      $dontTouchAmount
     * @param bool      $secureKey
     * @param Shop|null $shop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function validateOrder(
        $idCart,
        $idOrderState,
        $amountPaid,
        $paymentMethod = 'PayPal',
        $message = null,
        $transaction = [],
        $currencySpecial = null,
        $dontTouchAmount = false,
        $secureKey = false,
        Shop $shop = null
    )
    {
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
     * Checks if the address can be passed to PayPal
     *
     * @param Address $address
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function checkAddress($address)
    {
        return !in_array(Country::getIsoById($address->id_country), static::$postalCodeRequired) || $address->postcode;
    }

    /**
     * Check webhooks + update info
     *
     * @param bool $force
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function checkWebhooks($force = false)
    {
        $lastCheck = (int) Configuration::get(static::WEBHOOK_LAST_CHECK, null, 0, 0);
        $webHookId = Configuration::get(static::WEBHOOK_ID, null, 0, 0);

        if (time() > $lastCheck + static::WEBHOOK_CHECK_INTERVAL || !$webHookId || $force) {
            // Time to update/check webhooks
            $rest = PayPalRestApi::getInstance();
            try {
                $data = $rest->getWebhooks();
            } catch (TokenException $e) {
                return;
            } catch (PrestaShopException $e) {
                return;
            }

            /** @var array $data */
            if ($data) {
                $found = false;
                $sslEnabled = (bool) Configuration::get('PS_SSL_ENABLED');
                $webhookUrl = Context::getContext()->link->getModuleLink(
                    $this->name,
                    'hook',
                    [],
                    $sslEnabled,
                    (int) Configuration::get('PS_LANG_DEFAULT'),
                    (int) Configuration::get('PS_SHOP_DEFAULT')
                );
                if (!empty($data['webhooks'])) {
                    foreach ($data['webhooks'] as $webhook) {
                        if ($webhook['url'] === $webhookUrl) {
                            $found = true;
                            Configuration::updateValue(static::WEBHOOK_ID, $webhook['id'], null, 0, 0);

                            break;
                        }
                    }
                }

                if (!$found) {
                    $rest = PayPalRestApi::getInstance();
                    $registration = $rest->registerWebhook($webhookUrl);

                    if (isset($registration['id'])) {
                        Configuration::updateValue(self::WEBHOOK_ID, $registration['id'], null, 0, 0);
                    }
                }
            }

            Configuration::updateValue(static::WEBHOOK_LAST_CHECK, time(), null, 0, 0);
        }
    }
}
