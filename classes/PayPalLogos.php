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

namespace PayPalModule;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class PayPalLogos
 *
 * @package PayPalModule
 */
class PayPalLogos
{
    protected $isoCode = null;

    const LOCAL = 'Local';
    const HORIZONTAL = 'Horizontal';
    const VERTICAL = 'Vertical';

    /**
     * PayPalLogos constructor.
     *
     * @param $isoCode
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function __construct($isoCode)
    {
        $this->isoCode = $isoCode;
    }

    /**
     * @return array|bool
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getLogos()
    {
        $file = dirname(__FILE__).'/'.\PayPal::_PAYPAL_LOGO_XML_;

        if (!file_exists($file)) {
            return false;
        }

        $xml = simplexml_load_file($file);
        $logos = array();

        if (isset($xml) && $xml != false) {
            foreach ($xml->country as $item) {
                $tmpIsoCode = (string) $item->attributes()->iso_code;
                $logos[$tmpIsoCode] = (array) $item;
            }

            if (!isset($logos[$this->isoCode])) {
                $result = $this->getLocalLogos($logos['default'], 'default');
            } else {
                $result = $this->getLocalLogos($logos[$this->isoCode], $this->isoCode);
            }

            $result['default'] = $this->getLocalLogos($logos['default'], 'default');

            return $result;
        }

        return false;
    }

    /**
     * @param bool $vertical
     *
     * @return bool|mixed|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getCardsLogo($vertical = false)
    {
        $logos = $this->getLogos();

        if (!$logos) {
            return $logos[self::LOCAL.'PayPal'.self::HORIZONTAL.'SolutionPP'];
        }

        $orientation = $vertical === true ? self::VERTICAL : self::HORIZONTAL;
        $logoReference = self::LOCAL.'PayPal'.$orientation.'SolutionPP';

        if (array_key_exists($logoReference, $logos)) {
            return $logos[$logoReference];
        } elseif (($vertical !== false) && isset($logos[self::LOCAL.'PayPal'.self::HORIZONTAL.'SolutionPP'])) {
            return $logos[self::LOCAL.'PayPal'.self::HORIZONTAL.'SolutionPP'];
        }

        if (isset($logos['default'][self::LOCAL.'Local'.$orientation.'SolutionPP'])) {
            return _MODULE_DIR_.\PayPal::_PAYPAL_MODULE_DIRNAME_.$logos['default'][self::LOCAL.'Local'.$orientation.'SolutionPP'];
        }

        return false;
    }

    /**
     * @param array  $values
     * @param string $isoCode
     *
     * @return array
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    public function getLocalLogos(array $values, $isoCode)
    {
        foreach ($values as $key => $value) {
            if (!is_array($value)) {
                // Search for image file name
                preg_match('#.*/([\w._-]*)$#', $value, $logo);

                if ((count($logo) == 2) && (strstr($key, 'Local') === false)) {
                    $destination = \PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/logos/'.$isoCode.'_'.$logo[1];
                    $this->updatePictures($logo[0], $destination);

                    // Define the local path after picture have been downloaded
                    $values['Local'.$key] = _MODULE_DIR_.$destination;

                    // Load back office cards path
                    if (file_exists(dirname(__FILE__).'/views/img/bo-cards/'.\Tools::strtoupper($isoCode).'_bo_cards.png')) {
                        $values['BackOfficeCards'] = _MODULE_DIR_.\PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/bo-cards/'.\Tools::strtoupper($isoCode).'_bo_cards.png';
                    } elseif (file_exists(dirname(__FILE__).'/views/img/bo-cards/default.png')) {
                        $values['BackOfficeCards'] = _MODULE_DIR_.\PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/bo-cards/default.png';
                    }

                } elseif (isset($values['Local'.$key])) {
                    // Use the local version
                    $values['Local'.$key] = _MODULE_DIR_.\PayPal::_PAYPAL_MODULE_DIRNAME_.$values['Local'.$key];
                }

            }
        }

        return $values;
    }

    /**
     * @param      $source
     * @param      $destination
     * @param bool $force
     *
     * @return bool|string
     *
     * @author    PrestaShop SA <contact@prestashop.com>
     * @copyright 2007-2016 PrestaShop SA
     * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
     */
    protected function updatePictures($source, $destination, $force = false)
    {
        // 604800 => One week timestamp
        if (!file_exists(_PS_MODULE_DIR_.$destination) || ((time() - filemtime(_PS_MODULE_DIR_.$destination)) > 604800) || $force) {
            $picture = \Tools::file_get_contents($source);
            if ((bool) $picture !== false) {
                if ($handle = @fopen(_PS_MODULE_DIR_.$destination, 'w+')) {
                    $size = fwrite($handle, $picture);
                    if ($size > 0 || (file_exists(_MODULE_DIR_.$destination) && (@filesize(_MODULE_DIR_.$destination) > 0))) {
                        return _MODULE_DIR_.$destination;
                    }

                }
            } elseif (strstr($source, 'https')) {
                return $this->updatePictures(str_replace('https', 'http', $source), $destination);
            } else {
                return false;
            }

        }

        return _MODULE_DIR_.$destination;
    }
}
