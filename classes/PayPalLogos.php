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

namespace PayPalModule;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Hybridauth\Exception\Exception;
use PayPal;
use Tools;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalLogos
 *
 * @package PayPalModule
 */
class PayPalLogos
{
    const LOCAL = 'Local';
    const HORIZONTAL = 'Horizontal';
    const VERTICAL = 'Vertical';

    /**
     * @param string $isoCode
     *
     * @return array|bool
     */
    public static function getLogos($isoCode)
    {
        $file = _PS_MODULE_DIR_.'paypal/'.\PayPal::_PAYPAL_LOGO_XML_;

        if (!file_exists($file)) {
            return false;
        }

        $xml = simplexml_load_file($file);
        $logos = [];

        if (isset($xml) && $xml != false) {
            foreach ($xml->country as $item) {
                $tmpIsoCode = (string) $item->attributes()->iso_code;
                $logos[$tmpIsoCode] = (array) $item;
            }

            if (!isset($logos[$isoCode])) {
                $result = self::getLocalLogos($logos['default'], 'default');
            } else {
                $result = self::getLocalLogos($logos[$isoCode], $isoCode);
            }

            $result['default'] = self::getLocalLogos($logos['default'], 'default');

            return $result;
        }

        return false;
    }

    /**
     * @param string $isoCode
     * @param bool   $vertical
     *
     * @return bool|mixed|string
     */
    public static function getCardsLogo($isoCode, $vertical = false)
    {
        $logos = self::getLogos($isoCode);

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
            return _MODULE_DIR_.PayPal::_PAYPAL_MODULE_DIRNAME_.$logos['default'][self::LOCAL.'Local'.$orientation.'SolutionPP'];
        }

        return false;
    }

    /**
     * @param array  $values
     * @param string $isoCode
     *
     * @return array
     */
    public static function getLocalLogos(array $values, $isoCode)
    {
        foreach ($values as $key => $value) {
            if (!is_array($value)) {
                // Search for image file name
                preg_match('#.*/([\w._-]*)$#', $value, $logo);

                if ((count($logo) == 2) && (strstr($key, 'Local') === false)) {
                    $destination = PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/logos/'.$isoCode.'_'.$logo[1];
                    self::updatePictures($logo[0], $destination);

                    // Define the local path after picture have been downloaded
                    $values['Local'.$key] = _MODULE_DIR_.$destination;

                    // Load back office cards path
                    if (file_exists(dirname(__FILE__).'/views/img/bo-cards/'.Tools::strtoupper($isoCode).'_bo_cards.png')) {
                        $values['BackOfficeCards'] = _MODULE_DIR_.PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/bo-cards/'.Tools::strtoupper($isoCode).'_bo_cards.png';
                    } elseif (file_exists(dirname(__FILE__).'/views/img/bo-cards/default.png')) {
                        $values['BackOfficeCards'] = _MODULE_DIR_.PayPal::_PAYPAL_MODULE_DIRNAME_.'/views/img/bo-cards/default.png';
                    }

                } elseif (isset($values['Local'.$key])) {
                    // Use the local version
                    $values['Local'.$key] = _MODULE_DIR_.PayPal::_PAYPAL_MODULE_DIRNAME_.$values['Local'.$key];
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
     */
    protected static function updatePictures($source, $destination, $force = false)
    {
        if (!file_exists(_PS_MODULE_DIR_.$destination) || ((time() - filemtime(_PS_MODULE_DIR_.$destination)) > 604800) || $force) {
            $guzzle = new Client([
                'timeout'     => PayPal::CONNECTION_TIMEOUT,
                'verify'      => _PS_TOOL_DIR_.'cacert.pem',
            ]);
            try {
                $picture = (string) $guzzle->get($source)->getBody();
            } catch (TransferException $e) {
                $picture = false;
            }
            if ($picture !== false) {
                if ($handle = @fopen(_PS_MODULE_DIR_.$destination, 'w+')) {
                    $size = fwrite($handle, $picture);
                    if ($size > 0 || (file_exists(_MODULE_DIR_.$destination) && (@filesize(_MODULE_DIR_.$destination) > 0))) {
                        return _MODULE_DIR_.$destination;
                    }

                }
            } elseif (strstr($source, 'https')) {
                return self::updatePictures(str_replace('https', 'http', $source), $destination);
            } else {
                return false;
            }

        }

        return _MODULE_DIR_.$destination;
    }
}
