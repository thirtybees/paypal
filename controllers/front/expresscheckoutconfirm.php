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

        $payerId = \Tools::getValue('PayerID');
        $paymentId = \Tools::getValue('paymentId');

        $canShip = $this->assignCartSummary();
        if (!$canShip) {
            $this->setTemplate('cant-ship.tpl');

            return;
        }

        $rest = new PayPalRestApi();
        $previouslyAuthorized = Tools::getValue('authorized');
        $payment = $rest->lookUpPayment($paymentId);
        if (!empty($payment->transactions[0]->related_resources[0]->authorization->id)) {
            $this->redirectToPayment($payerId, $paymentId);
        }

        $authorized = false;
        if (isset($payment->links)) {
            foreach ($payment->links as $link) {
                if ($link->rel === 'capture') {
                    $authorized = true;
                    break;
                }
            }
        }

        if (!$authorized && !$previouslyAuthorized) {
            $rest->executePayment($payerId, $paymentId);
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
        } elseif ($previouslyAuthorized && $payment->state === 'authorized') {
            $rest->voidAuthorization($payment->id);

            // Unable to authorize, try again
            Tools::redirectLink($this->context->link->getModuleLink($this->module->name, 'expresscheckout', [], true));
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
     *
     *
     */
    public function assignCartSummary()
    {
        // Rest API object
        $restApi = new PayPalRestApi();
        $cart = $this->context->cart;

        // Get the currency
        $currency = new \Currency((int) $cart->id_currency);

        // Indicates whether we have checked the address before
        $addressChanged = (bool) Tools::getValue('addressChanged');
        $tbShippingAddress = new \Address($cart->id_address_delivery);
        $tbBillingAddress = new \Address($cart->id_address_invoice);

        // Check whether the address has been updated by the user
        $paymentInfo = $restApi->lookUpPayment(Tools::getValue('paymentId'));

        if (!$addressChanged && \PayPalModule\PayPalTools::checkAddressChanged($paymentInfo, $tbShippingAddress)) {
            $tbBillingAddress = $tbShippingAddress = \PayPalModule\PayPalTools::checkAndModifyAddress($paymentInfo, $this->context->customer);
            $cart->id_address_delivery = $tbShippingAddress->id;
            $cart->id_address_invoice = $tbShippingAddress->id;

            $deliveryOption = $cart->getDeliveryOption();
            if (is_array($deliveryOption) && !empty($deliveryOption)) {
                $deliveryOption = array_values($deliveryOption);
                if (!in_array($cart->id_carrier, $deliveryOption)) {
                    $cart->id_carrier = $deliveryOption[0];
                }
                if (!$cart->id_carrier) {
                    return false;
                }
            } else {
                return false;
            }

            $cart->save();

            Tools::redirectLink(
                $this->context->link->getModuleLink(
                    $this->module->name,
                    'expresscheckoutconfirm',
                    [
                        'PayerID'        => Tools::getValue('PayerID'),
                        'paymentId'      => Tools::getValue('paymentId'),
                        'addressChanged' => 1,
                        'authorized'     => (int) Tools::getValue('authorized'),
                    ],
                    true
                )
            );
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

        return true;
    }

    /**
     * Redirect to payment
     *
     * @param string $payerId
     * @param string $paymentId
     */
    protected function redirectToPayment($payerId, $paymentId)
    {
        Tools::redirectLink(
            $this->context->link->getModuleLink(
                $this->module->name,
                'expresscheckout',
                [
                    'PayerID'   => $payerId,
                    'paymentId' => $paymentId,
                ],
                true
            )
        );
    }
}
