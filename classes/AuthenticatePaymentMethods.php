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

class AuthenticatePaymentMethods
{
    /**
     * @param $isoCode
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaymentMethodsByIsoCode($isoCode)
    {
        // WPS -> Web Payment Standard
        // HSS -> Web Payment Pro / Integral Evolution
        // ECS -> Express Checkout Solution
        // PPP -> PAYPAL PLUS
        // PVZ -> Braintree / Payment VZero

        $paymentMethod = array(
            // EUROPE
            'BE'=>array(\PayPal::WPS, \PayPal::ECS),
            'CZ'=>array(\PayPal::WPS, \PayPal::ECS),
            'DE'=>array(\PayPal::WPS, \PayPal::ECS, \PayPal::PPP),
            'ES'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'FR'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'IT'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'VA'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'NL'=>array(\PayPal::WPS, \PayPal::ECS),
            'AN'=>array(\PayPal::WPS, \PayPal::ECS), //Netherlands Antilles
            'PL'=>array(\PayPal::WPS, \PayPal::ECS),
            'PT'=>array(\PayPal::WPS, \PayPal::ECS),
            'AT'=>array(\PayPal::WPS, \PayPal::ECS),
            'CH'=>array(\PayPal::WPS, \PayPal::ECS),
            'DK'=>array(\PayPal::WPS, \PayPal::ECS),
            'FI'=>array(\PayPal::WPS, \PayPal::ECS),
            'GR'=>array(\PayPal::WPS, \PayPal::ECS),
            'HU'=>array(\PayPal::WPS, \PayPal::ECS),
            'LU'=>array(\PayPal::WPS, \PayPal::ECS),
            'NO'=>array(\PayPal::WPS, \PayPal::ECS),
            'RO'=>array(\PayPal::WPS, \PayPal::ECS),
            'RU'=>array(\PayPal::WPS, \PayPal::ECS),
            'SE'=>array(\PayPal::WPS, \PayPal::ECS),
            'SK'=>array(\PayPal::WPS, \PayPal::ECS),
            'UA'=>array(\PayPal::WPS, \PayPal::ECS),
            'TR'=>array(\PayPal::WPS, \PayPal::ECS),
            'SI'=>array(\PayPal::WPS, \PayPal::ECS),
            'GB'=>array(\PayPal::WPS, \PayPal::ECS),

            //ASIE
            'CN'=>array(\PayPal::WPS, \PayPal::ECS),
            'MO'=>array(\PayPal::WPS, \PayPal::ECS),
            'HK'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'JP'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'MY'=>array(\PayPal::WPS, \PayPal::ECS),
            'BN'=>array(\PayPal::WPS, \PayPal::ECS),
            'ID'=>array(\PayPal::WPS, \PayPal::ECS),
            'KH'=>array(\PayPal::WPS, \PayPal::ECS),
            'LA'=>array(\PayPal::WPS, \PayPal::ECS),
            'PH'=>array(\PayPal::WPS, \PayPal::ECS),
            'TL'=>array(\PayPal::WPS, \PayPal::ECS),
            'VN'=>array(\PayPal::WPS, \PayPal::ECS),
            'IL'=>array(\PayPal::WPS, \PayPal::ECS), //Israel
            'SG'=>array(\PayPal::WPS, \PayPal::ECS),
            'TH'=>array(\PayPal::WPS, \PayPal::ECS),
            'TW'=>array(\PayPal::WPS, \PayPal::ECS),

            // OCEANIE
            'NZ'=>array(\PayPal::WPS, \PayPal::ECS),
            'PW'=>array(\PayPal::WPS, \PayPal::ECS),
            'AU'=>array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),

            // AMERIQUE LATINE
            'BR'=>array(\PayPal::WPS, \PayPal::ECS),
            'MX'=>array(\PayPal::WPS, \PayPal::ECS),
            'CL'=>array(\PayPal::WPS, \PayPal::ECS),
            'CO'=>array(\PayPal::WPS, \PayPal::ECS),
            'PE'=>array(\PayPal::WPS, \PayPal::ECS),

            //AFRIQUE
            'SL'=>array(\PayPal::WPS, \PayPal::ECS),
            'SN'=>array(\PayPal::WPS, \PayPal::ECS),
        );

        return isset($paymentMethod[$isoCode]) ? $paymentMethod[$isoCode] : false;
    }

    /**
     * @param $isoCode
     *
     * @return bool|int|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getCountryDependencyRetroCompatibilite($isoCode)
    {
        $localizations = array(
            'AU' => array('AU'), 'BE' => array('BE'), 'CN' => array('CN', 'MO'),
            'CZ' => array('CZ'), 'DE' => array('DE'), 'ES' => array('ES'),
            'FR' => array('FR'), 'GB' => array('GB'), 'HK' => array('HK'), 'IL' => array(
                'IL'), 'IN' => array('IN'), 'IT' => array('IT', 'VA'),
            'JP' => array('JP'), 'MY' => array('MY'), 'NL' => array('AN', 'NL'),
            'NZ' => array('NZ'), 'PL' => array('PL'), 'PT' => array('PT', 'BR'),
            'RA' => array('AF', 'AS', 'BD', 'BN', 'BT', 'CC', 'CK', 'CX', 'FM', 'HM',
                'ID', 'KH', 'KI', 'KN', 'KP', 'KR', 'KZ', 'LA', 'LK', 'MH',
                'MM', 'MN', 'MV', 'MX', 'NF', 'NP', 'NU', 'OM', 'PG', 'PH', 'PW',
                'QA', 'SB', 'TJ', 'TK', 'TL', 'TM', 'TO', 'TV', 'TZ', 'UZ', 'VN',
                'VU', 'WF', 'WS'),
            'RE' => array('IE', 'ZA', 'GP', 'GG', 'JE', 'MC', 'MS', 'MP', 'PA', 'PY',
                'PE', 'PN', 'PR', 'LC', 'SR', 'TT',
                'UY', 'VE', 'VI', 'AG', 'AR', 'CA', 'BO', 'BS', 'BB', 'BZ', 'CL',
                'CO', 'CR', 'CU', 'SV', 'GD', 'GT', 'HN', 'JM', 'NI', 'AD', 'AE',
                'AI', 'AL', 'AM', 'AO', 'AQ', 'AT', 'AW', 'AX', 'AZ', 'BA', 'BF',
                'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BV', 'BW', 'BY', 'CD', 'CF',
                'CG',
                'CH', 'CI', 'CM', 'CV', 'CY', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC',
                'EE', 'EG', 'EH', 'ER', 'ET', 'FI', 'FJ', 'FK', 'FO', 'GA', 'GE',
                'GF',
                'GH', 'GI', 'GL', 'GM', 'GN', 'GQ', 'GR', 'GS', 'GU', 'GW', 'GY',
                'HR', 'HT', 'HU', 'IM', 'IO', 'IQ', 'IR', 'IS', 'JO', 'KE', 'KM',
                'KW',
                'KY', 'LB', 'LI', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MD',
                'ME', 'MF', 'MG', 'MK', 'ML', 'MQ', 'MR', 'MT', 'MU', 'MW', 'MZ',
                'NA',
                'NC', 'NE', 'NG', 'NO', 'NR', 'PF', 'PK', 'PM', 'PS', 'RE', 'RO',
                'RS', 'RU', 'RW', 'SA', 'SC', 'SD', 'SE', 'SI', 'SJ', 'SK', 'SL',
                'SM', 'SN', 'SO', 'ST', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TN',
                'UA', 'UG', 'VC', 'VG', 'YE', 'YT', 'ZM', 'ZW'),
            'SG' => array('SG'), 'TH' => array('TH'), 'TR' => array('TR'), 'TW' => array(
                'TW'), 'US' => array('US'));

        foreach ($localizations as $key => $value) {
            if (in_array($isoCode, $value)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @param $isoCode
     *
     * @return mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaymentMethodsRetroCompatibilite($isoCode)
    {
        // WPS -> Web Payment Standard
        // HSS -> Web Payment Pro / Integral Evolution
        // ECS -> Express Checkout Solution
        // PPP -> PAYPAL PLUS

        $paymentMethod = array(
            'AU' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'BE' => array(\PayPal::WPS, \PayPal::ECS),
            'CN' => array(\PayPal::WPS, \PayPal::ECS),
            'CZ' => array(),
            'DE' => array(\PayPal::WPS, \PayPal::ECS, \PayPal::PPP),
            'ES' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'FR' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'GB' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'HK' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'IL' => array(\PayPal::WPS, \PayPal::ECS),
            'IN' => array(\PayPal::WPS, \PayPal::ECS),
            'IT' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'JP' => array(\PayPal::WPS, \PayPal::HSS, \PayPal::ECS),
            'MY' => array(\PayPal::WPS, \PayPal::ECS),
            'NL' => array(\PayPal::WPS, \PayPal::ECS),
            'NZ' => array(\PayPal::WPS, \PayPal::ECS),
            'PL' => array(\PayPal::WPS, \PayPal::ECS),
            'PT' => array(\PayPal::WPS, \PayPal::ECS),
            'RA' => array(\PayPal::WPS, \PayPal::ECS),
            'RE' => array(\PayPal::WPS, \PayPal::ECS),
            'SG' => array(\PayPal::WPS, \PayPal::ECS),
            'TH' => array(\PayPal::WPS, \PayPal::ECS),
            'TR' => array(\PayPal::WPS, \PayPal::ECS),
            'TW' => array(\PayPal::WPS, \PayPal::ECS),
            'US' => array(\PayPal::WPS, \PayPal::ECS),
            'ZA' => array(\PayPal::WPS, \PayPal::ECS));

        return isset($paymentMethod[$isoCode]) ? $paymentMethod[$isoCode] : $paymentMethod['GB'];
    }

    /**
     * @param $isoCode
     *
     * @return mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function authenticatePaymentMethodByLang($isoCode)
    {
        return self::getPaymentMethodsRetroCompatibilite(self::getCountryDependencyRetroCompatibilite($isoCode));
    }

    /**
     * @param $isoCode
     *
     * @return bool|mixed
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function authenticatePaymentMethodByCountry($isoCode)
    {
        return self::getPaymentMethodsByIsoCode($isoCode);
    }
}
