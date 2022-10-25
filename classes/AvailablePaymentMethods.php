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

use PayPal;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class AuthenticatePaymentMethods
 *
 * @package PayPalModule
 */
class AvailablePaymentMethods
{
    /**
     * @param string $isoCode
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaymentMethodsByIsoCode($isoCode)
    {
        $isoCode = strtoupper($isoCode);

        // WPS  -> Web Payments Standard
        // EC   -> Express Checkout
        // WPP  -> Website Payments Plus
        // PVZ  -> Braintree / Payment VZero

        $paymentMethod = [
            // Europe
            'BE' => [PayPal::WPS, PayPal::EC              ], // Belgium
            'CZ' => [PayPal::WPS, PayPal::EC              ], // Czech Republic
            'DE' => [PayPal::WPS, PayPal::EC, PayPal::WPP], // Germany
            'ES' => [PayPal::WPS, PayPal::EC              ], // Spain
            'FR' => [PayPal::WPS, PayPal::EC              ], // France
            'IT' => [PayPal::WPS, PayPal::EC              ], // Italy
            'VA' => [PayPal::WPS, PayPal::EC              ], // Vatican City
            'NL' => [PayPal::WPS, PayPal::EC,             ], // The Netherlands
            'AN' => [PayPal::WPS, PayPal::EC              ], // Netherlands Antilles
            'PL' => [PayPal::WPS, PayPal::EC              ], // Poland
            'PT' => [PayPal::WPS, PayPal::EC              ], // Portugal
            'AT' => [PayPal::WPS, PayPal::EC              ], // Austria (without kangaroos)
            'CH' => [PayPal::WPS, PayPal::EC              ], // Switzerland
            'DK' => [PayPal::WPS, PayPal::EC              ], // Denmark
            'FI' => [PayPal::WPS, PayPal::EC              ], // Finland
            'GR' => [PayPal::WPS, PayPal::EC              ], // Greece
            'HU' => [PayPal::WPS, PayPal::EC              ], // Hungary
            'LU' => [PayPal::WPS, PayPal::EC              ], // Luxembourg
            'NO' => [PayPal::WPS, PayPal::EC              ], // Norway
            'RO' => [PayPal::WPS, PayPal::EC              ], // Romania
            'RU' => [PayPal::WPS, PayPal::EC              ], // Russia
            'SE' => [PayPal::WPS, PayPal::EC              ], // Sweden
            'SK' => [PayPal::WPS, PayPal::EC              ], // Slovakia
            'UA' => [PayPal::WPS, PayPal::EC              ], // Ukraine
            'TR' => [PayPal::WPS, PayPal::EC              ], // Turkey
            'SI' => [PayPal::WPS, PayPal::EC              ], // Slovenia
            'GB' => [PayPal::WPS, PayPal::EC              ], // Great Britain (but incl. North Ireland)
            // Asia
            'CN' => [PayPal::WPS, PayPal::EC              ], // People's Republic of China
            'MO' => [PayPal::WPS, PayPal::EC              ], // Macao
            'HK' => [PayPal::WPS, PayPal::EC              ], // Hong Kong
            'JP' => [PayPal::WPS, PayPal::EC              ], // Japan
            'MY' => [PayPal::WPS, PayPal::EC              ], // Malaysia
            'BN' => [PayPal::WPS, PayPal::EC              ], // Brunei
            'ID' => [PayPal::WPS, PayPal::EC              ], // Indonesia
            'KH' => [PayPal::WPS, PayPal::EC              ], // Cambodia
            'LA' => [PayPal::WPS, PayPal::EC              ], // Laos
            'PH' => [PayPal::WPS, PayPal::EC              ], // Philippines
            'TL' => [PayPal::WPS, PayPal::EC              ], // East Timor
            'VN' => [PayPal::WPS, PayPal::EC              ], // Vietnam
            'IL' => [PayPal::WPS, PayPal::EC              ], // Israel
            'SG' => [PayPal::WPS, PayPal::EC              ], // Singapore
            'TH' => [PayPal::WPS, PayPal::EC              ], // Thailand
            'TW' => [PayPal::WPS, PayPal::EC              ], // Taiwan
            // Oceania
            'NZ' => [PayPal::WPS, PayPal::EC              ], // New-Zealand
            'PW' => [PayPal::WPS, PayPal::EC              ], // Palau
            'AU' => [PayPal::WPS, PayPal::EC              ], // Australia (with kangaroos)
            // North America
            'US' => [PayPal::WPS, PayPal::EC              ], // United States
            'CA' => [PayPal::WPS, PayPal::EC              ], // Canada
            // Latin America
            'BR' => [PayPal::WPS, PayPal::EC              ], // Brazil
            'MX' => [PayPal::WPS, PayPal::EC              ], // Mexico
            'CL' => [PayPal::WPS, PayPal::EC              ], // Chile
            'CO' => [PayPal::WPS, PayPal::EC              ], // Colombia
            'PE' => [PayPal::WPS, PayPal::EC              ], // Peru
            // Africa
            'SL' => [PayPal::WPS, PayPal::EC              ], // Sierra Leone
            'SN' => [PayPal::WPS, PayPal::EC              ], // Senegal
        ];

        if (isset($paymentMethod[$isoCode])) {
            return $paymentMethod[$isoCode];
        }

        return [
            PayPal::WPS,
            PayPal::EC
        ];
    }

    /**
     * @param string $isoCode
     *
     * @return bool|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getCountryDependencyRetroCompatibilite($isoCode)
    {
        $localizations = [
            'AU' => ['AU'],
            'BE' => ['BE'],
            'CN' => ['CN', 'MO'],
            'CZ' => ['CZ'],
            'DE' => ['DE'],
            'ES' => ['ES'],
            'FR' => ['FR'],
            'GB' => ['GB'],
            'HK' => ['HK'],
            'IL' => ['IL'],
            'IN' => ['IN'],
            'IT' => ['IT', 'VA'],
            'JP' => ['JP'],
            'MY' => ['MY'],
            'NL' => ['AN', 'NL'],
            'NZ' => ['NZ'],
            'PL' => ['PL'],
            'PT' => ['PT', 'BR'],
            'RA' => ['AF', 'AS', 'BD', 'BN', 'BT', 'CC', 'CK', 'CX', 'FM', 'HM', 'ID', 'KH', 'KI', 'KN', 'KP', 'KR', 'KZ', 'LA', 'LK', 'MH', 'MM', 'MN', 'MV', 'MX', 'NF', 'NP', 'NU', 'OM', 'PG', 'PH', 'PW', 'QA', 'SB', 'TJ', 'TK', 'TL', 'TM', 'TO', 'TV', 'TZ', 'UZ', 'VN', 'VU', 'WF', 'WS'],
            'RE' => ['IE', 'ZA', 'GP', 'GG', 'JE', 'MC', 'MS', 'MP', 'PA', 'PY', 'PE', 'PN', 'PR', 'LC', 'SR', 'TT', 'UY', 'VE', 'VI', 'AG', 'AR', 'CA', 'BO', 'BS', 'BB', 'BZ', 'CL', 'CO', 'CR', 'CU', 'SV', 'GD', 'GT', 'HN', 'JM', 'NI', 'AD', 'AE', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AT', 'AW', 'AX', 'AZ', 'BA', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BV', 'BW', 'BY', 'CD', 'CF', 'CG', 'CH', 'CI', 'CM', 'CV', 'CY', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ET', 'FI', 'FJ', 'FK', 'FO', 'GA', 'GE', 'GF', 'GH', 'GI', 'GL', 'GM', 'GN', 'GQ', 'GR', 'GS', 'GU', 'GW', 'GY', 'HR', 'HT', 'HU', 'IM', 'IO', 'IQ', 'IR', 'IS', 'JO', 'KE', 'KM', 'KW', 'KY', 'LB', 'LI', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MD', 'ME', 'MF', 'MG', 'MK', 'ML', 'MQ', 'MR', 'MT', 'MU', 'MW', 'MZ', 'NA', 'NC', 'NE', 'NG', 'NO', 'NR', 'PF', 'PK', 'PM', 'PS', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SC', 'SD', 'SE', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'ST', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TN', 'UA', 'UG', 'VC', 'VG', 'YE', 'YT', 'ZM', 'ZW'],
            'SG' => ['SG'],
            'TH' => ['TH'],
            'TR' => ['TR'],
            'TW' => ['TW'],
            'US' => ['US'],
        ];

        foreach ($localizations as $key => $value) {
            if (in_array($isoCode, $value)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @param string $isoCode
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public static function getPaymentMethodsRetroCompatibilite($isoCode)
    {
        // WPS -> Website Payments Standard
        // EC -> Express Checkout
        // WPP -> PAYPAL PLUS

        $paymentMethod = [
            'AU' => [PayPal::WPS, PayPal::EC              ],
            'BE' => [PayPal::WPS, PayPal::EC              ],
            'CN' => [PayPal::WPS, PayPal::EC              ],
            'CZ' => [PayPal::WPS, PayPal::EC,             ],
            'DE' => [PayPal::WPS, PayPal::EC, PayPal::WPP],
            'ES' => [PayPal::WPS, PayPal::EC              ],
            'FR' => [PayPal::WPS, PayPal::EC              ],
            'GB' => [PayPal::WPS, PayPal::EC              ],
            'HK' => [PayPal::WPS, PayPal::EC              ],
            'IL' => [PayPal::WPS, PayPal::EC              ],
            'IN' => [PayPal::WPS, PayPal::EC              ],
            'IT' => [PayPal::WPS, PayPal::EC              ],
            'JP' => [PayPal::WPS, PayPal::EC              ],
            'MY' => [PayPal::WPS, PayPal::EC              ],
            'NL' => [PayPal::WPS, PayPal::EC              ],
            'NZ' => [PayPal::WPS, PayPal::EC              ],
            'PL' => [PayPal::WPS, PayPal::EC              ],
            'PT' => [PayPal::WPS, PayPal::EC              ],
            'RA' => [PayPal::WPS, PayPal::EC              ],
            'RE' => [PayPal::WPS, PayPal::EC              ],
            'SG' => [PayPal::WPS, PayPal::EC              ],
            'TH' => [PayPal::WPS, PayPal::EC              ],
            'TR' => [PayPal::WPS, PayPal::EC              ],
            'TW' => [PayPal::WPS, PayPal::EC              ],
            'US' => [PayPal::WPS, PayPal::EC              ],
            'ZA' => [PayPal::WPS, PayPal::EC              ],
        ];

        if (isset($paymentMethod[$isoCode])) {
            return $paymentMethod[$isoCode];
        }

        return [
            PayPal::WPS,
            PayPal::EC
        ];
    }

    /**
     * @param string $isoCode
     *
     * @return array
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
     * @param string $isoCode
     *
     * @return array
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
