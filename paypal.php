<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

use PayPalModule\PayPalCapture;
use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalLogin;
use PayPalModule\PayPalLoginUser;
use PayPalModule\PayPalLogos;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalRestApi;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PayPal
 */
class PayPal extends PaymentModule
{
    const INSTALLATION_ID = 'PAYPAL_INSTALLATION_ID';
    const LIVE = 'PAYPAL_LIVE';
    const IMMEDIATE_CAPTURE = 'PAYPAL_CAPTURE';
    const STORE_COUNTRY = 'PAYPAL_COUNTRY_DEFAULT';
    const ACCESS_TOKENS = 'PAYPAL_ATS';
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

    const _PAYPAL_LOGO_XML_ = 'logos.xml';
    const _PAYPAL_MODULE_DIRNAME_ = 'paypal';

    const WEBSITE_PAYMENTS_STANDARD_ENABLED = 'PAYPAL_WPS_ENABLED';
    const WEBSITE_PAYMENTS_PLUS_ENABLED = 'PAYPAL_WPP_ENABLED';
    const EXPRESS_CHECKOUT_ENABLED = 'PAYPAL_EC_ENABLED';
    const LOGIN_ENABLED = 'PAYPAL_LOGIN_ENABLED';
    const LOGIN_THEME = 'PAYPAL_LOGIN_TPL';

    const WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE = 'PAYPAL_WPS_LANDING_PAGE_TYPE';

    /**
     * PayPal constructor.
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '5.6.1';
        $this->author = 'thirty bees';

        $this->currencies = true;
        $this->currencies_mode = 'radio';
        $this->need_instance = false;
        $this->bootstrap = true;
        $this->is_eu_compatible = 1;

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
    }

    /**
     * Install the module
     *
     * @return bool Indicates whether the module was installed successfully
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('shoppingCartExtra')
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

        return true;
    }

    /**
     * Set configuration table
     *
     * @throws PrestaShopException
     */
    public function updateConfiguration()
    {
        Configuration::updateValue(static::LIVE, false);
        Configuration::updateValue(static::IMMEDIATE_CAPTURE, true);
    }

    /**
     * Create a new order state
     *
     * @throws PrestaShopException
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function createOrderState()
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
                $source = dirname(__FILE__).'/../../img/os/'. Configuration::get('PS_OS_PAYPAL').'.gif';
                $destination = dirname(__FILE__).'/../../img/os/'.(int) $orderState->id.'.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('PAYPAL_OS_AUTHORIZATION', (int) $orderState->id);
        }
    }

    /**
     * Uninstall the module
     *
     * @return bool Indicates whether the module was uninstalled successfully
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
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function deleteConfiguration()
    {
        Configuration::deleteByName(static::INSTALLATION_ID);
        Configuration::deleteByName(static::LIVE);

        Configuration::deleteByName(static::IMMEDIATE_CAPTURE);
        Configuration::deleteByName(static::STORE_COUNTRY);

        Configuration::deleteByName(static::LOGIN_ENABLED);
        Configuration::deleteByName(static::CLIENT_ID);
        Configuration::deleteByName(static::SECRET);
        Configuration::deleteByName(static::LOGIN_THEME);
        Configuration::deleteByName(static::ACCESS_TOKENS);
    }

    /**
     * Get module configuration page
     *
     * @return string HTML
     * @throws GuzzleException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';

        $this->postProcess();

        $this->context->smarty->assign([
            'module_url' => $this->context->link->getAdminLink('AdminModules', true).'&'.http_build_query([
                'configure'   => $this->name,
                'tab_module'  => $this->tab,
                'module_name' => $this->name
            ])
        ]);

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output.$this->renderMainForm();
    }

    /**
     * Post process
     *
     * @throws GuzzleException
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            // General
            Configuration::updateValue(static::STORE_COUNTRY, (int) Tools::getValue(static::STORE_COUNTRY));
            Configuration::updateValue(static::LIVE, (int) Tools::getValue(static::LIVE));
            Configuration::updateValue(static::IMMEDIATE_CAPTURE, (int) Tools::getValue(static::IMMEDIATE_CAPTURE));

            // REST API
            Configuration::updateValue(static::CLIENT_ID, Tools::getValue(static::CLIENT_ID));
            Configuration::updateValue(static::SECRET, Tools::getValue(static::SECRET));

            // Website Payments Standard
            Configuration::updateValue(static::WEBSITE_PAYMENTS_STANDARD_ENABLED, Tools::getValue(static::WEBSITE_PAYMENTS_STANDARD_ENABLED));
            Configuration::updateValue(static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE, Tools::getValue(static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE));

            // Website Payments Plus
            Configuration::updateValue(static::WEBSITE_PAYMENTS_PLUS_ENABLED, Tools::getValue(static::WEBSITE_PAYMENTS_PLUS_ENABLED));

            // Express Checkout
            Configuration::updateValue(static::EXPRESS_CHECKOUT_ENABLED, Tools::getValue(static::EXPRESS_CHECKOUT_ENABLED));

            // PayPal Login
            Configuration::updateValue(static::LOGIN_ENABLED, (int) Tools::getValue(static::LOGIN_ENABLED));
            Configuration::updateValue(static::LOGIN_THEME, (int) Tools::getValue(static::LOGIN_THEME));

            // Create/update needed payment profiles via REST API
            if (Tools::getValue(static::CLIENT_ID) && Tools::getValue(static::SECRET)) {
                $rest = new PayPalRestApi(Tools::getValue(static::CLIENT_ID), Tools::getValue(static::SECRET));
                $rest->updateWebProfile(PayPalRestApi::STANDARD_PROFILE, Tools::getValue(static::WEBSITE_PAYMENTS_STANDARD_ENABLED));
                $rest->updateWebProfile(PayPalRestApi::PLUS_PROFILE, Tools::getValue(static::WEBSITE_PAYMENTS_PLUS_ENABLED));
                $rest->updateWebProfile(PayPalRestApi::EXPRESS_CHECKOUT_PROFILE, Tools::getValue(static::EXPRESS_CHECKOUT_ENABLED));
            }
        }
    }

    /**
     * Render the main form
     *
     * @return string
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

        /** @var AdminController $controller */
        $controller = $this->context->controller;

        $helper->tpl_vars = [
            'fields_value' => $this->getMainFormValues(),
            'languages'    => $controller->getLanguages(),
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
            static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE => Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE),

            static::WEBSITE_PAYMENTS_PLUS_ENABLED     => Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED),
            static::EXPRESS_CHECKOUT_ENABLED          => Configuration::get(static::EXPRESS_CHECKOUT_ENABLED),
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
     * @throws PrestaShopException
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
                        'name'     => static::STORE_COUNTRY,
                        'required' => true,
                        'options'  => [
                            'query' => Country::getCountries($this->context->language->id),
                            'id'    => 'id_country',
                            'name'  => 'name',
                        ],
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Go Live'),
                        'name'   => static::LIVE,
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
                    'standardProfile'              => $standardProfile,
                    'standardProfileNeeded'        => Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED),
                    'plusProfile'                  => $plusProfile,
                    'plusProfileNeeded'            => Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED),
                    'expressCheckoutProfile'       => $expressCheckoutProfile,
                    'expressCheckoutProfileNeeded' => Configuration::get(static::EXPRESS_CHECKOUT_ENABLED),
                ]
            );
        }

        if (($standardProfile || !Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED)) &&
            ($plusProfile || !Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED)) &&
            ($expressCheckoutProfile || !Configuration::get(static::EXPRESS_CHECKOUT_ENABLED))) {
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
                    'icon'  => 'icon-credit-card',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Website Payments Standard'),
                        'name'   => static::WEBSITE_PAYMENTS_STANDARD_ENABLED,
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
                        'type'     => 'select',
                        'label'    => $this->l('Landing Page Type'),
                        'name'     => static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE,
                        'desc'     => $this->l('"Billing" allows credit card payment without a PayPal account, while selecing "Login" requires the customer having a PayPal account. Creating a PayPal account during the payment process is possible with both modes.'),
                        'options'  => [
                            'query' => [
                                [
                                    'id' => 'billing',
                                    'value' => 'billing',
                                    'name' => $this->l('Billing'),
                                ],
                                [
                                    'id' => 'login',
                                    'value' => 'login',
                                    'name' => $this->l('Login'),
                                ],
                            ],
                            'id'    => 'id',
                            'name'  => 'name',
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
     * Get the Website Payments Plus form structure
     *
     * @return array
     */
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
                        'name'   => static::WEBSITE_PAYMENTS_PLUS_ENABLED,
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
                    'icon'  => 'icon-credit-card',
                ],
                'input'  => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable Express Checkout'),
                        'name'   => static::EXPRESS_CHECKOUT_ENABLED,
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
                'description' => $this->l('Use the following link for this store (repeat if you have multiple stores!) as the `redirect_uri` setting:').' '.PayPalLogin::getReturnLink(),
                'input'       => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Enable PayPal Login'),
                        'name'   => static::LOGIN_ENABLED,
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
                        'name'    => static::LOGIN_THEME,
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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getMainConfigurationPage()
    {
        return $this->renderMainForm();
    }

    /**
     * Hooks methods
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookHeader()
    {
        // TODO: check if this module should be hooked
        if (isset($this->context->cart) && $this->context->cart->id) {
            $this->context->smarty->assign('id_cart', (int) $this->context->cart->id);
        }

        $this->context->controller->addCSS($this->_path.'/views/css/paypal.css');

        $smarty = $this->context->smarty;
        $smarty->assign(
            [
                static::LIVE    => Configuration::get(static::LIVE),
                'incontextType' => (Tools::getValue('controller') == 'product') ? 'product' : 'cart',
                'paypal_locale' => $this->getLocale(),
            ]
        );

        $process = '';

        if (Configuration::get(static::LOGIN_ENABLED) || Configuration::get(static::EXPRESS_CHECKOUT_ENABLED)) {
            $process .= $this->display(__FILE__, 'views/templates/front/paypaljs.tpl');
            $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/api/checkout.js"></script>';
        }

        if ((
                (method_exists($smarty, 'getTemplateVars') && ($smarty->getTemplateVars('page_name')
                        == 'authentication' || $smarty->getTemplateVars('page_name') == 'order-opc'))
                || (isset($smarty->_tpl_vars) && ($smarty->_tpl_vars['page_name']
                        == 'authentication' || $smarty->_tpl_vars['page_name'] == 'order-opc')))
            && Configuration::get(static::LOGIN_ENABLED)
        ) {
            $this->context->smarty->assign(
                [
                    'client_id'     => Configuration::get(static::CLIENT_ID),
                    'login_theme'   => Configuration::get(static::LOGIN_THEME),
                    'live'          => Configuration::get(static::LIVE),
                    'return_link'   => PayPalLogin::getReturnLink(),
                ]
            );

            $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/js/external/api.js"></script>';
            $process .= '<script async defer type="text/javascript" src="'. Media::getJSPath($this->_path.'views/js/login.js').'"></script>';
            $process .= $this->display(__FILE__, 'views/templates/front/paypal_loginjs.tpl');
        }

        return $process;
    }

    /**
     * Get PayPal locale
     *
     * @return string
     * @throws PrestaShopException
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
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookProductActions()
    {
        return $this->hookProductFooter();
    }

    /**
     * Product Footer hook
     *
     * @return string Hook HTML
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookProductFooter()
    {
        if (!Configuration::get(static::EXPRESS_CHECKOUT_ENABLED)) {
            return '';
        }

        return $this->renderExpressCheckoutForm('product');
    }

    /**
     * @param string $type
     *
     * @return null|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderExpressCheckoutForm($type)
    {
        $idProduct = (int) Tools::getValue('id_product');
        $idProductAttribute = (int) Product::getDefaultAttribute($idProduct);
        if ($idProductAttribute) {
            $combination = new Combination($idProductAttribute);
            $minimalQuantity = $combination->minimal_quantity;
        } else {
            $product = new Product($idProduct);
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

        return $protocolLink. Tools::getShopDomainSsl().$request.'?'.$params;
    }

    /**
     * DisplayPayment Hook
     *
     * @param array $params
     *
     * @return null|string
     * @throws GuzzleException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment($params)
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
                static::LIVE       => Configuration::get(static::LIVE),
                'use_mobile'       => true,
                'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code])) ? $isoLang[$this->context->language->iso_code] : 'en_US',
                'params'           => $params,
                'landing_page'     => Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE)
            ]
        );

        $output = '';
        if (Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            $output .= $this->display(__FILE__, 'express_checkout_payment.tpl');
        }

        if (Configuration::get(static::WEBSITE_PAYMENTS_PLUS_ENABLED)) {
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
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU()
    {
        if (!$this->active) {
            return null;
        }

        $paymentOptions = [];

        if (Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_ENABLED)) {
            if (Configuration::get(static::WEBSITE_PAYMENTS_STANDARD_LANDING_PAGE_TYPE) == 'login') {
                $paymentOptions[] = [
                    'cta_text' => $this->l('PayPal'),
                    'logo'     => Media::getMediaPath($this->_path.'logo.png'),
                    'action'   => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
                ];
            }
            else {
                $paymentOptions[] = [
                    'cta_text' => $this->l('PayPal or credit card'),
                    'logo'     => Media::getMediaPath($this->_path.'views/img/default_logos/default_horizontal_large.png'),
                    'action'   => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
                ];
            }
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
     * @return null|string
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws GuzzleException
     */
    public function hookShoppingCartExtra()
    {
        if (!$this->active
            || !Configuration::get(static::EXPRESS_CHECKOUT_ENABLED)
        ) {
            return null;
        }

        $paypalLogos = PayPalLogos::getLogos($this->getLocale());

        $this->context->smarty->assign(
            [
                'PayPal_payment_type'                   => 'cart',
                'paypal_express_checkout_shortcut_logo' => $paypalLogos['ExpressCheckoutShortcutButton'] ?? false,
                'PayPal_current_page'                   => $this->getCurrentUrl(),
                'PayPal_lang_code'                      => $this->context->language->iso_code ? $this->context->language->iso_code : 'en_US',
                'PayPal_tracking_code'                  => '',
                'include_form'                          => true,
                'template_dir'                          => dirname(__FILE__).'/views/templates/hook/',
                'express_checkout_payment_link'         => $this->context->link->getModuleLink($this->name, 'expresscheckout', [], true),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * @param array $params
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active || !isset($params['objOrder']) || !$params['objOrder'] instanceof Order) {
            return '';
        }

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
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws GuzzleException
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @return string
     * @throws GuzzleException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookRightColumn()
    {
        $this->context->smarty->assign('logo', PayPalLogos::getCardsLogo($this->getLocale(), true));

        return $this->display(__FILE__, 'column.tpl');
    }

    /**
     * @return bool Indicates whether the PayPal API is available
     * @throws PrestaShopException
     */
    public function isPayPalAPIAvailable()
    {
        if (Configuration::get(static::CLIENT_ID) && Configuration::get(static::SECRET)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws GuzzleException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookAdminOrder($params)
    {
        $orderId = (int)$params['id_order'];

        if (Tools::isSubmit('submitPayPalCapture')) {
            if ($captureAmount = Tools::getValue('totalCaptureMoney')) {
                if ($captureAmount = PayPalCapture::parsePrice($captureAmount)) {
                    if (Validate::isFloat($captureAmount)) {
                        $captureAmount = Tools::ps_round($captureAmount, '6');
                        $ord = new Order($orderId);
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

                            $this->doCapture($orderId, $captureAmount, $complete);
                        }
                    }
                }
            }
        }


        $adminTemplates = [];
        if ($this->isPayPalAPIAvailable()) {
            if ($this->needsValidation($orderId)) {
                $adminTemplates[] = 'validation';
            }

            if ($this->needsCapture($orderId)) {
                $adminTemplates[] = 'capture';
            }

            if ($this->canRefund($orderId)) {
                $adminTemplates[] = 'refund';
            }

        }


        if (count($adminTemplates) > 0) {
            $order = new Order($orderId);
            $currency = new Currency($order->id_currency);
            $cpt = new PayPalCapture();
            $cpt->id_order = (int) $order->id;
            $orderState = $order->current_state;

            $this->context->smarty->assign(
                [
                    'authorization'   => (int) Configuration::get('PAYPAL_OS_AUTHORIZATION'),
                    'base_url'        => _PS_BASE_URL_.__PS_BASE_URI__,
                    'module_name'     => $this->name,
                    'order_state'     => $orderState,
                    'params'          => $params,
                    'id_currency'     => $currency->getSign(),
                    'rest_to_capture' => Tools::ps_round($cpt->getRestToPaid($order), '6'),
                    'list_captures'   => $cpt->getListCaptured(),
                    'ps_version'      => _PS_VERSION_,
                ]
            );

            $html = '';
            foreach ($adminTemplates as $adminTemplate) {
                $html .= $this->display(__FILE__, 'views/templates/admin/admin_order/'.$adminTemplate.'.tpl');
                $this->postProcess();
                $html .= '</fieldset>';
            }
            return $html;
        }

        return null;
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function needsValidation($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('po.`payment_method`, po.`payment_status`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.(int) $idOrder);

        $order = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return $order && $order['payment_status']
            == 'Pending_validation';
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function needsCapture($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('po.`payment_method`, po.`payment_status`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.(int) $idOrder);
        $sql->where('po.`capture` = 1');

        $result = Db::getInstance()->getRow($sql);

        return $result && $result['payment_status'] == 'Pending_capture';
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function canRefund($idOrder)
    {
        $idOrder = (int)$idOrder;
        if (!$idOrder) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('po.`payment_status`, po.`capture`');
        $sql->from('paypal_order', 'po');
        $sql->where('po.`id_order` = '.$idOrder);

        $paypalOrder = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return $paypalOrder && ($paypalOrder['payment_status'] == 'Completed' || $paypalOrder['payment_status'] == 'approved') && $paypalOrder['capture'] == 0;
    }

    /**
     * @param array $params
     *
     * @return bool|null
     * @throws GuzzleException
     * @throws PrestaShopException
     */
    public function hookCancelProduct($params)
    {
        /** @var Order $order */
        if (Tools::isSubmit('generateDiscount') || !$this->isPayPalAPIAvailable()
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
        if (!Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $paypalOrder = PayPalOrder::getOrderById((int) $order->id);
        if (!$paypalOrder) {
            return false;
        }

        $products = $order->getProducts();
        $cancelQuantity = Tools::getValue('cancelQuantity');

        $amount = (float) ($products[(int) $orderDetail->id]['product_price_wt'] * (int) $cancelQuantity[(int) $orderDetail->id]);
        $success = $this->doRefund($paypalOrder['id_payment'], $order, $amount);

        $message = $this->l('Cancel products result:').'<br>';
        $message .= $success ? $this->l('success') : $this->l('failed');
        $this->addNewPrivateMessage((int) $order->id, $message);

        return null;
    }

    /**
     * @param string $idPayment
     * @param Order $order
     * @param float $amount Amount
     *
     * @return bool
     * @throws GuzzleException
     * @throws PrestaShopException
     */
    protected function doRefund($idPayment, $order, $amount)
    {
        $details = new stdClass();
        $details->amount = (float) $amount;
        $details->currency = strtoupper(Currency::getCurrencyInstance($order->id_currency)->iso_code);

        // TODO: check if succeeded
        $rest = new PayPalRestApi();
        if ($rest->executeRefund($idPayment, $details)) {
            return true;
        }

        return false;
    }

    /**
     * Add new private message
     *
     * @param int $idOrder
     * @param string $message
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function addNewPrivateMessage($idOrder, $message)
    {
        $idOrder = (int)$idOrder;
        if (!$idOrder) {
            return false;
        }

        $newMessage = new Message();
        $message = strip_tags($message, '<br>');

        if (!Validate::isCleanHtml($message)) {
            $message = $this->l('Payment message is not valid, please check your module.');
        }

        $newMessage->message = $message;
        $newMessage->id_order = $idOrder;
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
        if ((strcmp(Tools::getValue('configure'), $this->name) === 0) ||
            (strcmp(Tools::getValue('module_name'), $this->name) === 0)
        ) {
            $this->context->controller->addJquery();
            $this->context->controller->addJQueryPlugin('fancybox');
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

            $this->context->smarty->assign(
                [
                    'PayPal_module_dir' => _MODULE_DIR_.$this->name,
                    'PayPal_WPS'        => static::WPS,
                    'PayPal_ECS'        => static::EC,
                    'PayPal_PPP'        => static::WPP,
                ]
            );

            //return (isset($output) ? $output : null).$this->display(__FILE__, 'views/templates/admin/header.tpl');
        }

        return null;
    }

    /**
     * Validate the order
     *
     * @param int $idCart
     * @param int $idOrderState
     * @param float $amountPaid
     * @param string $paymentMethod
     * @param string|null $message
     * @param array $transaction
     * @param int|null $currencySpecial
     * @param bool $dontTouchAmount
     * @param bool $secureKey
     * @param Shop|null $shop
     *
     * @return bool
     * @throws PrestaShopException
     * @throws SmartyException
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
    ) {
        if ($this->active) {
            // Set transaction details if pcc is defined in PaymentModule class_exists
            if (isset($this->pcc)) {
                $this->pcc->transaction_id = ($transaction['transaction_id'] ?? '');
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
     * Assign cart summary
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws GuzzleException
     */
    public function assignCartSummary()
    {
        $currency = new Currency((int) $this->context->cart->id_currency);

        $this->context->smarty->assign(
            [
                'total'            => Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
                'logos'            => PayPalLogos::getLogos($this->getLocale()),
                'use_mobile'       => (bool) $this->context->getMobileDevice(),
                'address_shipping' => new Address($this->context->cart->id_address_delivery),
                'address_billing'  => new Address($this->context->cart->id_address_invoice),
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
     */
    protected function doCapture($idOrder, $captureAmount = false, $isComplete = false)
    {
        // FIXME: not implemented
    }

    /**
     * @return bool|string|null
     * @throws PrestaShopException
     */
    public static function getInstallationId()
    {
        $id = Configuration::getGlobalValue(static::INSTALLATION_ID);
        if (! $id) {
            $id = Tools::passwdGen(8, 'ALPHANUMERIC');
            Configuration::updateGlobalValue(static::INSTALLATION_ID, $id);
        }
        return $id;
    }
}
