<?php
/**
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
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/autoload.php';

define('WPS', 1); //Paypal Integral
define('HSS', 2); //Paypal Integral Evolution
define('ECS', 4); //Paypal Option +
define('PPP', 5); //Paypal Plus

/* Tracking */
define('TRACKING_INTEGRAL_EVOLUTION', '');
define('TRACKING_INTEGRAL', '');
define('TRACKING_OPTION_PLUS', '');
define('TRACKING_PAYPAL_PLUS', '');
define('PAYPAL_HSS_REDIRECTION', 0);
define('PAYPAL_HSS_IFRAME', 1);
define('TRACKING_EXPRESS_CHECKOUT_SEAMLESS', '');

define('TRACKING_CODE', '');
define('SMARTPHONE_TRACKING_CODE', '');
define('TABLET_TRACKING_CODE', '');

/* Traking APAC */
define('APAC_TRACKING_INTEGRAL_EVOLUTION', '');
define('APAC_TRACKING_INTEGRAL', '');
define('APAC_TRACKING_OPTION_PLUS', '');
define('APAC_TRACKING_PAYPAL_PLUS', '');
define('APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS', '');

define('APAC_TRACKING_CODE', '');
define('APAC_SMARTPHONE_TRACKING_CODE', '');
define('APAC_TABLET_TRACKING_CODE', '');

define('_PAYPAL_LOGO_XML_', 'logos.xml');
define('_PAYPAL_MODULE_DIRNAME_', 'paypal');
define('_PAYPAL_TRANSLATIONS_XML_', 'translations.xml');

class PayPal extends PaymentModule
{
    /** @var string $html */
    protected $html = '';

    /** @var array $errors */
    public $errors = array();

    /** @var Context $context */
    public $context;

    /** @var string $iso_code */
    public $iso_code;

    public $defaultCountry;

    public $module_key = '336225a5988ad434b782f2d868d7bfcd';

    /** @var PayPalLogos $paypalLogos */
    public $paypalLogos;

    const BACKWARD_REQUIREMENT = '0.4';
    const ONLY_PRODUCTS = 1;
    const ONLY_DISCOUNTS = 2;
    const BOTH = 3;
    const BOTH_WITHOUT_SHIPPING = 4;
    const ONLY_SHIPPING = 5;
    const ONLY_WRAPPING = 6;
    const ONLY_PRODUCTS_WITHOUT_SHIPPING = 7;

    const SANDBOX = 'PAYPAL_SANDBOX';
    const HEADER = 'PAYPAL_HEADER';
    const BUSINESS = 'PAYPAL_BUSINESS';
    const BUSINESS_ACCOUNT = 'PAYPAL_BUSINESS_ACCOUNT';
    const API_USER = 'PAYPAL_API_USER';
    const API_PASSWORD = 'PAYPAL_API_PASSWORD';
    const API_SIGNATURE = 'PAYPAL_API_SIGNATURE';
    const EXPRESS_CHECKOUT = 'PAYPAL_EXPRESS_CHECKOUT';
    const CAPTURE = 'PAYPAL_CAPTURE';
    const PAYMENT_METHOD = 'PAYPAL_PAYMENT_METHOD';
    const IS_NEW = 'PAYPAL_NEW';
    const DEBUG_MODE = 'PAYPAL_DEBUG_MODE';
    const SHIPPING_COST = 'PAYPAL_SHIPPING_COST';
    const VERSION = 'PAYPAL_VERSION';
    const COUNTRY_DEFAULT = 'PAYPAL_COUNTRY_DEFAULT';
    const TEMPLATE = 'PAYPAL_TEMPLATE';

    const LOGIN = 'PAYPAL_LOGIN';
    const LOGIN_CLIENT_ID = 'PAYPAL_LOGIN_CLIENT_ID';
    const LOGIN_SECRET = 'PAYPAL_LOGIN_SECRET';
    const LOGIN_TPL = 'PAYPAL_LOGIN_TPL';
    const EXPRESS_CHECKOUT_SHORTCUT = 'PAYPAL_EXPRESS_CHECKOUT_SHORTCUT';

    const PLUS_CLIENT_ID = 'PAYPAL_PLUS_CLIENT_ID';
    const PLUS_SECRET = 'PAYPAL_PLUS_SECRET';

    const IN_CONTEXT_CHECKOUT = 'PAYPAL_IN_CONTEXT_CHECKOUT';
    const IN_CONTEXT_CHECKOUT_M_ID = 'PAYPAL_IN_CONTEXT_CHECKOUT_M_ID';

    const HSS_TEMPLATE = 'PAYPAL_HSS_TEMPLATE';
    const HSS_SOLUTION = 'PAYPAL_HSS_SOLUTION';
    const WEB_PROFILE_ID = 'PAYPAL_WEB_PROFILE_ID';
    const UPDATED_COUNTRIES_OK = 'PAYPAL_UPDATED_COUNTRIES_OK';
    const CONFIGURATION_OK = 'PAYPAL_CONFIGURATION_OK';

    /**
     * PayPal constructor.
     */
    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '4.0.0';
        $this->author = 'PrestaShop';
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayPal');
        $this->description = $this->l('Accepts payments by credit cards (CB, Visa, MasterCard, Amex, Aurore, Cofinoga, 4 stars) with PayPal.');

        $this->page = basename(__FILE__, '.php');

        $this->controllers = array(
            'confirm',
            'expresscheckoutajax',
            'expresscheckoutpayment',
            'expresscheckoutsubmit',
            'hostedsolutionconfirm',
            'hostedsolutionsubmit',
            'logintoken',
            'submit',
            'submitplus',
        );

        if (self::isInstalled($this->name)) {
            $this->loadDefaults();
        }
    }

    /**
     * Install the module
     *
     * @return bool Indicates whether the module was installed successfully
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn')
            ||
            !$this->registerHook('shoppingCartExtra') || !$this->registerHook('backBeforePayment')
            || !$this->registerHook('rightColumn') ||
            !$this->registerHook('cancelProduct') || !$this->registerHook('productFooter')
            || !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') || !$this->registerHook('backOfficeHeader')) {
            return false;
        }

        // Optional hooks
        $this->registerHook('displayPaymentEU');
        $this->registerHook('actionPSCleanerGetModulesTables');


        if (!(PayPalCapture::createDatabase()
            && PayPalCustomer::createDatabase()
            && PayPalLoginUser::createDatabase()
            && PayPalOrder::createDatabase())) {
            return false;
        }
        $this->updateConfiguration($this->version);
        $this->createOrderState();

        $paypalTools = new PayPalTools($this->name);
        $paypalTools->moveTopPayments(1);
        $paypalTools->moveRightColumn(3);

        return true;
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
     * Set configuration table
     */
    public function updateConfiguration($paypalVersion)
    {
        Configuration::updateValue(self::SANDBOX, 0);
        Configuration::updateValue(self::HEADER, '');
        Configuration::updateValue(self::BUSINESS, 0);
        Configuration::updateValue(self::BUSINESS_ACCOUNT, 'paypal@thirtybees.com');
        Configuration::updateValue(self::API_USER, '');
        Configuration::updateValue(self::API_PASSWORD, '');
        Configuration::updateValue(self::API_SIGNATURE, '');
        Configuration::updateValue(self::EXPRESS_CHECKOUT, 0);
        Configuration::updateValue(self::CAPTURE, 0);
        Configuration::updateValue(self::PAYMENT_METHOD, WPS);
        Configuration::updateValue(self::IS_NEW, 1);
        Configuration::updateValue(self::DEBUG_MODE, 0);
        Configuration::updateValue(self::SHIPPING_COST, 20.00);
        Configuration::updateValue(self::VERSION, $paypalVersion);
        Configuration::updateValue(self::COUNTRY_DEFAULT, (int) Configuration::get('PS_COUNTRY_DEFAULT'));

        // PayPal v3 configuration
        Configuration::updateValue('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT', 1);
        $paypal = new Paypal();
        $sslVerif = new TLSVerificator(true, $paypal);
        Configuration::updateValue('PAYPAL_VERSION_TLS_CHECKED', $sslVerif->getVersion());
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
        Configuration::deleteByName(self::SANDBOX);
        Configuration::deleteByName(self::HEADER);
        Configuration::deleteByName(self::BUSINESS);
        Configuration::deleteByName(self::API_USER);
        Configuration::deleteByName(self::API_PASSWORD);
        Configuration::deleteByName(self::API_SIGNATURE);
        Configuration::deleteByName(self::BUSINESS_ACCOUNT);
        Configuration::deleteByName(self::EXPRESS_CHECKOUT);
        Configuration::deleteByName(self::PAYMENT_METHOD);
        Configuration::deleteByName(self::TEMPLATE);
        Configuration::deleteByName(self::CAPTURE);
        Configuration::deleteByName(self::DEBUG_MODE);
        Configuration::deleteByName(self::COUNTRY_DEFAULT);
        Configuration::deleteByName(self::VERSION);

        /* USE PAYPAL LOGIN */
        Configuration::deleteByName(self::LOGIN);
        Configuration::deleteByName(self::LOGIN_CLIENT_ID);
        Configuration::deleteByName(self::LOGIN_SECRET);
        Configuration::deleteByName(self::LOGIN_TPL);
        /* /USE PAYPAL LOGIN */

        // PayPal v3 configuration
        Configuration::deleteByName(self::EXPRESS_CHECKOUT_SHORTCUT);
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
        if (!Configuration::get('PAYPAL_OS_AUTHORIZATION')) {
            $orderState = new OrderState();
            $orderState->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
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
            }
            Configuration::updateValue('PAYPAL_OS_AUTHORIZATION', (int) $orderState->id);
        }
    }

    private function compatibilityCheck()
    {
        if (file_exists(_PS_MODULE_DIR_.'paypalapi/paypalapi.php') && $this->active) {
            $this->warning = $this->l('All features of Paypal API module are included in the new Paypal module. In order to do not have any conflict, please do not use and remove PayPalAPI module.').'<br />';
        }

        /* For 1.4.3 and less compatibility */
        $updateConfig = array(
            'PS_OS_CHEQUE' => 1,
            'PS_OS_PAYMENT' => 2,
            'PS_OS_PREPARATION' => 3,
            'PS_OS_SHIPPING' => 4,
            'PS_OS_DELIVERED' => 5,
            'PS_OS_CANCELED' => 6,
            'PS_OS_REFUND' => 7,
            'PS_OS_ERROR' => 8,
            'PS_OS_OUTOFSTOCK' => 9,
            'PS_OS_BANKWIRE' => 10,
            'PS_OS_PAYPAL' => 11,
            'PS_OS_WS_PAYMENT' => 12
        );

        foreach ($updateConfig as $key => $value) {
            if (!Configuration::get($key) || (int) Configuration::get($key) < 1) {
                if (defined('_'.$key.'_') && (int) constant('_'.$key.'_')
                    > 0) {
                    Configuration::updateValue($key, constant('_'.$key.'_'));
                } else {
                    Configuration::updateValue($key, $value);
                }

            }
        }

    }

    public function isPayPalAPIAvailable()
    {
        $paymentMethod = Configuration::get(self::PAYMENT_METHOD);

        if (($paymentMethod == WPS || $paymentMethod == ECS) && (!is_null(Configuration::get('PAYPAL_API_USER'))
            && !is_null(Configuration::get('PAYPAL_API_PASSWORD')) && !is_null(Configuration::get('PAYPAL_API_SIGNATURE')))) {
            return true;
        }

        if ($paymentMethod == PPP && (!is_null(Configuration::get(self::PLUS_CLIENT_ID))
            || !is_null(Configuration::get(self::PLUS_SECRET)))) {
            return true;
        }

        if ($paymentMethod == HSS && !is_null(Configuration::get(self::BUSINESS_ACCOUNT))) {
            return true;
        }

        return false;
    }

    /**
     * Initialize default values
     */
    protected function loadDefaults()
    {
        $this->loadLangDefault();
        $this->paypalLogos = new PayPalLogos($this->iso_code);
        $paymentMethod = Configuration::get(self::PAYMENT_METHOD);
        $orderProcessType = (int) Configuration::get('PS_ORDER_PROCESS_TYPE');

        if (Tools::getValue('paypal_ec_canceled') || $this->context->cart === false) {
            unset($this->context->cookie->express_checkout);
        }

        $version = Db::getInstance()->getValue('SELECT version FROM `'._DB_PREFIX_.'module` WHERE name = \''.$this->name.'\'');
        if (empty($version) === true) {
            Db::getInstance()->execute('
                UPDATE `'._DB_PREFIX_.'module` m
                SET m.version = \''.bqSQL($this->version).'\'
                WHERE m.name = \''.bqSQL($this->name).'\'');
        }

        if (isset($this->context->employee->id) && $this->context->employee->id) {
            /* Upgrade and compatibility checks */
            $this->compatibilityCheck();
            $this->warningsCheck();
        } else {
            if (isset($this->context->cookie->express_checkout)) {
                $this->context->smarty->assign('paypal_authorization', true);
            }

            $isECS = false;
            if (isset($this->context->cookie->express_checkout)) {
                $expressCheckoutCookie = unserialize($this->context->cookie->express_checkout);
                if (isset($expressCheckoutCookie['token']) && isset($expressCheckoutCookie['payer_id'])) {
                    $isECS = true;
                }
            }

            if (($orderProcessType == 1) && ((int) $paymentMethod == HSS) && !$this->useMobile()) {
                $this->context->smarty->assign('paypal_order_opc', true);
            } elseif (($orderProcessType == 1) && ((bool) Tools::getValue('isPaymentStep') == true || $isECS)) {
                $shopUrl = PayPal::getShopDomainSsl(true, true);
                $values = array('fc' => 'module', 'module' => 'paypal', 'controller' => 'confirm', 'get_confirmation' => true);
                $this->context->smarty->assign('paypal_confirmation', $shopUrl.__PS_BASE_URI__.'?'.http_build_query($values));

            }
        }
    }

    protected function checkMobileCredentials()
    {
        $paymentMethod = Configuration::get(self::PAYMENT_METHOD);

        if (((int) $paymentMethod == HSS) && (
            (!(bool) Configuration::get(self::API_USER)) &&
            (!(bool) Configuration::get(self::API_PASSWORD)) &&
            (!(bool) Configuration::get(self::API_SIGNATURE)))) {
            $this->warning .= $this->l('You must set your PayPal Integral credentials in order to have the mobile theme work correctly.').'<br />';
        }

    }

    protected function checkMobileNeeds()
    {
        $isoCode = Country::getIsoById((int) Configuration::get('PS_COUNTRY_DEFAULT'));
        $paypalCountries = array('ES', 'FR', 'PL', 'IT');

        if (method_exists($this->context->shop, 'getTheme')) {
            if (($this->context->shop->getTheme() == 'default') && in_array($isoCode, $paypalCountries)) {
                $this->warning .= $this->l('The mobile theme only works with the PayPal\'s payment module at this time. Please activate the module to enable payments.').'<br />';
            }

        } else {
            $this->warning .= $this->l('In order to use the module you need to install the backward compatibility.').'<br />';
        }

    }

    /* Check status of backward compatibility module */

    protected function backwardCompatibilityChecks()
    {
        if (Module::isInstalled('backwardcompatibility')) {
            $backwardModule = Module::getInstanceByName('backwardcompatibility');
            if (!$backwardModule->active) {
                $this->warning .= $this->l('To work properly the module requires the backward compatibility module enabled').'<br />';
            } elseif ($backwardModule->version < PayPal::BACKWARD_REQUIREMENT) {
                $this->warning .= $this->l('To work properly the module requires at least the backward compatibility module v').PayPal::BACKWARD_REQUIREMENT.'.<br />';
            }

        } else {
            $this->warning .= $this->l('In order to use the module you need to install the backward compatibility.').'<br />';
        }

    }

    public function getContent()
    {
        $this->postProcess();

        if (($idLang = Language::getIdByIso('EN')) == 0) {
            $englishLanguageId = (int) $this->context->employee->id_lang;
        } else {
            $englishLanguageId = (int) $idLang;
        }

        $this->context->smarty->assign(array(
            'PayPal_WPS' => (int) WPS,
            'PayPal_HSS' => (int) HSS,
            'PayPal_ECS' => (int) ECS,
            'PayPal_PPP' => (int) PPP,
            'PP_errors' => $this->errors,
            'PayPal_logo' => $this->paypalLogos->getLogos(),
            'PayPal_allowed_methods' => $this->getPaymentMethods(),
            'PayPal_country' => Country::getNameById((int) $englishLanguageId, (int) $this->defaultCountry),
            'PayPal_country_id' => (int) $this->defaultCountry,
            'PayPal_business' => Configuration::get(self::BUSINESS),
            'PayPal_payment_method' => (int) Configuration::get(self::PAYMENT_METHOD),
            'PayPal_api_username' => Configuration::get(self::API_USER),
            'PayPal_api_password' => Configuration::get(self::API_PASSWORD),
            'PayPal_api_signature' => Configuration::get(self::API_SIGNATURE),
            'PayPal_api_business_account' => Configuration::get(self::BUSINESS_ACCOUNT),
            'PayPal_express_checkout_shortcut' => (int) Configuration::get(self::EXPRESS_CHECKOUT_SHORTCUT),
            'PayPal_in_context_checkout' => (int) Configuration::get(self::IN_CONTEXT_CHECKOUT),
            'use_paypal_in_context' => (int) $this->useInContextCheckout(),
            'PayPal_in_context_checkout_merchant_id' => Configuration::get(self::IN_CONTEXT_CHECKOUT_M_ID),
            'PayPal_sandbox_mode' => (int) Configuration::get(self::SANDBOX),
            'PayPal_payment_capture' => (int) Configuration::get(self::CAPTURE),
            'PayPal_country_default' => (int) $this->defaultCountry,
            'PayPal_change_country_url' => 'index.php?tab=AdminCountries&token='.Tools::getAdminTokenLite('AdminCountries').'#footer',
            'Countries' => Country::getCountries($englishLanguageId),
            'One_Page_Checkout' => (int) Configuration::get('PS_ORDER_PROCESS_TYPE'),
            'PayPal_integral_evolution_template' => Configuration::get(self::HSS_TEMPLATE),
            'PayPal_integral_evolution_solution' => Configuration::get(self::HSS_SOLUTION),
            'PayPal_login' => (int) Configuration::get(self::LOGIN),
            'PayPal_login_client_id' => Configuration::get(self::LOGIN_CLIENT_ID),
            'PayPal_login_secret' => Configuration::get(self::LOGIN_SECRET),
            'PayPal_login_tpl' => (int) Configuration::get(self::LOGIN_TPL),
            'default_lang_iso' => Language::getIsoById($this->context->employee->id_lang),
            'PayPal_plus_client' => Configuration::get(self::PLUS_CLIENT_ID),
            'PayPal_plus_secret' => Configuration::get(self::PLUS_SECRET),
            'PayPal_plus_webprofile' => (Configuration::get(self::WEB_PROFILE_ID) != '0') ? Configuration::get(self::WEB_PROFILE_ID) : 0,
            //'PayPal_version_tls_checked' => $tls_version,
            'Presta_version' => _PS_VERSION_,
        ));

        $this->getTranslations();

        $output = $this->fetchTemplate('/views/templates/admin/back_office.tpl');

        if ($this->active == false) {
            return $output.$this->hookBackOfficeHeader();
        }

        return $output;
    }

    /**
     * Hooks methods
     */
    public function hookHeader()
    {
        if (isset($this->context->cart) && $this->context->cart->id) {
            $this->context->smarty->assign('id_cart', (int) $this->context->cart->id);
        }

        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

        $smarty = $this->context->smarty;
        $smarty->assign(array(
            'ssl_enabled' => Configuration::get('PS_SSL_ENABLED'),
            'PAYPAL_SANDBOX' => Configuration::get(self::SANDBOX),
            'PayPal_in_context_checkout' => Configuration::get(self::IN_CONTEXT_CHECKOUT),
            'use_paypal_in_context' => (int) $this->useInContextCheckout(),
            'PayPal_in_context_checkout_merchant_id' => Configuration::get(self::IN_CONTEXT_CHECKOUT_M_ID),
        ));

        $process = '<script type="text/javascript">'.$this->fetchTemplate('views/js/paypal.js').'</script>';
        if ($this->useInContextCheckout()) {
            $process .= '<script defer src="//www.paypalobjects.com/api/checkout.js"></script>';
        }

        if ((
            (method_exists($smarty, 'getTemplateVars') && ($smarty->getTemplateVars('page_name')
                == 'authentication' || $smarty->getTemplateVars('page_name') == 'order-opc'))
            || (isset($smarty->_tpl_vars) && ($smarty->_tpl_vars['page_name']
                == 'authentication' || $smarty->_tpl_vars['page_name'] == 'order-opc')))
            &&
            (int) Configuration::get('PAYPAL_LOGIN') == 1) {
            $this->context->smarty->assign(array(
                'paypal_locale' => $this->getLocale(),
                'PAYPAL_LOGIN_CLIENT_ID' => Configuration::get(self::LOGIN_CLIENT_ID),
                'PAYPAL_LOGIN_TPL' => Configuration::get(self::LOGIN_TPL),
                'PAYPAL_RETURN_LINK' => PayPalLogin::getReturnLink(),
            ));
            $process .= '
                    <script src="https://www.paypalobjects.com/js/external/api.js"></script>
                    <script>'.$this->fetchTemplate('views/js/paypal_login.js').'</script>';
        }

        if (Configuration::get(self::PAYMENT_METHOD) == PPP) {
            $this->context->smarty->assign(array(
                'paypal_locale' => $this->getLocalePayPalPlus(),
                'PAYPAL_LOGIN_CLIENT_ID' => Configuration::get(self::LOGIN_CLIENT_ID),
                'PAYPAL_LOGIN_TPL' => Configuration::get(self::LOGIN_TPL),
                'PAYPAL_RETURN_LINK' => PayPalLogin::getReturnLink(),
            ));
            $process .= '<script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js" type="text/javascript"></script>';
        }

        return $process;
    }

    public function useInContextCheckout()
    {
        return Configuration::get(self::IN_CONTEXT_CHECKOUT) && Configuration::get(self::IN_CONTEXT_CHECKOUT_M_ID)
            != null;
    }

    public function getLocalePayPalPlus()
    {
        switch (Tools::strtolower($this->getCountryCode())) {
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

    public function getLocale()
    {
        switch (Language::getIsoById($this->context->language->id)) {
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

    public function canBeUsed()
    {
        if (!$this->active) {
            return false;
        }

        //If merchant has not upgraded and payment method is out of country's specs
        if (!Configuration::get(self::UPDATED_COUNTRIES_OK) && !in_array((int) Configuration::get(self::PAYMENT_METHOD), $this->getPaymentMethods())) {
            return false;
        }

        return true;
    }

    public function hookProductFooter()
    {
        $content = (!$this->useMobile()) ? $this->renderExpressCheckoutButton('product')
        : null;
        return $content.$this->renderExpressCheckoutForm('product');
    }

    public function hookPayment($params)
    {
        if (!$this->canBeUsed()) {
            return null;
        }

        $useMobile = $this->useMobile();

        if ($useMobile) {
            $method = ECS;
        } else {
            $method = (int) Configuration::get(self::PAYMENT_METHOD);
        }

        if (isset($this->context->cookie->express_checkout)) {
            $this->redirectToConfirmation();
        }

        $isoLang = array(
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
        );

        $this->context->smarty->assign(array(
            'logos' => $this->paypalLogos->getLogos(),
            'sandbox_mode' => Configuration::get(self::SANDBOX),
            'use_mobile' => $useMobile,
            'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code]))
            ? $isoLang[$this->context->language->iso_code] : 'en_US',
        ));

        if ($method == HSS) {
            $billingAddress = new Address($this->context->cart->id_address_invoice);
            $deliveryAddress = new Address($this->context->cart->id_address_delivery);
            $billingAddress->country = new Country($billingAddress->id_country);
            $deliveryAddress->country = new Country($deliveryAddress->id_country);
            $billingAddress->state = new State($billingAddress->id_state);
            $deliveryAddress->state = new State($deliveryAddress->id_state);

            $cart = $this->context->cart;
            $cartDetails = $cart->getSummaryDetails(null, true);

            if ((int) Configuration::get('PAYPAL_SANDBOX') == 1) {
                $actionUrl = 'https://securepayments.sandbox.paypal.com/acquiringweb';
            } else {
                $actionUrl = 'https://securepayments.paypal.com/acquiringweb';
            }

            $shopUrl = PayPal::getShopDomainSsl(true, true);

            $this->context->smarty->assign(array(
                'action_url' => $actionUrl,
                'cart' => $cart,
                'cart_details' => $cartDetails,
                'currency' => new Currency((int) $cart->id_currency),
                'customer' => $this->context->customer,
                'business_account' => Configuration::get('PAYPAL_BUSINESS_ACCOUNT'),
                'custom' => Tools::jsonEncode(array('id_cart' => $cart->id, 'hash' => sha1(serialize($cart->nbProducts())))),
                'gift_price' => (float) $this->getGiftWrappingPrice(),
                'billing_address' => $billingAddress,
                'delivery_address' => $deliveryAddress,
                'shipping' => $cartDetails['total_shipping_tax_exc'],
                'subtotal' => $cartDetails['total_price_without_tax'] - $cartDetails['total_shipping_tax_exc'],
                'time' => time(),
                'cancel_return' => $this->context->link->getPageLink('order.php'),
                'notify_url' => $shopUrl._MODULE_DIR_.$this->name.'/ipn.php',
                'return_url' => $shopUrl._MODULE_DIR_.$this->name.'/integral_evolution/submit.php?id_cart='.(int) $cart->id,
                'tracking_code' => $this->getTrackingCode($method),
                'iso_code' => Tools::strtoupper($this->context->language->iso_code),
                'payment_hss_solution' => Configuration::get('PAYPAL_HSS_SOLUTION'),
                'payment_hss_template' => Configuration::get('PAYPAL_HSS_TEMPLATE'),
            ));
            $this->getTranslations();

            return $this->fetchTemplate('integral_evolution_payment.tpl');
        } elseif ($method == WPS || $method == ECS) {
            $this->getTranslations();
            $this->context->smarty->assign(array(
                'PayPal_integral' => WPS,
                'PayPal_express_checkout' => ECS,
                'PayPal_payment_method' => $method,
                'PayPal_payment_type' => 'payment_cart',
                'PayPal_current_page' => $this->getCurrentUrl(),
                'PayPal_tracking_code' => $this->getTrackingCode($method),
                'PayPal_in_context_checkout' => Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT'),
                'use_paypal_in_context' => (int) $this->useInContextCheckout(),
                'PayPal_in_context_checkout_merchant_id' => Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT_M_ID'),
            ));

            return $this->fetchTemplate('express_checkout_payment.tpl');
        } elseif ($method == PPP) {
            $CallApiPaypalPlus = new CallApiPayPalPlus();
            $CallApiPaypalPlus->setParams($params);

            $approvalUrl = $CallApiPaypalPlus->getApprovalUrl();

            $this->context->smarty->assign(
                array(
                    'approval_url' => $approvalUrl,
                    'language' => $this->getLocalePayPalPlus(),
                    'country' => $this->getCountryCode(),
                    'mode' => Configuration::get('PAYPAL_SANDBOX') ? 'sandbox'
                    : 'live',
                )
            );

            return $this->fetchTemplate('paypal_plus_payment.tpl');
        }
    }

    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if ($this->hookPayment($params) == null) {
            return null;
        }

        $useMobile = $this->useMobile();

        if ($useMobile) {
            $method = ECS;
        } else {
            $method = (int) Configuration::get(self::PAYMENT_METHOD);
        }

        if (isset($this->context->cookie->express_checkout)) {
            $this->redirectToConfirmation();
        }

        $logos = $this->paypalLogos->getLogos();

        if (isset($logos['LocalPayPalHorizontalSolutionPP']) && $method == WPS) {
            $logo = $logos['LocalPayPalHorizontalSolutionPP'];
        } else {
            $logo = $logos['LocalPayPalLogoMedium'];
        }

        $this->context->smarty->assign(array(
            'express_checkout_payment_link' => $this->context->link->getModuleLink($this->name, 'expresscheckoutpayment', array(), Tools::usingSecureMode()),
        ));

        if ($method == HSS) {
            return array(
                'cta_text' => $this->l('Paypal'),
                'logo' => $logo,
                'form' => $this->fetchTemplate('integral_evolution_payment_eu.tpl'),
            );
        } elseif ($method == WPS || $method == ECS) {
            return array(
                'cta_text' => $this->l('Paypal'),
                'logo' => $logo,
                'form' => $this->fetchTemplate('express_checkout_payment_eu.tpl'),
            );
        }
    }

    public function hookShoppingCartExtra()
    {
        if (!$this->active
            || (((int) Configuration::get(self::PAYMENT_METHOD) == HSS) && !$this->context->getMobileDevice())
            || !Configuration::get(self::EXPRESS_CHECKOUT_SHORTCUT)
            || !in_array(ECS, $this->getPaymentMethods())
            || isset($this->context->cookie->express_checkout)) {
            return null;
        }

        $values = array('en' => 'en_US', 'fr' => 'fr_FR', 'de' => 'de_DE');
        $paypalLogos = $this->paypalLogos->getLogos();

        $this->context->smarty->assign(array(
            'PayPal_payment_type' => 'cart',
            'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton'])
            ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'PayPal_lang_code' => (isset($values[$this->context->language->iso_code])
                ? $values[$this->context->language->iso_code] : 'en_US'),
            'PayPal_tracking_code' => $this->getTrackingCode((int) Configuration::get('PAYPAL_PAYMENT_METHOD')),
            'include_form' => true,
            'template_dir' => dirname(__FILE__).'/views/templates/hook/'));

        return $this->fetchTemplate('express_checkout_shortcut_button.tpl');
    }

    public function hookPaymentReturn()
    {
        if (!$this->active) {
            return null;
        }

        return $this->fetchTemplate('confirmation.tpl');
    }

    public function hookRightColumn()
    {
        $this->context->smarty->assign('logo', $this->paypalLogos->getCardsLogo(true));

        return $this->fetchTemplate('column.tpl');
    }

    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    public function hookBackBeforePayment($params)
    {
        if (!$this->active) {
            return null;
        }

        /* Only execute if you use PayPal API for payment */
        if (((int) Configuration::get('PAYPAL_PAYMENT_METHOD') != HSS) && $this->isPayPalAPIAvailable()) {
            if ($params['module'] != $this->name || !$this->context->cookie->paypal_token
                || !$this->context->cookie->paypal_payer_id) {
                return false;
            }

            Tools::redirect('modules/'.$this->name.'/express_checkout/submit.php?confirm=1&token='.$this->context->cookie->paypal_token.'&payerID='.$this->context->cookie->paypal_payer_id);
        }
    }

    public function setPayPalAsConfigured()
    {
        Configuration::updateValue(self::CONFIGURATION_OK, true);
    }

    public function hookAdminOrder($params)
    {
        if (Tools::isSubmit('submitPayPalCapture')) {
            if ($captureAmount = Tools::getValue('totalCaptureMoney')) {
                if ($captureAmount = PayPalCapture::parsePrice($captureAmount)) {
                    if (Validate::isFloat($captureAmount)) {
                        $captureAmount = Tools::ps_round($captureAmount, '6');
                        $ord = new Order((int) $params['id_order']);
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

                            $this->doCapture($params['id_order'], $captureAmount, $complete);
                        }
                    }
                }
            }
        } elseif (Tools::isSubmit('submitPayPalRefund')) {
            $this->doFullRefund($params['id_order']);
        }

        $adminTemplates = array();
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
            $order = new Order((int) $params['id_order']);
            $currency = new Currency($order->id_currency);
            $cpt = new PayPalCapture();
            $cpt->id_order = (int) $order->id;
            $orderState = $order->current_state;

            $this->context->smarty->assign(
                array(
                    'authorization' => (int) Configuration::get('PAYPAL_OS_AUTHORIZATION'),
                    'base_url' => _PS_BASE_URL_.__PS_BASE_URI__,
                    'module_name' => $this->name,
                    'order_state' => $orderState,
                    'params' => $params,
                    'id_currency' => $currency->getSign(),
                    'rest_to_capture' => Tools::ps_round($cpt->getRestToPaid($order), '6'),
                    'list_captures' => $cpt->getListCaptured(),
                    'ps_version' => _PS_VERSION_,
                )
            );

            foreach ($adminTemplates as $adminTemplate) {
                $this->html .= $this->fetchTemplate('/views/templates/admin/admin_order/'.$adminTemplate.'.tpl');
                $this->postProcess();
                $this->html .= '</fieldset>';
            }
        }

        return $this->html;
    }

    public function hookCancelProduct($params)
    {
        /** @var Order $order */
        if (Tools::isSubmit('generateDiscount') || !$this->isPayPalAPIAvailable()
            || Tools::isSubmit('generateCreditSlip')) {
            return false;
        } elseif ($params['order']->module != $this->name || !($order = $params['order'])
            || !Validate::isLoadedObject($order)) {
            return false;
        } elseif (!$order->hasBeenPaid()) {
            return false;
        }

        $orderDetail = new OrderDetail((int) $params['id_order_detail']);
        if (!$orderDetail || !Validate::isLoadedObject($orderDetail)) {
            return false;
        }

        $paypalOrder = PayPalOrder::getOrderById((int) $order->id);
        if (!$paypalOrder) {
            return false;
        }

        $products = $order->getProducts();
        $cancelQuantity = Tools::getValue('cancelQuantity');
        $message = $this->l('Cancel products result:').'<br>';

        $amount = (float) ($products[(int) $orderDetail->id]['product_price_wt'] * (int) $cancelQuantity[(int) $orderDetail->id]);
        $refund = $this->doRefund($paypalOrder['id_transaction'], (int) $order->id, $amount);
        $this->formatMessage($refund, $message);
        $this->addNewPrivateMessage((int) $order->id, $message);
    }

    public function hookActionPSCleanerGetModulesTables()
    {
        return array('paypal_customer', 'paypal_order');
    }

    public function hookBackOfficeHeader()
    {
        if ((strcmp(Tools::getValue('configure'), $this->name) === 0) ||
            (strcmp(Tools::getValue('module_name'), $this->name) === 0)) {
            $this->context->controller->addJquery();
            $this->context->controller->addJQueryPlugin('fancybox');
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

            $this->context->smarty->assign(array(
                'PayPal_module_dir' => _MODULE_DIR_.$this->name,
                'PayPal_WPS' => (int) WPS,
                'PayPal_HSS' => (int) HSS,
                'PayPal_ECS' => (int) ECS,
                'PayPal_PPP' => (int) PPP,
            ));

            return (isset($output) ? $output : null).$this->fetchTemplate('/views/templates/admin/header.tpl');
        }

        return null;
    }

    public function renderExpressCheckoutButton($type)
    {
        if ((!Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT') && !$this->useMobile())) {
            return null;
        }

        if (!in_array(ECS, $this->getPaymentMethods()) || (((int) Configuration::get('PAYPAL_BUSINESS')
            == 1) &&
            (int) Configuration::get('PAYPAL_PAYMENT_METHOD') == HSS) && !$this->useMobile()) {
            return null;
        }

        $paypalLogos = $this->paypalLogos->getLogos();
        $isoLang = array(
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
        );

        $this->context->smarty->assign(array(
            'use_mobile' => (bool) $this->useMobile(),
            'PayPal_payment_type' => $type,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code])) ? $isoLang[$this->context->language->iso_code] : 'en_US',
            'PayPal_tracking_code' => $this->getTrackingCode((int) Configuration::get('PAYPAL_PAYMENT_METHOD')),
            'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton']) ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
        ));

        return $this->fetchTemplate('express_checkout_shortcut_button.tpl');
    }

    public function renderExpressCheckoutForm($type)
    {
        if ((!Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT') && !$this->useMobile())
            || !in_array(ECS, $this->getPaymentMethods()) ||
            (((int) Configuration::get('PAYPAL_BUSINESS') == 1) && ((int) Configuration::get('PAYPAL_PAYMENT_METHOD') == HSS) && !$this->useMobile())) {
            return null;
        }

        $idProduct = (int) Tools::getValue('id_product');
        $idProductAttribute = (int) Product::getDefaultAttribute($idProduct);
        if ($idProductAttribute) {
            $minimalQuantity = Attribute::getAttributeMinimalQty($idProductAttribute);
        } else {
            $product = new Product($idProduct);
            $minimalQuantity = $product->minimal_quantity;
        }

        $this->context->smarty->assign(array(
            'PayPal_payment_type' => $type,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'id_product_attribute_ecs' => $idProductAttribute,
            'product_minimal_quantity' => $minimalQuantity,
            'PayPal_tracking_code' => $this->getTrackingCode((int) Configuration::get(self::PAYMENT_METHOD)),
            'express_checkout_payment_link' => $this->context->link->getModuleLink('paypal', 'expresscheckoutpayment', array(), Tools::usingSecureMode()),
        ));

        return $this->fetchTemplate('express_checkout_shortcut_form.tpl');
    }

    public function useMobile()
    {
        if ((method_exists($this->context, 'getMobileDevice') && $this->context->getMobileDevice())
            || Tools::getValue('ps_mobile_site')) {
            return true;
        }

        return false;
    }

    public function isCountryAPAC()
    {
        $country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));

        $tabCountryApac = array('CN', 'JP', 'AU', 'HK', 'TW', 'NZ', 'BU', 'BN', 'KH',
            'ID', 'LA', 'MY', 'PH', 'SG', 'TH',
            'TL', 'VN');

        if (in_array($country->iso_code, $tabCountryApac)) {
            return true;
        }
        return false;
    }

    public function getTrackingCode($method)
    {
        $isApacCountry = $this->isCountryAPAC();

        if ((_PS_VERSION_ < '1.5') && (_THEME_NAME_ == 'prestashop_mobile' || Tools::getValue('ps_mobile_site')
            == 1)) {
            if (_PS_MOBILE_TABLET_) {
                return $isApacCountry ? APAC_TABLET_TRACKING_CODE : TABLET_TRACKING_CODE;
            } elseif (_PS_MOBILE_PHONE_) {
                return $isApacCountry ? APAC_SMARTPHONE_TRACKING_CODE : SMARTPHONE_TRACKING_CODE;
            }

        }

        //Get Seamless checkout
        $loginUser = false;
        if (Configuration::get('PAYPAL_LOGIN')) {
            $loginUser = PayPalLoginUser::getByIdCustomer((int) $this->context->customer->id);

            if ($loginUser && $loginUser->expires_in <= time()) {
                $obj = new PayPalLogin();
                $loginUser = $obj->getRefreshToken();
            }
        }

        if ($method == WPS) {
            if ($loginUser) {
                return $isApacCountry ? APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS : TRACKING_EXPRESS_CHECKOUT_SEAMLESS;
            } else {
                return $isApacCountry ? APAC_TRACKING_INTEGRAL : TRACKING_INTEGRAL;
            }

        }
        if ($method == HSS) {
            return $isApacCountry ? APAC_TRACKING_INTEGRAL_EVOLUTION : TRACKING_INTEGRAL_EVOLUTION;
        }

        if ($method == ECS) {
            if ($loginUser) {
                return $isApacCountry ? APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS : TRACKING_EXPRESS_CHECKOUT_SEAMLESS;
            } else {
                return $isApacCountry ? APAC_TRACKING_OPTION_PLUS : TRACKING_OPTION_PLUS;
            }

        }
        if ($method == PPP) {
            return $isApacCountry ? APAC_TRACKING_PAYPAL_PLUS : TRACKING_PAYPAL_PLUS;
        }

        return TRACKING_CODE;
    }

    public function getTranslations()
    {
        $file = dirname(__FILE__).'/'._PAYPAL_TRANSLATIONS_XML_;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            if (isset($xml) && $xml) {
                $index = -1;
                $content = $default = array();

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

    public function getPayPalURL()
    {
        return 'www'.(Configuration::get('PAYPAL_SANDBOX') ? '.sandbox' : '').'.paypal.com';
    }

    public function getPaypalIntegralEvolutionUrl()
    {
        if (Configuration::get('PAYPAL_SANDBOX')) {
            return 'https://'.$this->getPayPalURL().'/cgi-bin/acquiringweb';
        }

        return 'https://securepayments.paypal.com/acquiringweb?cmd=_hosted-payment';
    }

    public function getPaypalStandardUrl()
    {
        return 'https://'.$this->getPayPalURL().'/cgi-bin/webscr';
    }

    public function getAPIURL()
    {
        return 'api-3t'.(Configuration::get('PAYPAL_SANDBOX') ? '.sandbox' : '').'.paypal.com';
    }

    public function getAPIScript()
    {
        return '/nvp';
    }

    public function getPaymentMethods()
    {
        if (Configuration::get(self::UPDATED_COUNTRIES_OK)) {
            return AuthenticatePaymentMethods::authenticatePaymentMethodByLang(Tools::strtoupper($this->context->language->iso_code));
        } else {
            $country = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));

            return AuthenticatePaymentMethods::authenticatePaymentMethodByCountry($country->iso_code);
        }
    }

    public function getCountryCode()
    {
        $cart = new Cart((int) $this->context->cookie->id_cart);
        $address = new Address((int) $cart->id_address_invoice);
        $country = new Country((int) $address->id_country);

        return $country->iso_code;
    }

    public function displayPayPalAPIError($message, $log = false)
    {
        $send = true;
        // Sanitize log
        if (is_array($log)) {
            foreach ($log as $key => $string) {
                if ($string == 'ACK -> Success') {
                    $send = false;
                } elseif (Tools::substr($string, 0, 6) == 'METHOD') {
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

        $this->context->smarty->assign(array('message' => $message, 'logs' => $log));

        if ($send) {
            $idLang = (int) $this->context->language->id;
            $isoLang = Language::getIsoById($idLang);

            if (!is_dir(dirname(__FILE__).'/mails/'.Tools::strtolower($isoLang))) {
                $idLang = Language::getIdByIso('en');
            }

            Mail::Send(
                $idLang,
                'error_reporting',
                Mail::l('Error reporting from your PayPal module', (int) $this->context->language->id),
                array('{logs}' => implode('<br />', $log)),
                Configuration::get('PS_SHOP_EMAIL'),
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_.$this->name.'/mails/'
            );
        }

        return $this->fetchTemplate('error.tpl');
    }

    private function canRefund($idOrder)
    {
        if (!(bool) $idOrder) {
            return false;
        }

        $paypalOrder = Db::getInstance()->getRow('
            SELECT `payment_status`, `capture`
            FROM `'._DB_PREFIX_.'paypal_order`
            WHERE `id_order` = '.(int) $idOrder);

        return $paypalOrder && ($paypalOrder['payment_status'] == 'Completed' || $paypalOrder['payment_status']
            == 'approved') && $paypalOrder['capture'] == 0;
    }

    private function needsValidation($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $order = Db::getInstance()->getRow('
            SELECT `payment_method`, `payment_status`
            FROM `'._DB_PREFIX_.'paypal_order`
            WHERE `id_order` = '.(int) $idOrder);

        return $order && $order['payment_method'] != HSS && $order['payment_status']
            == 'Pending_validation';
    }

    private function needsCapture($idOrder)
    {
        if (!(int) $idOrder) {
            return false;
        }

        $result = Db::getInstance()->getRow('
            SELECT `payment_method`, `payment_status`
            FROM `'._DB_PREFIX_.'paypal_order`
            WHERE `id_order` = '.(int) $idOrder.' AND `capture` = 1');

        return $result && $result['payment_method'] != HSS && $result['payment_status']
            == 'Pending_capture';
    }

    private function preProcess()
    {
        if (Tools::isSubmit('submitPaypal')) {
            $business = Tools::getValue('business') !== false ? (int) Tools::getValue('business') : false;
            $paymentMethod = Tools::getValue('paypal_payment_method') !== false ? (int) Tools::getValue('paypal_payment_method') : false;
            $paymentCapture = Tools::getValue('payment_capture') !== false ? (int) Tools::getValue('payment_capture') : false;
            $sandboxMode = Tools::getValue('sandbox_mode') !== false ? (int) Tools::getValue('sandbox_mode') : false;

            if ($this->defaultCountry === false || $sandboxMode === false || $paymentCapture === false || $business === false || $paymentMethod === false) {
                $this->errors[] = $this->l('Some fields are empty.');
            } elseif (!$business) {
                $this->errors[] = $this->l('Credentials fields cannot be empty');
            } elseif ($business) {
                if (($paymentMethod == WPS || $paymentMethod == ECS) && (!Tools::getValue('api_username')
                    || !Tools::getValue('api_password') || !Tools::getValue('api_signature'))) {
                    $this->errors[] = $this->l('Credentials fields cannot be empty');
                }

                if ($paymentMethod == PPP && (Tools::getValue('paypalplus_webprofile')
                    != 0 && (!Tools::getValue('client_id') && !Tools::getValue('secret')))) {
                    $this->errors[] = $this->l('Credentials fields cannot be empty');
                }

                if ($paymentMethod == HSS && !Tools::getValue('api_business_account')) {
                    $this->errors[] = $this->l('Business e-mail field cannot be empty');
                }

            }
        }

        return !count($this->errors);
    }

    private function postProcess()
    {
        if (Tools::getValue('old_partners')) {
            Configuration::updateValue(self::UPDATED_COUNTRIES_OK,1);
        }

        if (Tools::isSubmit('submitTlsVerificator')) {
            $tlsVe = new TLSVerificator(true,$this);
            if ($tlsVe->getVersion() == '1.2') {
                $tlsVerificator = 1;
            } else {
                $tlsVerificator = 0;
            }
        } else {
            $tlsVerificator = -1;
        }

        $this->context->smarty->assign('PayPal_tls_verificator', $tlsVerificator);

        if (Tools::isSubmit('submitPaypal')) {
            if (Tools::getValue('paypal_country_only')) {
                Configuration::updateValue('PAYPAL_COUNTRY_DEFAULT', (int) Tools::getValue('paypal_country_only'));
            } elseif ($this->preProcess()) {
                Configuration::updateValue(self::BUSINESS, (int) Tools::getValue('business'));
                Configuration::updateValue(self::PAYMENT_METHOD, (int) Tools::getValue('paypal_payment_method'));
                Configuration::updateValue(self::API_USER, trim(Tools::getValue('api_username')));
                Configuration::updateValue(self::API_PASSWORD, trim(Tools::getValue('api_password')));
                Configuration::updateValue(self::API_SIGNATURE, trim(Tools::getValue('api_signature')));
                Configuration::updateValue(self::BUSINESS_ACCOUNT, trim(Tools::getValue('api_business_account')));
                Configuration::updateValue(self::EXPRESS_CHECKOUT_SHORTCUT, (int) Tools::getValue('express_checkout_shortcut'));
                Configuration::updateValue(self::IN_CONTEXT_CHECKOUT_M_ID, Tools::getValue('in_context_checkout_merchant_id'));
                Configuration::updateValue(self::SANDBOX, (int) Tools::getValue('sandbox_mode'));
                Configuration::updateValue(self::CAPTURE, (int) Tools::getValue('payment_capture'));

                /* USE PAYPAL LOGIN */
                Configuration::updateValue(self::LOGIN, (int) Tools::getValue('paypal_login'));
                Configuration::updateValue(self::LOGIN_CLIENT_ID, Tools::getValue('paypal_login_client_id'));
                Configuration::updateValue(self::LOGIN_SECRET, Tools::getValue('paypal_login_client_secret'));
                Configuration::updateValue(self::LOGIN_TPL, (int) Tools::getValue('paypal_login_client_template'));

                /* USE PAYPAL PLUS */
                if ((int) Tools::getValue('paypal_payment_method') == 5) {
                    Configuration::updateValue(self::PLUS_CLIENT_ID, Tools::getValue('client_id'));
                    Configuration::updateValue(self::PLUS_SECRET, Tools::getValue('secret'));

                    if ((int) Tools::getValue('paypalplus_webprofile') == 1) {
                        $ApiPaypalPlus = new ApiPayPalPlus();
                        $idWebProfile = $ApiPaypalPlus->getWebProfile();

                        if ($idWebProfile) {
                            Configuration::updateValue('PAYPAL_WEB_PROFILE_ID', $idWebProfile);
                        } else {
                            Configuration::updateValue('PAYPAL_WEB_PROFILE_ID', 0);
                        }
                    }
                }
                /* IS IN_CONTEXT_CHECKOUT ENABLED */
                if ((int) Tools::getValue('paypal_payment_method') != 2) {
                    Configuration::updateValue('PAYPAL_IN_CONTEXT_CHECKOUT', (int) Tools::getValue('in_context_checkout'));
                } else {
                    Configuration::updateValue('PAYPAL_IN_CONTEXT_CHECKOUT', 0);
                }

                /* /IS IN_CONTEXT_CHECKOUT ENABLED */

                //EXPRESS CHECKOUT TEMPLATE
                Configuration::updateValue('PAYPAL_HSS_SOLUTION', (int) Tools::getValue('integral_evolution_solution'));
                if (Tools::getValue('integral_evolution_solution') == PAYPAL_HSS_IFRAME) {
                    Configuration::updateValue('PAYPAL_HSS_TEMPLATE', 'D');
                } else {
                    Configuration::updateValue('PAYPAL_HSS_TEMPLATE', Tools::getValue('integral_evolution_template'));
                }

                $this->context->smarty->assign('PayPal_save_success', true);
            } else {
                $this->html = $this->displayError(implode('<br />', $this->errors)); // Not displayed at this time
                $this->context->smarty->assign('PayPal_save_failure', true);
            }
        }

        return $this->loadLangDefault();
    }

    private function doRefund($idTransaction, $idOrder, $amt = false)
    {
        if (!$this->isPayPalAPIAvailable()) {
            die(Tools::displayError('Fatal Error: no API Credentials are available'));
        } elseif (!$idTransaction) {
            die(Tools::displayError('Fatal Error: id_transaction is null'));
        }

        $paymentMethod = Configuration::get('PAYPAL_PAYMENT_METHOD');

        if ($paymentMethod != PPP) {

            if (!$amt) {
                $params = array('TRANSACTIONID' => $idTransaction, 'REFUNDTYPE' => 'Full');
            } else {
                $isoCurrency = Db::getInstance()->getValue('
                    SELECT `iso_code`
                    FROM `'._DB_PREFIX_.'orders` o
                    LEFT JOIN `'._DB_PREFIX_.'currency` c ON (o.`id_currency` = c.`id_currency`)
                    WHERE o.`id_order` = '.(int) $idOrder);

                $params = array(
                    'TRANSACTIONID' => $idTransaction,
                    'REFUNDTYPE' => 'Partial',
                    'AMT' => (float) $amt,
                    'CURRENCYCODE' => Tools::strtoupper($isoCurrency),
                );
            }

            $paypalLib = new PaypalLib();

            return $paypalLib->makeCall(
                $this->getAPIURL(),
                $this->getAPIScript(),
                'RefundTransaction',
                '&'.http_build_query($params, '', '&')
            );
        } else {

            if (!$amt) {
                $params = new stdClass();
            } else {
                $result = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'paypal_order WHERE id_transaction = "'.pSQL($idTransaction).'"');
                $result = current($result);

                $amount = new stdClass();
                $amount->total = $amt;
                $amount->currency = $result['currency'];

                $params = new stdClass();
                $params->amount = $amount;
            }

            $callApiPaypalPlus = new CallApiPayPalPlus();

            return Tools::jsonDecode($callApiPaypalPlus->executeRefund($idTransaction, $params));
        }
    }

    public function addNewPrivateMessage($idOrder, $message)
    {
        if (!(bool) $idOrder) {
            return false;
        }

        $newMessage = new Message();
        $message = strip_tags($message, '<br>');

        if (!Validate::isCleanHtml($message)) {
            $message = $this->l('Payment message is not valid, please check your module.');
        }

        $newMessage->message = $message;
        $newMessage->id_order = (int) $idOrder;
        $newMessage->private = 1;

        return $newMessage->add();
    }

    private function doFullRefund($idOrder)
    {
        $paypalOrder = PayPalOrder::getOrderById((int) $idOrder);
        if (!$this->isPayPalAPIAvailable() || !$paypalOrder) {
            return false;
        }

        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        $products = $order->getProducts();
        $currency = new Currency((int) $order->id_currency);
        if (!Validate::isLoadedObject($currency)) {
            $this->errors[] = $this->l('Not a valid currency');
        }

        if (count($this->errors)) {
            return false;
        }

        $decimals = (is_array($currency) ? (int) $currency['decimals'] : (int) $currency->decimals) * _PS_PRICE_DISPLAY_PRECISION_;

        // Amount for refund
        $amt = 0.00;

        foreach ($products as $product) {
            $amt += (float) ($product['product_price_wt']) * ($product['product_quantity'] - $product['product_quantity_refunded']);
        }

        $amt += (float) ($order->total_shipping) + (float) ($order->total_wrapping) - (float) ($order->total_discounts);

        // check if total or partial
        if (Tools::ps_round($order->total_paid_real, $decimals) == Tools::ps_round($amt, $decimals)) {
            $response = $this->doRefund($paypalOrder['id_transaction'], $idOrder);
        } else {
            $response = $this->doRefund($paypalOrder['id_transaction'], $idOrder, (float) ($amt));
        }

        $message = $this->l('Refund operation result:')." \r\n";
        foreach ($response as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $message .= $key.': '.Tools::jsonEncode($value)." \r\n";
            } else {
                $message .= $key.': '.$value." \r\n";
            }
        }
        if ((array_key_exists('ACK', $response) && $response['ACK'] == 'Success'
            && $response['REFUNDTRANSACTIONID'] != '') || (isset($response->state)
            && $response->state == 'completed')) {
            $message .= $this->l('PayPal refund successful!');
            if (!Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'paypal_order` SET `payment_status` = \'Refunded\' WHERE `id_order` = '.(int) $idOrder)) {
                die(Tools::displayError('Error when updating PayPal database'));
            }

            $history = new OrderHistory();
            $history->id_order = (int) $idOrder;
            $history->changeIdOrderState((int) Configuration::get('PS_OS_REFUND'), $history->id_order);
            $history->addWithemail();
            $history->save();
        } else {
            $message .= $this->l('Transaction error!');
        }

        $this->addNewPrivateMessage((int) $idOrder, $message);

        Tools::redirect($_SERVER['HTTP_REFERER']);
    }

    private function doCapture($idOrder, $captureAmount = false, $isComplete = false)
    {
        $paypalOrder = PayPalOrder::getOrderById((int) $idOrder);
        if (!$this->isPayPalAPIAvailable() || !$paypalOrder) {
            return false;
        }

        $order = new Order((int) $idOrder);
        $currency = new Currency((int) $order->id_currency);

        if (!$captureAmount) {
            $captureAmount = (float) $order->total_paid;
        }

        $complete = 'Complete';
        if (!$isComplete) {
            $complete = 'NotComplete';
        }

        $paypalLib = new PaypalLib();
        $response = $paypalLib->makeCall(
            $this->getAPIURL(),
            $this->getAPIScript(),
            'DoCapture',
            '&'.http_build_query(
                array(
                    'AMT' => $captureAmount,
                    'AUTHORIZATIONID' => $paypalOrder['id_transaction'],
                    'CURRENCYCODE' => $currency->iso_code,
                    'COMPLETETYPE' => $complete,
                ),
                '',
                '&'
            )
        );
        $message = $this->l('Capture operation result:').'<br>';

        foreach ($response as $key => $value) {
            $message .= $key.': '.$value.'<br>';
        }

        $capture = new PayPalCapture();
        $capture->id_order = (int) $idOrder;
        $capture->capture_amount = (float) $captureAmount;

        if ((array_key_exists('ACK', $response)) && ($response['ACK'] == 'Success')
            && ($response['PAYMENTSTATUS'] == 'Completed')) {
            $capture->result = pSQL($response['PAYMENTSTATUS']);
            if ($capture->save()) {
                if (!($capture->getRestToCapture($capture->id_order))) {
                    //plus d'argent a capturer
                    if (!Db::getInstance()->Execute(
                        'UPDATE `'._DB_PREFIX_.'paypal_order`
                        SET `capture` = 0, `payment_status` = \''.pSQL($response['PAYMENTSTATUS']).'\', `id_transaction` = \''.pSQL($response['TRANSACTIONID']).'\'
                        WHERE `id_order` = '.(int) $idOrder
                    )
                    ) {
                        die(Tools::displayError('Error when updating PayPal database'));
                    }

                    $orderHistory = new OrderHistory();
                    $orderHistory->id_order = (int) $idOrder;
                    $orderHistory->changeIdOrderState(Configuration::get('PS_OS_WS_PAYMENT'), $order);

                    $orderHistory->addWithemail();
                    $message .= $this->l('Order finished with PayPal!');
                }
            }
        } elseif (isset($response['PAYMENTSTATUS'])) {
            $capture->result = pSQL($response['PAYMENTSTATUS']);
            $capture->save();
            $message .= $this->l('Transaction error!');
        }

        $this->addNewPrivateMessage((int) $idOrder, $message);

        Tools::redirect($_SERVER['HTTP_REFERER']);
    }

    public function fetchTemplate($name)
    {
        return $this->display(__FILE__, $name);
    }

    public static function getPayPalCustomerIdByEmail($email)
    {
        return Db::getInstance()->getValue(
            'SELECT `id_customer`
            FROM `'._DB_PREFIX_.'paypal_customer`
            WHERE paypal_email = \''.pSQL($email).'\''
        );
    }

    public static function getPayPalEmailByIdCustomer($idCustomer)
    {
        return Db::getInstance()->getValue(
            'SELECT `paypal_email`
            FROM `'._DB_PREFIX_.'paypal_customer`
            WHERE `id_customer` = '.(int) $idCustomer
        );
    }

    public static function addPayPalCustomer($idCustomer, $email)
    {
        if (!PayPal::getPayPalEmailByIdCustomer($idCustomer)) {
            Db::getInstance()->Execute(
                'INSERT INTO `'._DB_PREFIX_.'paypal_customer` (`id_customer`, `paypal_email`)
                VALUES('.(int) $idCustomer.', \''.pSQL($email).'\')'
            );

            return Db::getInstance()->Insert_ID();
        }

        return false;
    }

    private function warningsCheck()
    {
        if (Configuration::get(self::PAYMENT_METHOD) == HSS && Configuration::get(self::BUSINESS_ACCOUNT) == 'paypal@thirtybees.com') {
            $this->warning = $this->l('You are currently using the default PayPal e-mail address, please enter your own e-mail address.').'<br />';
        }

        /* Check preactivation warning */
        if (Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING')) {
            $this->warning .= (!empty($this->warning)) ? ', ' : Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING').'<br />';
        }

        if (!function_exists('curl_init')) {
            $this->warning .= $this->l('In order to use your module, please activate cURL (PHP extension)');
        }

    }

    private function loadLangDefault()
    {
        if (Configuration::get(self::UPDATED_COUNTRIES_OK)) {
            $this->iso_code = Tools::strtoupper($this->context->language->iso_code);
            if ($this->iso_code == 'EN') {
                $isoCode = 'GB';
            } else {
                $isoCode = $this->iso_code;
            }
            $this->defaultCountry = Country::getByIso($isoCode);
        } else {
            $this->defaultCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT');
            $country = new Country($this->defaultCountry);
            $this->iso_code = Tools::strtoupper($country->iso_code);
        }

        //$this->iso_code = AuthenticatePaymentMethods::getCountryDependency($iso_code);
    }
    public function formatMessage($response, &$message)
    {
        foreach ($response as $key => $value) {
            $message .= $key.': '.$value.'<br>';
        }

    }

    private function checkCurrency($cart)
    {
        $currencyModule = $this->getCurrency((int) $cart->id_currency);

        if ((int) $cart->id_currency == (int) $currencyModule->id) {
            return true;
        } else {
            return false;
        }

    }

    public static function getShopDomainSsl($http = false, $entities = false)
    {
        if (method_exists('Tools', 'getShopDomainSsl')) {
            return Tools::getShopDomainSsl($http, $entities);
        } else {
            if (!($domain = Configuration::get('PS_SHOP_DOMAIN_SSL'))) {
                $domain = Tools::getHttpHost();
            }

            if ($entities) {
                $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
            }

            if ($http) {
                $domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$domain;
            }

            return $domain;
        }
    }

    public function validateOrder($idCart, $idOrderState, $amountPaid, $paymentMethod = 'Unknown', $message = null, $transaction = array(), $currencySpecial = null, $dontTouchAmount = false, $secureKey = false, Shop $shop = null)
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

            $this->setPayPalAsConfigured();
        }
    }

    protected function getGiftWrappingPrice()
    {
        $wrappingFeesTaxInc = $this->context->cart->getGiftWrappingPrice();

        return (float) Tools::convertPrice($wrappingFeesTaxInc, $this->context->currency);
    }

    public function redirectToConfirmation()
    {
        $shopUrl = PayPal::getShopDomainSsl(true, true);

        // Check if user went through the payment preparation detail and completed it
        $detail = unserialize($this->context->cookie->express_checkout);

        if (!empty($detail['payer_id']) && !empty($detail['token'])) {
            $values = array('get_confirmation' => true);
            Tools::redirect(Context::getContext()->link->getModuleLink('paypal', 'confirm', $values));
        }
    }

    /**
     * Check if the current page use SSL connection on not
     *
     * @return bool uses SSL
     */
    public function usingSecureMode()
    {
        if (isset($_SERVER['HTTPS'])) {
            return ($_SERVER['HTTPS'] == 1 || Tools::strtolower($_SERVER['HTTPS']) == 'on');
        }

        // $_SERVER['SSL'] exists only in some specific configuration
        if (isset($_SERVER['SSL'])) {
            return ($_SERVER['SSL'] == 1 || Tools::strtolower($_SERVER['SSL']) == 'on');
        }

        return false;
    }

    protected function getCurrentUrl()
    {
        $protocolLink = $this->usingSecureMode() ? 'https://' : 'http://';
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');

        if (($pos !== false) && ($pos >= 0)) {
            $request = Tools::substr($request, 0, $pos);
        }

        $params = urlencode($_SERVER['QUERY_STRING']);

        return $protocolLink.Tools::getShopDomainSsl().$request.'?'.$params;
    }

    public function assignCartSummary()
    {
        $currency = new Currency((int) $this->context->cart->id_currency);

        $this->context->smarty->assign(array(
            'total' => Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
            'logos' => $this->paypalLogos->getLogos(),
            'use_mobile' => (bool) $this->useMobile(),
            'address_shipping' => new Address($this->context->cart->id_address_delivery),
            'address_billing' => new Address($this->context->cart->id_address_invoice),
            'cart' => $this->context->cart,
            'patternRules' => array('avoid' => array()),
            'cart_image_size' => 'cart_default',
            'useStyle14' => false,
            'useStyle15' => false,
        ));

        $this->context->smarty->assign(array(
            'paypal_cart_summary' => $this->display(__FILE__, 'views/templates/hook/paypal_cart_summary.tpl'),
        ));
    }
}
