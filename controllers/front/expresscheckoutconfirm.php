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

use PayPalModule\PayPalLogos;
use PayPalModule\PayPalRestApi;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalExpressCheckoutConfirmModuleFrontController
 *
 * Used for In-Context, Website Payments Standards and Website Payments Plus
 */
class PayPalExpressCheckoutConfirmModuleFrontController extends \ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $display_column_left */
    public $display_column_left = false;
    // @codingStandardsIgnoreEnd

    /** @var \PayPal $module */
    public $module;

    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        if (\Tools::isSubmit('update')) {
            $this->updateOrder();
        }

        $payerId = \Tools::getValue('PayerID');
        $paymentId = \Tools::getValue('paymentId');

        $api = new PayPalRestApi();

        $this->assignCartSummary();

        if (!Tools::isSubmit('authorized')) {
            $payment = $api->lookUpPayment($paymentId);
            $authorized = false;
            if (isset($payment->links)) {
                foreach ($payment->links as $link) {
                    if ($link->rel === 'capture') {
                        $authorized = true;
                        break;
                    }
                }
            }

            if (!$authorized) {
                $api->executePayment($payerId, $paymentId);
                Tools::redirectLink(
                    $this->context->link->getModuleLink(
                        $this->module->name,
                        'expresscheckoutconfirm',
                        [
                            'PayerID'        => $payerId,
                            'paymentId'      => $paymentId,
                            'addressChanged' => (int) \Tools::getValue('addressChanged'),
                            'authorized'     => 1,
                        ],
                        true
                    )
                );
            }
        }


        $params = [
            'PayerID'   => $payerId,
            'paymentId' => $paymentId,
        ];

        $this->context->smarty->assign([
            'confirm_form_action' => $this->context->link->getModuleLink($this->module->name, 'expresscheckout', $params, true),
        ]);

        $this->setTemplate('order-summary.tpl');
    }

    /**
     * Assign cart summary
     */
    public function assignCartSummary()
    {
        // Rest API object
        $restApi = new PayPalRestApi();

        // Get the currency
        $currency = new \Currency((int) $this->context->cart->id_currency);

        $addressChanged = (bool) Tools::getValue('addressChanged');
        $tbShippingAddress = new \Address($this->context->cart->id_address_delivery);
        $tbBillingAddress = new \Address($this->context->cart->id_address_invoice);

        // Check whether the address has been updated by the user
        $paymentInfo = $restApi->lookUpPayment(Tools::getValue('paymentId'));

        if (isset ($paymentInfo->payer->payer_info->shipping_address)) {
            // Update the address accordingly
            $paypalShippingAddress = $paymentInfo->payer->payer_info->shipping_address;

            // Check if the country code and postal code (optional) and city are the same
            if ($paypalShippingAddress->country_code !== strtoupper(Country::getIsoById($tbShippingAddress->id_country))
                || $paypalShippingAddress->postal_code !== $tbShippingAddress->postcode
                || $paypalShippingAddress->city !== $tbShippingAddress->city
            ) {
                $names = explode(' ', $paypalShippingAddress->recipient_name);
                if (count($names) >= 2) {
                    $tbShippingAddress->firstname = $names[0];
                    $tbShippingAddress->lastname = implode(' ', array_splice($names, 1));
                } else {
                    $tbShippingAddress->firstname = $paymentInfo->payer->payer_info->first_name;
                    $tbShippingAddress->lastname = $paymentInfo->payer->payer_info->last_name;
                }
                $tbShippingAddress->address1 = $paypalShippingAddress->line1;
                $tbShippingAddress->city = $paypalShippingAddress->city;
                if (isset($paypalShippingAddress->state) && $paypalShippingAddress->state) {
                    $tbShippingAddress->id_state = State::getIdByIso($paypalShippingAddress->state);
                } else {
                    $tbShippingAddress->id_state = null;
                }
                $tbShippingAddress->postcode = $paypalShippingAddress->postal_code;
                $tbShippingAddress->id_country = Country::getByIso($paypalShippingAddress->country_code);
                $tbBillingAddress = $tbShippingAddress;
                $tbBillingAddress->save();
                $tbShippingAddress->save();

                // Make the cart recalculate shipping costs and save it to DB
                $this->context->cart->getPackageShippingCost();
                $this->context->cart->save();

                Tools::redirectLink($this->context->link->getModuleLink(
                    'paypal',
                    'expresscheckoutconfirm',
                    [
                        'paymentId'      => Tools::getValue('paymentId'),
                        'PayerID'        => Tools::getValue('PayerID'),
                        'addressChanged' => 1,
                        'authorized'     => (int) Tools::getValue('authorized'),
                    ],
                    true
                ));
                exit;
            }
        }

        // Grab the module's file path
        $reflection = new ReflectionClass($this->module);
        $moduleFilepath = $reflection->getFileName();
        $this->context->smarty->assign([
            'total'            => \Tools::displayPrice($this->context->cart->getOrderTotal(true), $currency),
            'logos'            => PayPalLogos::getLogos($this->module->getLocale()),
            'use_mobile'       => (bool) $this->context->getMobileDevice(),
            'address_shipping' => $tbShippingAddress,
            'address_billing'  => $tbBillingAddress,
            'cart'             => $this->context->cart,
            'patternRules'     => ['avoid' => []],
            'cart_image_size'  => 'cart_default',
            'addressChanged'   => $addressChanged,
        ]);

        // With these smarty vars, generate the new template
        $this->context->smarty->assign([
            'paypal_cart_summary' => $this->module->display($moduleFilepath, 'views/templates/hook/paypal_cart_summary.tpl'),
        ]);
    }
}
