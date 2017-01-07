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

use PayPalModule\PayPalRestApi;
use PayPalModule\AvailablePaymentMethods;
use PayPalModule\CallPayPalPlusApi;
use PayPalModule\PayPalCapture;
use PayPalModule\PayPalCustomer;
use PayPalModule\PayPalLib;
use PayPalModule\PayPalLogin;
use PayPalModule\PayPalLoginUser;
use PayPalModule\PayPalLogos;
use PayPalModule\PayPalOrder;
use PayPalModule\PayPalTools;
use PayPalModule\TlsVerifier;

/**
 * Class PayPal
 */
class PayPal extends \PaymentModule
{
    /** @var string $html */
    protected $html = '';

    /** @var array $errors */
    public $errors = [];

    // @codingStandardsIgnoreStart
    /**
     * Indicates whether the module is compatible with the Advanced EU Checkout
     *
     * @var int $is_eu_compatible
     */
    public $is_eu_compatible = 1;
    // @codingStandardsIgnoreEnd

    /** @var \Context $context */
    public $context;

    // @codingStandardsIgnoreStart
    /** @var string $iso_code */
    public $iso_code;
    // @codingStandardsIgnoreEnd

    public $defaultCountry;

    // @codingStandardsIgnoreStart
    public $module_key = '336225a5988ad434b782f2d868d7bfcd';
    // @codingStandardsIgnoreEnd

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
    const LOGIN_TPL = 'PAYPAL_LOGIN_TPL';
    const EXPRESS_CHECKOUT_SHORTCUT = 'PAYPAL_EXPRESS_CHECKOUT_SHORTCUT';

    const WEBSITE_PAYMENTS_PRO_HOSTED = 'PAYPAL_WPRH';

    const API_USER = 'PAYPAL_API_USER';
    const API_PASSWORD = 'PAYPAL_API_PASSWORD';
    const API_SIGNATURE = 'PAYPAL_API_SIGNATURE';
    const CLIENT_ID = 'PAYPAL_PLUS_CLIENT_ID';
    const SECRET = 'PAYPAL_PLUS_SECRET';

    const IN_CONTEXT_CHECKOUT = 'PAYPAL_IN_CONTEXT_CHECKOUT';

    const HSS_TEMPLATE = 'PAYPAL_HSS_TEMPLATE';
    const HSS_SOLUTION = 'PAYPAL_HSS_SOLUTION';
    const WEB_PROFILE_ID = 'PAYPAL_WEB_PROFILE_ID';
    const UPDATED_COUNTRIES_OK = 'PAYPAL_UPDATED_COUNTRIES_OK';
    const CONFIGURATION_OK = 'PAYPAL_CONFIGURATION_OK';

    const WPS = 1; //Paypal Integral
    const WPRH = 2; //Paypal Integral Evolution
    const EC = 4; //Paypal Option +
    const WPP = 5; //Paypal Plus

    /* Tracking */
    const TRACKING_INTEGRAL_EVOLUTION = '';
    const TRACKING_INTEGRAL = '';
    const TRACKING_OPTION_PLUS = '';
    const TRACKING_PAYPAL_PLUS = '';
    const PAYPAL_HSS_REDIRECTION = 0;
    const PAYPAL_HSS_IFRAME = 1;
    const TRACKING_EXPRESS_CHECKOUT_SEAMLESS = '';

    const TRACKING_CODE = '';
    const SMARTPHONE_TRACKING_CODE = '';
    const TABLET_TRACKING_CODE = '';

    /* Tracking APAC */
    const APAC_TRACKING_INTEGRAL_EVOLUTION = '';
    const APAC_TRACKING_INTEGRAL = '';
    const APAC_TRACKING_OPTION_PLUS = '';
    const APAC_TRACKING_PAYPAL_PLUS = '';
    const APAC_TRACKING_EXPRESS_CHECKOUT_SEAMLESS = '';

    const APAC_TRACKING_CODE = '';
    const APAC_SMARTPHONE_TRACKING_CODE = '';
    const APAC_TABLET_TRACKING_CODE = '';

    const _PAYPAL_LOGO_XML_ = 'logos.xml';
    const _PAYPAL_MODULE_DIRNAME_ = 'paypal';
    const _PAYPAL_TRANSLATIONS_XML_ = 'translations.xml';

    /**
     * PayPal constructor.
     */
    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '4.0.0';
        $this->author = 'Thirty Bees';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayPal');
        $this->description = $this->l('Accepts payments by credit cards (CB, Visa, MasterCard, Amex, Aurore, Cofinoga, 4 stars) with PayPal.');

        $this->controllers = [
            'confirm',
            'expresscheckoutajax',
            'expresscheckoutpayment',
            'expresscheckoutsubmit',
            'incontextcheckoutajax',
            'hostedsolutionconfirm',
            'hostedsolutionsubmit',
            'logintoken',
            'submit',
            'plussubmit',
            'pluscancel',
            'ipn',
        ];

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
     *
     * @param string $paypalVersion
     */
    public function updateConfiguration($paypalVersion)
    {
        \Configuration::updateValue(self::SANDBOX, 0);
        \Configuration::updateValue(self::HEADER, '');
        \Configuration::updateValue(self::BUSINESS, 0);
        \Configuration::updateValue(self::BUSINESS_ACCOUNT, 'paypal@thirtybees.com');
        \Configuration::updateValue(self::API_USER, '');
        \Configuration::updateValue(self::API_PASSWORD, '');
        \Configuration::updateValue(self::API_SIGNATURE, '');
        \Configuration::updateValue(self::EXPRESS_CHECKOUT, 0);
        \Configuration::updateValue(self::CAPTURE, 0);
        \Configuration::updateValue(self::PAYMENT_METHOD, self::WPS);
        \Configuration::updateValue(self::IS_NEW, 1);
        \Configuration::updateValue(self::DEBUG_MODE, 0);
        \Configuration::updateValue(self::SHIPPING_COST, 20.00);
        \Configuration::updateValue(self::VERSION, $paypalVersion);
        \Configuration::updateValue(self::COUNTRY_DEFAULT, (int) \Configuration::get('PS_COUNTRY_DEFAULT'));

        // PayPal v3 configuration
        \Configuration::updateValue(self::EXPRESS_CHECKOUT_SHORTCUT, 1);
        $paypal = new \Paypal();
        $tlsVerifier = new TlsVerifier(true, $paypal);
        \Configuration::updateValue('PAYPAL_VERSION_TLS_CHECKED', $tlsVerifier->getVersion());
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
        \Configuration::deleteByName(self::SANDBOX);
        \Configuration::deleteByName(self::HEADER);
        \Configuration::deleteByName(self::BUSINESS);
        \Configuration::deleteByName(self::API_USER);
        \Configuration::deleteByName(self::API_PASSWORD);
        \Configuration::deleteByName(self::API_SIGNATURE);
        \Configuration::deleteByName(self::BUSINESS_ACCOUNT);
        \Configuration::deleteByName(self::EXPRESS_CHECKOUT);
        \Configuration::deleteByName(self::PAYMENT_METHOD);
        \Configuration::deleteByName(self::TEMPLATE);
        \Configuration::deleteByName(self::CAPTURE);
        \Configuration::deleteByName(self::DEBUG_MODE);
        \Configuration::deleteByName(self::COUNTRY_DEFAULT);
        \Configuration::deleteByName(self::VERSION);

        /* USE PAYPAL LOGIN */
        \Configuration::deleteByName(self::LOGIN);
        \Configuration::deleteByName(self::CLIENT_ID);
        \Configuration::deleteByName(self::SECRET);
        \Configuration::deleteByName(self::LOGIN_TPL);
        /* /USE PAYPAL LOGIN */

        // PayPal v3 configuration
        \Configuration::deleteByName(self::EXPRESS_CHECKOUT_SHORTCUT);
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

    protected function compatibilityCheck()
    {
        if (file_exists(_PS_MODULE_DIR_.'paypalapi/paypalapi.php') && $this->active) {
            $this->warning = $this->l('All features of Paypal API module are included in the new Paypal module. In order to do not have any conflict, please do not use and remove PayPalAPI module.').'<br />';
        }

        /* For 1.4.3 and less compatibility */
        $updateConfig = [
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
            'PS_OS_WS_PAYMENT' => 12,
        ];

        foreach ($updateConfig as $key => $value) {
            if (!\Configuration::get($key) || (int) \Configuration::get($key) < 1) {
                if (defined('_'.$key.'_') && (int) constant('_'.$key.'_')
                    > 0) {
                    \Configuration::updateValue($key, constant('_'.$key.'_'));
                } else {
                    \Configuration::updateValue($key, $value);
                }

            }
        }

    }

    /**
     * @return bool Indicates whether the PayPal API is available
     */
    public function isPayPalAPIAvailable()
    {
        $paymentMethod = \Configuration::get(self::PAYMENT_METHOD);

        if (\Configuration::get(self::CLIENT_ID) && \Configuration::get(self::SECRET)) {
            return true;
        } elseif ($paymentMethod == self::WPRH && \Configuration::get(self::BUSINESS_ACCOUNT)) {
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
        $paymentMethod = \Configuration::get(self::PAYMENT_METHOD);
        $orderProcessType = (int) \Configuration::get('PS_ORDER_PROCESS_TYPE');

        if (\Tools::getValue('paypal_ec_canceled') || $this->context->cart === false) {
            unset($this->context->cookie->express_checkout);
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

            if (($orderProcessType == 1) && ((int) $paymentMethod == self::WPRH) && !$this->context->getMobileDevice()) {
                $this->context->smarty->assign('paypal_order_opc', true);
            } elseif (($orderProcessType == 1) && ((bool) \Tools::getValue('isPaymentStep') == true || $isECS)) {
                $shopUrl = PayPal::getShopDomainSsl(true, true);
                $values = ['fc' => 'module', 'module' => 'paypal', 'controller' => 'confirm', 'get_confirmation' => true];
                $this->context->smarty->assign('paypal_confirmation', $shopUrl.__PS_BASE_URI__.'?'.http_build_query($values));

            }
        }
    }

    protected function checkMobileCredentials()
    {
        $paymentMethod = \Configuration::get(self::PAYMENT_METHOD);

        if (((int) $paymentMethod == self::WPRH) && (
            (!\Configuration::get(self::CLIENT_ID)) &&
            (!\Configuration::get(self::SECRET)))) {
            $this->warning .= $this->l('You must set your PayPal Website Payments Pro Hosted Solution credentials in order to have the mobile theme work correctly.').'<br />';
        }

    }

    protected function checkMobileNeeds()
    {
        $isoCode = \Country::getIsoById((int) \Configuration::get('PS_COUNTRY_DEFAULT'));
        $paypalCountries = ['ES', 'FR', 'PL', 'IT'];

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
        if (\Module::isInstalled('backwardcompatibility')) {
            $backwardModule = \Module::getInstanceByName('backwardcompatibility');
            if (!$backwardModule->active) {
                $this->warning .= $this->l('To work properly the module requires the backward compatibility module enabled').'<br />';
            } elseif ($backwardModule->version < self::BACKWARD_REQUIREMENT) {
                $this->warning .= $this->l('To work properly the module requires at least the backward compatibility module v').self::BACKWARD_REQUIREMENT.'.<br />';
            }

        } else {
            $this->warning .= $this->l('In order to use the module you need to install the backward compatibility.').'<br />';
        }

    }

    public function getContent()
    {
        $this->postProcess();

        if (($idLang = \Language::getIdByIso('EN')) == 0) {
            $englishLanguageId = (int) $this->context->employee->id_lang;
        } else {
            $englishLanguageId = (int) $idLang;
        }

        $this->context->smarty->assign([
            'PayPal_WPS' => (int) self::WPS,
            'PayPal_HSS' => (int) self::WPRH,
            'PayPal_ECS' => (int) self::EC,
            'PayPal_PPP' => (int) self::WPP,
            'PP_errors' => $this->errors,
            'PayPal_logo' => $this->paypalLogos->getLogos(),
            'PayPal_allowed_methods' => $this->getPaymentMethods(),
            'PayPal_country' => \Country::getNameById((int) $englishLanguageId, (int) $this->defaultCountry),
            'PayPal_country_id' => (int) $this->defaultCountry,
            self::BUSINESS => \Configuration::get(self::BUSINESS),
            self::PAYMENT_METHOD => (int) \Configuration::get(self::PAYMENT_METHOD),
            self::BUSINESS_ACCOUNT => \Configuration::get(self::BUSINESS_ACCOUNT),
            self::EXPRESS_CHECKOUT_SHORTCUT => (int) \Configuration::get(self::EXPRESS_CHECKOUT_SHORTCUT),
            self::IN_CONTEXT_CHECKOUT => (int) \Configuration::get(self::IN_CONTEXT_CHECKOUT),
            'use_paypal_in_context' => (int) $this->useInContextCheckout(),
            self::SANDBOX => (int) \Configuration::get(self::SANDBOX),
            self::CAPTURE => (int) \Configuration::get(self::CAPTURE),
            'PayPal_country_default' => (int) $this->defaultCountry,
            'PayPal_change_country_url' => $this->context->link->getAdminLink('AdminCountries', true).'#footer',
            'Countries' => \Country::getCountries($englishLanguageId),
            'One_Page_Checkout' => (int) \Configuration::get('PS_ORDER_PROCESS_TYPE'),
            self::HSS_TEMPLATE => \Configuration::get(self::HSS_TEMPLATE),
            self::HSS_SOLUTION => \Configuration::get(self::HSS_SOLUTION),
            self::LOGIN => (int) \Configuration::get(self::LOGIN),
            self::CLIENT_ID => \Configuration::get(self::CLIENT_ID),
            self::SECRET => \Configuration::get(self::SECRET),
            self::LOGIN_TPL => (int) \Configuration::get(self::LOGIN_TPL),
            self::API_USER => \Configuration::get(self::API_USER),
            self::API_PASSWORD => \Configuration::get(self::API_PASSWORD),
            self::API_SIGNATURE =>  \Configuration::get(self::API_SIGNATURE),
            'default_lang_iso' => \Language::getIsoById($this->context->employee->id_lang),
            'PayPal_plus_client' => \Configuration::get(self::CLIENT_ID),
            'PayPal_plus_secret' => \Configuration::get(self::SECRET),
            self::WEB_PROFILE_ID => (\Configuration::get(self::WEB_PROFILE_ID) != '0') ? \Configuration::get(self::WEB_PROFILE_ID) : 0,
            //'PayPal_version_tls_checked' => $tls_version,
            'Presta_version' => _PS_VERSION_,
        ]);

        $this->getTranslations();

        $output = $this->display(__FILE__, 'views/templates/admin/back_office.tpl');

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
        $smarty->assign([
            'ssl_enabled' => \Configuration::get('PS_SSL_ENABLED'),
            'PAYPAL_SANDBOX' => \Configuration::get(self::SANDBOX),
            'PayPal_in_context_checkout' => \Configuration::get(self::IN_CONTEXT_CHECKOUT),
            'use_paypal_in_context' => (int) $this->useInContextCheckout(),
            'express_checkout_payment_link' => $this->context->link->getModuleLink($this->name, 'expresscheckoutpayment', [], \Tools::usingSecureMode()),
        ]);

        $process = $this->display(__FILE__, 'views/templates/front/paypaljs.tpl');
        if ($this->useInContextCheckout()) {
            $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/api/checkout.js"></script>';
        }

        if ((
            (method_exists($smarty, 'getTemplateVars') && ($smarty->getTemplateVars('page_name')
                == 'authentication' || $smarty->getTemplateVars('page_name') == 'order-opc'))
            || (isset($smarty->_tpl_vars) && ($smarty->_tpl_vars['page_name']
                == 'authentication' || $smarty->_tpl_vars['page_name'] == 'order-opc')))
            &&
            (int) \Configuration::get('PAYPAL_LOGIN') == 1) {
            $this->context->smarty->assign([
                'paypal_locale' => $this->getLocale(),
                \PayPal::CLIENT_ID => \Configuration::get(self::CLIENT_ID),
                \PayPal::LOGIN_TPL => \Configuration::get(self::LOGIN_TPL),
                'PAYPAL_RETURN_LINK' => PayPalLogin::getReturnLink(),
            ]);

            $process .= '<script async defer type="text/javascript" src="//www.paypalobjects.com/js/external/api.js"></script>';
            $process .= '<script async defer type="text/javascript" src="'.Media::getJSPath($this->_path.'views/js/login.js').'"></script>';
            $process .= $this->display(__FILE__, 'views/templates/front/paypal_loginjs.tpl');
        }

        if (\Configuration::get(self::PAYMENT_METHOD) == self::WPP) {
            $process .= '<script src="https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js" type="text/javascript"></script>';
        }

        return $process;
    }

    public function useInContextCheckout()
    {
        return \Configuration::get(self::IN_CONTEXT_CHECKOUT);
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

    public function canBeUsed()
    {
        if (!$this->active) {
            return false;
        }

        //If merchant has not upgraded and payment method is out of country's specs
        if (!\Configuration::get(self::UPDATED_COUNTRIES_OK) && !in_array((int) \Configuration::get(self::PAYMENT_METHOD), $this->getPaymentMethods())) {
            return false;
        }

        return true;
    }

    public function hookProductFooter()
    {
        $content = (!$this->context->getMobileDevice()) ? $this->renderExpressCheckoutButton('product') : null;

        return $content.$this->renderExpressCheckoutForm('product');
    }

    public function hookPayment($params)
    {
        if (!$this->canBeUsed()) {
            return null;
        }

        $useMobile = $this->context->getMobileDevice();

        if ($useMobile) {
            $method = self::EC;
        } else {
            $method = (int) \Configuration::get(self::PAYMENT_METHOD);
        }

        if (isset($this->context->cookie->express_checkout)) {
            $this->redirectToConfirmation();
        }

        $isoLang = [
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
        ];

        $this->context->smarty->assign([
            'logos' => $this->paypalLogos->getLogos(),
            'sandbox_mode' => \Configuration::get(self::SANDBOX),
            'use_mobile' => $useMobile,
            'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code]))
            ? $isoLang[$this->context->language->iso_code] : 'en_US',
        ]);

        if ($method == self::WPRH) {
            $billingAddress = new \Address($this->context->cart->id_address_invoice);
            $deliveryAddress = new \Address($this->context->cart->id_address_delivery);
            $billingAddress->country = new \Country($billingAddress->id_country);
            $deliveryAddress->country = new \Country($deliveryAddress->id_country);
            $billingAddress->state = new \State($billingAddress->id_state);
            $deliveryAddress->state = new \State($deliveryAddress->id_state);

            $cart = $this->context->cart;
            $cartDetails = $cart->getSummaryDetails(null, true);

            if ((int) \Configuration::get('PAYPAL_SANDBOX') == 1) {
                $actionUrl = 'https://securepayments.sandbox.paypal.com/acquiringweb';
            } else {
                $actionUrl = 'https://securepayments.paypal.com/acquiringweb';
            }

            $this->context->smarty->assign([
                'action_url' => $actionUrl,
                'cart' => $cart,
                'cart_details' => $cartDetails,
                'currency' => new \Currency((int) $cart->id_currency),
                'customer' => $this->context->customer,
                'business_account' => \Configuration::get('PAYPAL_BUSINESS_ACCOUNT'),
                'custom' => \json_encode(['id_cart' => $cart->id, 'hash' => sha1(serialize($cart->nbProducts()))]),
                'gift_price' => (float) $this->getGiftWrappingPrice(),
                'billing_address' => $billingAddress,
                'delivery_address' => $deliveryAddress,
                'shipping' => $cartDetails['total_shipping_tax_exc'],
                'subtotal' => $cartDetails['total_price_without_tax'] - $cartDetails['total_shipping_tax_exc'],
                'time' => time(),
                'cancel_return' => $this->context->link->getPageLink('order.php'),
                'notify_url' => $this->context->link->getModuleLink($this->name, 'hostedsolutionsubmit', ['id_cart' => (int) $cart->id], \Tools::usingSecureMode()),
                'return_url' => $this->context->link->getModuleLink($this->name, 'hostedsolutionsubmit', ['id_cart' => (int) $cart->id], \Tools::usingSecureMode()),
                'tracking_code' => $this->getTrackingCode($method),
                'iso_code' => \Tools::strtoupper($this->context->language->iso_code),
                'payment_hss_solution' => \Configuration::get('PAYPAL_HSS_SOLUTION'),
                'payment_hss_template' => \Configuration::get('PAYPAL_HSS_TEMPLATE'),
            ]);
            $this->getTranslations();

            return $this->display(__FILE__, 'integral_evolution_payment.tpl');
        } elseif ($method == self::WPS || $method == self::EC) {
            $this->getTranslations();
            $this->context->smarty->assign([
                'PayPal_integral' => self::WPS,
                'PayPal_express_checkout' => self::EC,
                'PayPal_payment_method' => $method,
                'PayPal_payment_type' => 'payment_cart',
                'PayPal_current_page' => $this->getCurrentUrl(),
                'PayPal_tracking_code' => $this->getTrackingCode($method),
                'PayPal_in_context_checkout' => \Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT'),
                'use_paypal_in_context' => (int) $this->useInContextCheckout(),
            ]);

            return $this->display(__FILE__, 'express_checkout_payment.tpl');
        } elseif ($method == self::WPP) {
            $callApiPaypalPlus = new CallPayPalPlusApi();
            $callApiPaypalPlus->setParams($params);

            $approvalUrl = $callApiPaypalPlus->getApprovalUrl();

            $this->context->smarty->assign([
                'approval_url' => $approvalUrl,
                'language' => $this->getLocalePayPalPlus(),
                'country' => $this->getCountryCode(),
                'mode' => \Configuration::get('PAYPAL_SANDBOX') ? 'sandbox' : 'live',
            ]);

            return $this->display(__FILE__, 'paypal_plus_payment.tpl');
        }

        return null;
    }

    /**
     * Hook for Advanced EU checkout
     *
     * @param array $params
     *
     * @return array|null
     */
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return null;
        }

        if ($this->hookPayment($params) == null) {
            return null;
        }

        $useMobile = $this->context->getMobileDevice();

        if ($useMobile) {
            $method = self::EC;
        } else {
            $method = (int) \Configuration::get(self::PAYMENT_METHOD);
        }

        if (isset($this->context->cookie->express_checkout)) {
            $this->redirectToConfirmation();
        }

        $logos = $this->paypalLogos->getLogos();

        if (isset($logos['LocalPayPalHorizontalSolutionPP']) && $method == self::WPS) {
            $logo = $logos['LocalPayPalHorizontalSolutionPP'];
        } else {
            $logo = $logos['LocalPayPalLogoMedium'];
        }

        $this->context->smarty->assign(
            [
            'express_checkout_payment_link' => $this->context->link->getModuleLink($this->name, 'expresscheckoutpayment', [], \Tools::usingSecureMode()),
            ]
        );

        if ($method == self::WPRH) {
            return [
                'cta_text' => $this->l('Paypal'),
                'logo' => $logo,
                'form' => $this->display(__FILE__, 'integral_evolution_payment_eu.tpl'),
            ];
        } elseif ($method == self::WPS || $method == self::EC) {
            return [
                'cta_text' => $this->l('Paypal'),
                'logo' => $logo,
                'form' => $this->display(__FILE__, 'express_checkout_payment_eu.tpl'),
            ];
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function hookShoppingCartExtra()
    {
        if (!$this->active
            || (((int) \Configuration::get(self::PAYMENT_METHOD) == self::WPRH) && !$this->context->getMobileDevice())
            || !\Configuration::get(self::EXPRESS_CHECKOUT_SHORTCUT)
            || !in_array(self::EC, $this->getPaymentMethods())
            || isset($this->context->cookie->express_checkout)) {
            return null;
        }

        $paypalLogos = $this->paypalLogos->getLogos();

        $this->context->smarty->assign([
            'PayPal_payment_type' => 'cart',
            'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton']) ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'PayPal_lang_code' => $this->context->language->iso_code ? $this->context->language->iso_code : 'en_US',
            'PayPal_tracking_code' => $this->getTrackingCode((int) \Configuration::get('PAYPAL_PAYMENT_METHOD')),
            'include_form' => true,
            'template_dir' => dirname(__FILE__).'/views/templates/hook/',
            'express_checkout_payment_link' => $this->context->link->getModuleLink($this->name, 'expresscheckoutpayment', [], \Tools::usingSecureMode()),
        ]);

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * @return null|string
     */
    public function hookPaymentReturn()
    {
        if (!$this->active) {
            return null;
        }

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    /**
     * @return string
     */
    public function hookRightColumn()
    {
        $this->context->smarty->assign('logo', $this->paypalLogos->getCardsLogo(true));

        return $this->display(__FILE__, 'column.tpl');
    }

    /**
     * @return string
     */
    public function hookLeftColumn()
    {
        return $this->hookRightColumn();
    }

    /**
     * @param $params
     *
     * @return bool|null
     */
    public function hookBackBeforePayment($params)
    {
        if (!$this->active) {
            return null;
        }

        /* Only execute if you use PayPal API for payment */
        if (((int) \Configuration::get('PAYPAL_PAYMENT_METHOD') != self::WPRH) && $this->isPayPalAPIAvailable()) {
            if ($params['module'] != $this->name || !$this->context->cookie->paypal_token
                || !$this->context->cookie->paypal_payer_id) {
                return false;
            }

            \Tools::redirect($this->context->link->getModuleLink($this->name, 'plussubmit', [], \Tools::usingSecureMode()).'?confirm=1&token='.$this->context->cookie->paypal_token.'&payerID='.$this->context->cookie->paypal_payer_id);
        }

        return null;
    }

    /**
     *
     */
    public function setPayPalAsConfigured()
    {
        \Configuration::updateValue(self::CONFIGURATION_OK, true);
    }

    /**
     * @param $params
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

                            $this->doCapture($params['id_order'], $captureAmount, $complete);
                        }
                    }
                }
            }
        } elseif (\Tools::isSubmit('submitPayPalRefund')) {
            $this->doFullRefund($params['id_order']);
        }

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

            $this->context->smarty->assign(
                [
                    'authorization' => (int) \Configuration::get('PAYPAL_OS_AUTHORIZATION'),
                    'base_url' => _PS_BASE_URL_.__PS_BASE_URI__,
                    'module_name' => $this->name,
                    'order_state' => $orderState,
                    'params' => $params,
                    'id_currency' => $currency->getSign(),
                    'rest_to_capture' => \Tools::ps_round($cpt->getRestToPaid($order), '6'),
                    'list_captures' => $cpt->getListCaptured(),
                    'ps_version' => _PS_VERSION_,
                ]
            );

            foreach ($adminTemplates as $adminTemplate) {
                $this->html .= $this->display(__FILE__, 'views/templates/admin/admin_order/'.$adminTemplate.'.tpl');
                $this->postProcess();
                $this->html .= '</fieldset>';
            }
        }

        return $this->html;
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
            || \Tools::isSubmit('generateCreditSlip')) {
            return false;
        } elseif ($params['order']->module != $this->name || !($order = $params['order'])
            || !\Validate::isLoadedObject($order)) {
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
        $refund = $this->doRefund($paypalOrder['id_transaction'], (int) $order->id, $amount);
        $this->formatMessage($refund, $message);
        $this->addNewPrivateMessage((int) $order->id, $message);

        return null;
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
            (strcmp(\Tools::getValue('module_name'), $this->name) === 0)) {
            $this->context->controller->addJquery();
            $this->context->controller->addJQueryPlugin('fancybox');
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/paypal.css');

            $this->context->smarty->assign(
                [
                'PayPal_module_dir' => _MODULE_DIR_.$this->name,
                'PayPal_WPS' => (int) self::WPS,
                'PayPal_HSS' => (int) self::WPRH,
                'PayPal_ECS' => (int) self::EC,
                'PayPal_PPP' => (int) self::WPP,
                ]
            );

            return (isset($output) ? $output : null).$this->display(__FILE__, 'views/templates/admin/header.tpl');
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

        if (!in_array(self::EC, $this->getPaymentMethods()) || (((int) \Configuration::get('PAYPAL_BUSINESS')
            == 1) &&
            (int) \Configuration::get('PAYPAL_PAYMENT_METHOD') == self::WPRH) && !$this->context->getMobileDevice()) {
            return null;
        }

        $paypalLogos = $this->paypalLogos->getLogos();
        $isoLang = [
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
        ];

        $this->context->smarty->assign(
            [
            'use_mobile' => (bool) $this->context->getMobileDevice(),
            'PayPal_payment_type' => $type,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'PayPal_lang_code' => (isset($isoLang[$this->context->language->iso_code])) ? $isoLang[$this->context->language->iso_code] : 'en_US',
            'PayPal_tracking_code' => $this->getTrackingCode((int) \Configuration::get('PAYPAL_PAYMENT_METHOD')),
            'paypal_express_checkout_shortcut_logo' => isset($paypalLogos['ExpressCheckoutShortcutButton']) ? $paypalLogos['ExpressCheckoutShortcutButton'] : false,
            'express_checkout_payment_link' => $this->context->link->getModuleLink($this->name, 'expresscheckoutpayment', [], \Tools::usingSecureMode()),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_button.tpl');
    }

    /**
     * @param string $type
     *
     * @return null|string
     */
    public function renderExpressCheckoutForm($type)
    {
        if ((!\Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT') && !$this->context->getMobileDevice())
            || !in_array(self::EC, $this->getPaymentMethods()) ||
            (((int) \Configuration::get('PAYPAL_BUSINESS') == 1) && ((int) \Configuration::get('PAYPAL_PAYMENT_METHOD') == self::WPRH) && !$this->context->getMobileDevice())) {
            return null;
        }

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
            'PayPal_payment_type' => $type,
            'PayPal_current_page' => $this->getCurrentUrl(),
            'id_product_attribute_ecs' => $idProductAttribute,
            'product_minimal_quantity' => $minimalQuantity,
            'PayPal_tracking_code' => $this->getTrackingCode((int) \Configuration::get(self::PAYMENT_METHOD)),
            'express_checkout_payment_link' => $this->context->link->getModuleLink('paypal', 'expresscheckoutpayment', [], \Tools::usingSecureMode()),
            ]
        );

        return $this->display(__FILE__, 'express_checkout_shortcut_form.tpl');
    }

    /**
     * @return bool
     */
    public function isCountryAPAC()
    {
        return true;
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
        if (\Configuration::get('PAYPAL_LOGIN')) {
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
        if ($method == self::WPRH) {
            return $isApacCountry ? self::APAC_TRACKING_INTEGRAL_EVOLUTION : self::TRACKING_INTEGRAL_EVOLUTION;
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
     * @return string PayPal base URL
     */
    public function getPayPalURL()
    {
        return 'www'.(\Configuration::get('PAYPAL_SANDBOX') ? '.sandbox' : '').'.paypal.com';
    }

    /**
     * @return string PayPal Hosted Solution URL
     */
    public function getPaypalIntegralEvolutionUrl()
    {
        if (\Configuration::get('PAYPAL_SANDBOX')) {
            return 'https://'.$this->getPayPalURL().'/cgi-bin/acquiringweb';
        }

        return 'https://securepayments.paypal.com/acquiringweb?cmd=_hosted-payment';
    }

    /**
     * @return string PayPal Website Payments Standard or Express Checkout URL
     */
    public function getPaypalStandardUrl()
    {
        return 'https://'.$this->getPayPalURL().'/cgi-bin/webscr';
    }

    /**
     * Get PayPal API URL
     *
     * @return string
     */
    public function getAPIURL()
    {
        return 'api-3t'.(\Configuration::get('PAYPAL_SANDBOX') ? '.sandbox' : '').'.paypal.com';
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
     * @return bool|mixed
     */
    public function getPaymentMethods()
    {
        if (\Configuration::get(self::UPDATED_COUNTRIES_OK)) {
            return AvailablePaymentMethods::authenticatePaymentMethodByLang(\Tools::strtoupper($this->context->language->iso_code));
        } else {
            $country = new \Country((int) \Configuration::get('PS_COUNTRY_DEFAULT'));

            return AvailablePaymentMethods::authenticatePaymentMethodByCountry($country->iso_code);
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
     * @param      $message
     * @param bool $log
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

        return $order && $order['payment_method'] != self::WPRH && $order['payment_status']
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

        return $result && $result['payment_method'] != self::WPRH && $result['payment_status'] == 'Pending_capture';
    }

    /**
     * @return bool
     */
    protected function preProcess()
    {
        if (\Tools::isSubmit('submitPaypal')) {
            $business = \Tools::getValue(self::BUSINESS) !== false ? (int) \Tools::getValue(self::BUSINESS) : false;
            $paymentMethod = \Tools::getValue(self::PAYMENT_METHOD) !== false ? (int) Tools::getValue(self::PAYMENT_METHOD) : false;
            $paymentCapture = \Tools::getValue(self::CAPTURE) !== false ? (int) \Tools::getValue(self::CAPTURE) : false;
            $sandboxMode = \Tools::getValue(self::SANDBOX) !== false ? (int) \Tools::getValue(self::SANDBOX) : false;

            if ($this->defaultCountry === false || $sandboxMode === false || $paymentCapture === false || $business === false || $paymentMethod === false) {
                $this->errors[] = $this->l('Some fields are empty.');
            } elseif (!$business) {
                $this->errors[] = $this->l('Credentials fields cannot be empty');
            } elseif ($business) {
                if (($paymentMethod == self::WPS || $paymentMethod == self::EC) && (!\Tools::getValue(self::API_USER)
                    || !\Tools::getValue(self::API_PASSWORD) || !\Tools::getValue(self::API_SIGNATURE))) {
                    $this->errors[] = $this->l('Credentials fields cannot be empty');
                }

                if ($paymentMethod == self::WPP && (\Tools::getValue(self::WEB_PROFILE_ID)
                    != 0 && (!\Tools::getValue(self::CLIENT_ID) && !\Tools::getValue(self::SECRET)))) {
                    $this->errors[] = $this->l('Credentials fields cannot be empty');
                }

                if ($paymentMethod == self::WPRH && !\Tools::getValue(self::BUSINESS_ACCOUNT)) {
                    $this->errors[] = $this->l('Business e-mail field cannot be empty');
                }

            }
        }

        return !count($this->errors);
    }

    /**
     * Post process
     */
    protected function postProcess()
    {
        if (\Tools::getValue('old_partners')) {
            \Configuration::updateValue(self::UPDATED_COUNTRIES_OK, 1);
        }

        if (\Tools::isSubmit('submitTlsVerificator')) {
            $tlsVe = new TlsVerifier(true, $this);
            if ($tlsVe->getVersion() == '1.2') {
                $tlsVerificator = 1;
            } else {
                $tlsVerificator = 0;
            }
        } else {
            $tlsVerificator = -1;
        }

        $this->context->smarty->assign('PayPal_tls_verificator', $tlsVerificator);

        if (\Tools::isSubmit('submitPaypal')) {
            if (\Tools::getValue('paypal_country_only')) {
                \Configuration::updateValue('PAYPAL_COUNTRY_DEFAULT', (int) \Tools::getValue('paypal_country_only'));
            } elseif ($this->preProcess()) {
                \Configuration::updateValue(self::BUSINESS, (int) \Tools::getValue(self::BUSINESS));
                \Configuration::updateValue(self::PAYMENT_METHOD, (int) \Tools::getValue(self::PAYMENT_METHOD));
                \Configuration::updateValue(self::BUSINESS_ACCOUNT, trim(\Tools::getValue(self::BUSINESS_ACCOUNT)));
                \Configuration::updateValue(self::API_USER, trim(\Tools::getValue(self::API_USER)));
                \Configuration::updateValue(self::API_PASSWORD, trim(\Tools::getValue(self::API_PASSWORD)));
                \Configuration::updateValue(self::API_SIGNATURE, trim(\Tools::getValue(self::API_SIGNATURE)));
                \Configuration::updateValue(self::EXPRESS_CHECKOUT_SHORTCUT, (int) \Tools::getValue(self::EXPRESS_CHECKOUT_SHORTCUT));
                \Configuration::updateValue(self::SANDBOX, (int) \Tools::getValue(self::SANDBOX));
                \Configuration::updateValue(self::CAPTURE, (int) \Tools::getValue(self::CAPTURE));

                /* USE PAYPAL LOGIN */
                \Configuration::updateValue(self::LOGIN, (int) \Tools::getValue(self::LOGIN));
                \Configuration::updateValue(self::CLIENT_ID, \Tools::getValue(self::CLIENT_ID));
                \Configuration::updateValue(self::SECRET, \Tools::getValue(self::SECRET));
                \Configuration::updateValue(self::LOGIN_TPL, (int) \Tools::getValue(self::LOGIN_TPL));

                /* USE PAYPAL PLUS */
                if ((int) \Tools::getValue('paypal_payment_method') == 5) {
                    \Configuration::updateValue(self::CLIENT_ID, \Tools::getValue(self::CLIENT_ID));
                    \Configuration::updateValue(self::SECRET, \Tools::getValue(self::SECRET));

                    if ((int) Tools::getValue('paypalplus_webprofile') == 1) {
                        $apiPaypalPlus = new PayPalRestApi();
                        $idWebProfile = $apiPaypalPlus->getWebProfile();

                        if ($idWebProfile) {
                            \Configuration::updateValue(self::WEB_PROFILE_ID, $idWebProfile);
                        } else {
                            \Configuration::updateValue(self::WEB_PROFILE_ID, 0);
                        }
                    }
                }
                /* IS IN_CONTEXT_CHECKOUT ENABLED */
                if ((int) \Tools::getValue(self::PAYMENT_METHOD) != 2) {
                    \Configuration::updateValue(self::IN_CONTEXT_CHECKOUT, (int) \Tools::getValue(self::IN_CONTEXT_CHECKOUT));
                } else {
                    \Configuration::updateValue(self::IN_CONTEXT_CHECKOUT, 0);
                }

                /* /IS IN_CONTEXT_CHECKOUT ENABLED */

                //EXPRESS CHECKOUT TEMPLATE
                \Configuration::updateValue(self::HSS_SOLUTION, (int) \Tools::getValue(self::HSS_SOLUTION));
                if (\Tools::getValue(self::HSS_SOLUTION) == self::PAYPAL_HSS_IFRAME) {
                    \Configuration::updateValue(self::HSS_TEMPLATE, 'D');
                } else {
                    \Configuration::updateValue(self::HSS_TEMPLATE, \Tools::getValue(self::HSS_TEMPLATE));
                }

                $this->context->smarty->assign('PayPal_save_success', true);
            } else {
                $this->html = $this->displayError(implode('<br />', $this->errors)); // Not displayed at this time
                $this->context->smarty->assign('PayPal_save_failure', true);
            }
        }

        return $this->loadLangDefault();
    }

    /**
     * @param string $idTransaction
     * @param int    $idOrder
     * @param bool   $amt
     *
     * @return array
     */
    protected function doRefund($idTransaction, $idOrder, $amt = false)
    {
        if (!$this->isPayPalAPIAvailable()) {
            die(\Tools::displayError('Fatal Error: no API Credentials are available'));
        } elseif (!$idTransaction) {
            die(\Tools::displayError('Fatal Error: id_transaction is null'));
        }

        $paymentMethod = \Configuration::get('PAYPAL_PAYMENT_METHOD');

        if ($paymentMethod != self::WPP) {
            if (!$amt) {
                $params = ['TRANSACTIONID' => $idTransaction, 'REFUNDTYPE' => 'Full'];
            } else {
                $sql = new \DbQuery();
                $sql->select('c.`iso_code`');
                $sql->from('orders', 'o');
                $sql->leftJoin('currency', 'c', 'o.`id_currency` = c.`id_currency`');
                $sql->where('o.`id_order` = '.(int) $idOrder);

                $isoCurrency = \Db::getInstance()->getValue($sql);

                $params = [
                    'TRANSACTIONID' => $idTransaction,
                    'REFUNDTYPE' => 'Partial',
                    'AMT' => (float) $amt,
                    'CURRENCYCODE' => \Tools::strtoupper($isoCurrency),
                ];
            }

            $paypalLib = new PayPalLib();

            return $paypalLib->makeCall(
                $this->getAPIURL(),
                $this->getAPIScript(),
                'RefundTransaction',
                '&'.http_build_query($params, '', '&')
            );
        } else {
            if (!$amt) {
                $params = new \stdClass();
            } else {
                $sql = new \DbQuery();
                $sql->select('po.*');
                $sql->from('paypal_order', 'po');
                $sql->where('po.`id_transaction` = \''.pSQL($idTransaction).'\'');
                $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
                $result = current($result);

                $amount = new \stdClass();
                $amount->total = $amt;
                $amount->currency = $result['currency'];

                $params = new \stdClass();
                $params->amount = $amount;
            }

            $callApiPaypalPlus = new CallPayPalPlusApi();

            return json_decode($callApiPaypalPlus->executeRefund($idTransaction, $params));
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
     * Do a full refund
     *
     * @param int $idOrder Order ID
     *
     * @return bool Indicates whether the full refund was successful
     */
    protected function doFullRefund($idOrder)
    {
        $paypalOrder = PayPalOrder::getOrderById((int) $idOrder);
        if (!$this->isPayPalAPIAvailable() || !$paypalOrder) {
            return false;
        }

        $order = new \Order((int) $idOrder);
        if (!\Validate::isLoadedObject($order)) {
            return false;
        }

        $products = $order->getProducts();
        $currency = new \Currency((int) $order->id_currency);
        if (!\Validate::isLoadedObject($currency)) {
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
        if (\Tools::ps_round($order->total_paid_real, $decimals) == \Tools::ps_round($amt, $decimals)) {
            $response = $this->doRefund($paypalOrder['id_transaction'], $idOrder);
        } else {
            $response = $this->doRefund($paypalOrder['id_transaction'], $idOrder, (float) ($amt));
        }

        $message = $this->l('Refund operation result:')." \r\n";
        foreach ($response as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $message .= $key.': '.\json_encode($value)." \r\n";
            } else {
                $message .= $key.': '.$value." \r\n";
            }
        }
        if ((array_key_exists('ACK', $response) && $response['ACK'] == 'Success'
            && $response['REFUNDTRANSACTIONID'] != '') || (isset($response->state)
            && $response->state == 'completed')) {
            $message .= $this->l('PayPal refund successful!');
            if (!\Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'paypal_order` SET `payment_status` = \'Refunded\' WHERE `id_order` = '.(int) $idOrder)) {
                die(\Tools::displayError('Error when updating PayPal database'));
            }

            $history = new \OrderHistory();
            $history->id_order = (int) $idOrder;
            $history->changeIdOrderState((int) \Configuration::get('PS_OS_REFUND'), $history->id_order);
            $history->addWithemail();
            $history->save();
        } else {
            $message .= $this->l('Transaction error!');
        }

        $this->addNewPrivateMessage((int) $idOrder, $message);

        \Tools::redirect($_SERVER['HTTP_REFERER']);

        return null;
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
        $paypalOrder = PayPalOrder::getOrderById((int) $idOrder);
        if (!$this->isPayPalAPIAvailable() || !$paypalOrder) {
            return false;
        }

        $order = new \Order((int) $idOrder);
        $currency = new \Currency((int) $order->id_currency);

        if (!$captureAmount) {
            $captureAmount = (float) $order->total_paid;
        }

        $complete = 'Complete';
        if (!$isComplete) {
            $complete = 'NotComplete';
        }

        $paypalLib = new PayPalLib();
        $response = $paypalLib->makeCall(
            $this->getAPIURL(),
            $this->getAPIScript(),
            'DoCapture',
            '&'.http_build_query(
                [
                    'AMT' => $captureAmount,
                    'AUTHORIZATIONID' => $paypalOrder['id_transaction'],
                    'CURRENCYCODE' => $currency->iso_code,
                    'COMPLETETYPE' => $complete,
                ],
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
                    if (!\Db::getInstance()->execute(
                        'UPDATE `'._DB_PREFIX_.'paypal_order`
                        SET `capture` = 0, `payment_status` = \''.pSQL($response['PAYMENTSTATUS']).'\', `id_transaction` = \''.pSQL($response['TRANSACTIONID']).'\'
                        WHERE `id_order` = '.(int) $idOrder
                    )
                    ) {
                        die(\Tools::displayError('Error when updating PayPal database'));
                    }

                    $orderHistory = new \OrderHistory();
                    $orderHistory->id_order = (int) $idOrder;
                    $orderHistory->changeIdOrderState(\Configuration::get('PS_OS_WS_PAYMENT'), $order);

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

        \Tools::redirect($_SERVER['HTTP_REFERER']);

        return null;
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

    protected function warningsCheck()
    {
        if (\Configuration::get(self::PAYMENT_METHOD) == self::WPRH && \Configuration::get(self::BUSINESS_ACCOUNT) == 'paypal@thirtybees.com') {
            $this->warning = $this->l('You are currently using the default PayPal e-mail address, please enter your own e-mail address.').'<br />';
        }

        /* Check preactivation warning */
        if (\Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING')) {
            $this->warning .= (!empty($this->warning)) ? ', ' : \Configuration::get('PS_PREACTIVATION_PAYPAL_WARNING').'<br />';
        }

        if (!function_exists('curl_init')) {
            $this->warning .= $this->l('In order to use your module, please activate cURL (PHP extension)');
        }

    }

    /**
     * Load default language
     */
    protected function loadLangDefault()
    {
        if (\Configuration::get(self::UPDATED_COUNTRIES_OK)) {
            $this->iso_code = \Tools::strtoupper($this->context->language->iso_code);
            if ($this->iso_code == 'EN') {
                $isoCode = 'GB';
            } else {
                $isoCode = $this->iso_code;
            }
            $this->defaultCountry = \Country::getByIso($isoCode);
        } else {
            $this->defaultCountry = (int) \Configuration::get('PS_COUNTRY_DEFAULT');
            $country = new \Country($this->defaultCountry);
            $this->iso_code = \Tools::strtoupper($country->iso_code);
        }
    }

    /**
     * Add PayPal customer
     *
     * @param int    $idCustomer
     * @param string $email
     *
     * @return bool
     */
    public static function addPayPalCustomer($idCustomer, $email)
    {
        if (!PayPal::getPayPalEmailByIdCustomer($idCustomer)) {
            \Db::getInstance()->insert(
                'paypal_customer',
                [
                    'id_customer' => (int) $idCustomer,
                    'paypal_email' => pSQL($email),
                ]
            );

            return \Db::getInstance()->Insert_ID();
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

    /**
     * Get Shop domain SSL
     *
     * @param bool $http
     * @param bool $entities
     *
     * @return string
     */
    public static function getShopDomainSsl($http = false, $entities = false)
    {
        if (method_exists('\Tools', 'getShopDomainSsl')) {
            return \Tools::getShopDomainSsl($http, $entities);
        } else {
            if (!($domain = \Configuration::get('PS_SHOP_DOMAIN_SSL'))) {
                $domain = \Tools::getHttpHost();
            }

            if ($entities) {
                $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
            }

            if ($http) {
                $domain = (\Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$domain;
            }

            return $domain;
        }
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
     */
    public function validateOrder($idCart, $idOrderState, $amountPaid, $paymentMethod = 'Unknown', $message = null, $transaction = [], $currencySpecial = null, $dontTouchAmount = false, $secureKey = false, Shop $shop = null)
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
     * Redirect to confirmation page
     */
    public function redirectToConfirmation()
    {
        // Check if user went through the payment preparation detail and completed it
        $detail = unserialize($this->context->cookie->express_checkout);

        if (!empty($detail['payer_id']) && !empty($detail['token'])) {
            $values = ['get_confirmation' => true];
            \Tools::redirect(\Context::getContext()->link->getModuleLink('paypal', 'confirm', $values));
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
            return ($_SERVER['HTTPS'] == 1 || \Tools::strtolower($_SERVER['HTTPS']) == 'on');
        }

        // $_SERVER['SSL'] exists only in some specific configuration
        if (isset($_SERVER['SSL'])) {
            return ($_SERVER['SSL'] == 1 || \Tools::strtolower($_SERVER['SSL']) == 'on');
        }

        return false;
    }

    /**
     * Get current URL
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        $protocolLink = $this->usingSecureMode() ? 'https://' : 'http://';
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');

        if (($pos !== false) && ($pos >= 0)) {
            $request = \Tools::substr($request, 0, $pos);
        }

        $params = urlencode($_SERVER['QUERY_STRING']);

        return $protocolLink.\Tools::getShopDomainSsl().$request.'?'.$params;
    }

    /**
     * Assign cart summary
     */
    public function assignCartSummary()
    {
        $currency = new \Currency((int) $this->context->cart->id_currency);

        $this->context->smarty->assign([
            'total' => \Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
            'logos' => $this->paypalLogos->getLogos(),
            'use_mobile' => (bool) $this->context->getMobileDevice(),
            'address_shipping' => new \Address($this->context->cart->id_address_delivery),
            'address_billing' => new \Address($this->context->cart->id_address_invoice),
            'cart' => $this->context->cart,
            'patternRules' => ['avoid' => []],
            'cart_image_size' => 'cart_default',
            'useStyle14' => false,
            'useStyle15' => false,
        ]);

        $this->context->smarty->assign([
            'paypal_cart_summary' => $this->display(__FILE__, 'views/templates/hook/paypal_cart_summary.tpl'),
        ]);
    }
}
