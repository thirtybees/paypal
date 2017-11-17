<?php
/**
 * Copyright (C) 2017 thirty bees
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
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace PayPalModule;

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
     */
    public function moveTopPayments($position)
    {
        $hookPayment = (int) \Hook::getIdByName('payment');
        $moduleInstance = \Module::getInstanceByName($this->name);
        $moduleInfo = \Hook::getModulesFromHook($hookPayment, $moduleInstance->id);


        if ((isset($moduleInfo['position']) && (int) $moduleInfo['position'] > (int) $position) ||
            (isset($moduleInfo['m.position']) && (int) $moduleInfo['m.position'] > (int) $position)) {
            return $moduleInstance->updatePosition($hookPayment, 0, (int) $position);
        }

        return $moduleInstance->updatePosition($hookPayment, 1, (int) $position);
    }

    /**
     * @param int $position
     *
     * @return bool
     */
    public function moveRightColumn($position)
    {
        $hookRight = (int) \Hook::getIdByName('rightColumn');
        $moduleInstance = \Module::getInstanceByName($this->name);
        $moduleInfo = \Hook::getModulesFromHook($hookRight, $moduleInstance->id);


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
     * @param \stdClass $payment
     * @param \Customer $customer
     * @param int       $idAddress
     *
     * @return \Address
     */
    public static function setCustomerAddress($payment, $customer, $idAddress = null)
    {
        $payerInfo = $payment->payer->payer_info;
        $shippingAddress = $payerInfo->shipping_address;

        $address = new \Address($idAddress);
        $address->id_country = \Country::getByIso($shippingAddress->country_code);
        if (!$idAddress) {
            // Avoid the same alias, increment the number if possible
            $customerAddresses = $customer->getAddresses(\Context::getContext()->language->id);
            $id = 0;
            $uniqueFound = false;
            while (!$uniqueFound) {
                foreach ($customerAddresses as $customerAddress) {
                    if ($customerAddress['alias'] === (!$id ? 'Paypal_Address' : 'Paypal_Address'.$id)) {
                        $id++;
                        continue 2;
                    }
                }

                $address->alias = 'Paypal_Address'.$id;
                $uniqueFound = true;
            }
        }

        $name = trim($shippingAddress->recipient_name);
        $name = explode(' ', $name);
        if (isset($name[1])) {
            $firstname = $name[0];
            unset($name[0]);
            $lastname = implode(' ', $name);
        } else {
            $firstname = $payerInfo->first_name;
            $lastname = $payerInfo->last_name;
        }

        $address->lastname = $lastname;
        $address->firstname = $firstname;
        $address->address1 = $shippingAddress->line1;
        if (isset($shippingAddress->line2)) {
            $address->address2 = $shippingAddress->line2;
        }

        $address->city = $shippingAddress->city;
        if (\Country::containsStates($address->id_country)) {
            $address->id_state = (int) \State::getIdByIso($shippingAddress->state, $address->id_country);
        }

        $address->postcode = $shippingAddress->postal_code;
        $address->phone = '0000000000';

        $address->id_customer = $customer->id;

        return $address;
    }

    /**
     * Check if the address has changed
     *
     * @param \stdClass $payment
     * @param \Address  $address
     *
     * @return bool
     */
    public static function checkAddressChanged($payment, $address)
    {
        if (!isset($payment->payer->payer_info)) {
            return true;
        }
        $payerInfo = $payment->payer->payer_info;
        $paypalAddress = $payerInfo->shipping_address;

        return !($address->id_country == \Country::getByIso($paypalAddress->country_code)
        && $address->address1 == $paypalAddress->line1
        && $address->address2 == (isset($paypalAddress->line2) ? $paypalAddress->line2 : null)
        && $address->city == $paypalAddress->city);
    }

    /**
     * @param \stdClass $payment
     * @param \Customer $customer
     *
     * @return \Address|bool
     */
    public static function checkAndModifyAddress($payment, $customer)
    {
        $context = \Context::getContext();
        $customerAddresses = $customer->getAddresses($context->cookie->id_lang);
        $paypalAddress = false;
        if (empty($customerAddresses)) {
            $paypalAddress = static::setCustomerAddress($payment, $customer);
        } else {
            foreach ($customerAddresses as $address) {
                $payerInfo = $payment->payer->payer_info;
                $shippingAddress = $payerInfo->shipping_address;

                if ($address['firstname'] == $payerInfo->first_name
                    && $address['lastname'] == $payerInfo->last_name
                    && $address['id_country'] == \Country::getByIso($shippingAddress->country_code)
                    && $address['address1'] == $shippingAddress->line1
                    && $address['address2'] == (isset($shippingAddress->line2) ? $shippingAddress->line2 : null)
                    && $address['city'] == $shippingAddress->city
                ) {
                    $paypalAddress = new \Address($address['id_address']);
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
