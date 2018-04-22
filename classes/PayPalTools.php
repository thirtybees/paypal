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

use Address;
use Context;
use Country;
use Customer;
use Hook;
use Module;
use PrestaShopDatabaseException;
use PrestaShopException;
use State;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalTools
 *
 * @package PayPalModule
 */
class PayPalTools
{
    protected $name = null;

    /**
     * PayPalTools constructor.
     *
     * @param string $moduleName
     */
    public function __construct($moduleName)
    {
        $this->name = $moduleName;
    }

    /**
     * @param int $position
     *
     * @return bool
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function moveTopPayments($position)
    {
        try {
            $hookPayment = (int) Hook::getIdByName('payment');
            $moduleInstance = Module::getInstanceByName($this->name);
            $moduleInfo = Hook::getModulesFromHook($hookPayment, $moduleInstance->id);


            if ((isset($moduleInfo['position']) && (int) $moduleInfo['position'] > (int) $position) ||
                (isset($moduleInfo['m.position']) && (int) $moduleInfo['m.position'] > (int) $position)) {
                return $moduleInstance->updatePosition($hookPayment, 0, (int) $position);
            }

            return $moduleInstance->updatePosition($hookPayment, 1, (int) $position);
        } catch (\PrestaShopException $e) {
            \Logger::addLog("PayPal module error: Error during install - {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @param int $position
     *
     * @return bool
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function moveRightColumn($position)
    {
        $hookRight = (int) Hook::getIdByName('rightColumn');
        $moduleInstance = Module::getInstanceByName($this->name);
        $moduleInfo = Hook::getModulesFromHook($hookRight, $moduleInstance->id);


        if ((isset($moduleInfo['position']) && (int) $moduleInfo['position'] > (int) $position) ||
            (isset($moduleInfo['m.position']) && (int) $moduleInfo['m.position'] > (int) $position)) {
            return $moduleInstance->updatePosition($hookRight, 0, (int) $position);
        }

        return $moduleInstance->updatePosition($hookRight, 1, (int) $position);
    }

    /**
     * Set customer address (when not logged in)
     * Used to create user address with PayPal account information
     *
     * @param array    $payment
     * @param Customer $customer
     * @param int      $idAddress
     *
     * @return Address|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setCustomerAddress(array $payment, $customer, $idAddress = null)
    {
        /** @var array $payerInfo */
        $payerInfo = $payment['payer']['payer_info'];
        /** @var array $shippingAddress */
        $shippingAddress = $payerInfo['shipping_address'];

        $address = new Address($idAddress);
        $address->id_country = Country::getByIso($shippingAddress['country_code']);
        if (!$idAddress) {
            // Avoid the same alias, increment the number if possible
            $customerAddresses = $customer->getAddresses(Context::getContext()->language->id);
            $id = 0;
            $uniqueFound = false;
            while (!$uniqueFound) {
                foreach ($customerAddresses as $customerAddress) {
                    if ($customerAddress['alias'] === (!$id ? 'PayPal_Address' : 'PayPal_Address'.$id)) {
                        $id++;
                        continue 2;
                    }
                }

                $address->alias = (!$id ? 'PayPal_Address' : 'PayPal_Address'.$id);
                $uniqueFound = true;
            }
        }

        $name = trim($shippingAddress['recipient_name']);
        $name = explode(' ', $name);
        if (isset($name[1])) {
            $firstname = $name[0];
            unset($name[0]);
            $lastname = implode(' ', $name);
        } else {
            $firstname = $payerInfo['first_name'];
            $lastname = $payerInfo['last_name'];
        }

        $address->lastname = $lastname;
        $address->firstname = $firstname;
        $address->address1 = $shippingAddress['line1'];
        if (isset($shippingAddress['line2'])) {
            $address->address2 = $shippingAddress['line2'];
        }

        $address->city = $shippingAddress['city'];
        if (Country::containsStates($address->id_country)) {
            $address->id_state = (int) State::getIdByIso($shippingAddress['state'], $address->id_country);
        }
        $address->postcode = $shippingAddress['postal_code'];
        if (isset($shippingAddress->phone)) {
            $address->phone = $shippingAddress->phone;
        } else {
            $address->phone = '0000000000';
        }

        $address->id_customer = $customer->id;

        return $address;
    }

    /**
     * Check if the address has changed
     *
     * @param array   $payment
     * @param Address $address
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function checkAddressChanged($payment, $address)
    {
        if (!isset($payment['payer']['payer_info'])) {
            return true;
        }
        /** @var array $payerInfo */
        $payerInfo = $payment['payer']['payer_info'];
        /** @var array $paypalAddress */
        $paypalAddress = $payerInfo['shipping_address'];

        return !($address->id_country == Country::getByIso($paypalAddress['country_code'])
            && $address->address1 == $paypalAddress['line1']
            && $address->address2 == (isset($paypalAddress['line2']) ? $paypalAddress['line2'] : null)
            && $address->city == $paypalAddress['city']);
    }

    /**
     * @param array    $payment
     * @param Customer $customer
     *
     * @return Address|bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function checkAndModifyAddress(array $payment, $customer)
    {
        $context = Context::getContext();
        $customerAddresses = $customer->getAddresses($context->cookie->id_lang);
        $paypalAddress = false;
        if (empty($customerAddresses)) {
            $paypalAddress = static::setCustomerAddress($payment, $customer);
        } else {
            foreach ($customerAddresses as $address) {
                /** @var array $payerInfo */
                $payerInfo = $payment['payer']['payer_info'];
                /** @var array $shippingAddress */
                $shippingAddress = $payerInfo['shipping_address'];

                if ($address['firstname'] == $payerInfo['first_name']
                    && $address['lastname'] == $payerInfo['last_name']
                    && $address['id_country'] == Country::getByIso($shippingAddress['country_code'])
                    && $address['address1'] == $shippingAddress['line1']
                    && $address['address2'] == (isset($shippingAddress['line2']) ? $shippingAddress['line2'] : null)
                    && $address['city'] == $shippingAddress['city']
                ) {
                    $paypalAddress = new Address($address['id_address']);
                    break;
                }
            }
        }

        if (!$paypalAddress) {
            $paypalAddress = static::setCustomerAddress($payment, $customer);
        }

        $paypalAddress->save();

        return $paypalAddress;
    }
}
