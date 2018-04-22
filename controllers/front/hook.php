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

use PayPalModule\PayPalRestApi;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class PayPalHookModuleFrontController
 */
class PayPalHookModuleFrontController extends ModuleFrontController
{
    /** @var PayPal $module */
    public $module;
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * @return void
     */
    public function displayMaintenancePage()
    {
        // Disable maintenance page
    }

    /**
     * Initialize content and block unauthorized calls
     *
     * @since 2.0.0
     *
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function initContent()
    {
        $content = json_decode(file_get_contents('php://input'), true);

        // Don't believe anything in this webhook, grab the webhook URL and check with PayPal directly
        if (!isset($content['id'])) {
            http_response_code(400);
            die('1');
        }

        $rest = PayPalRestApi::getInstance();

        $webhook = $rest->lookupWebhook($content['id']);
        if (!isset($webhook->event_type)) {
            http_response_code(400);
            die('1');
        }

        $this->processWebhook($webhook);

        http_response_code(400);
        die('1');
    }

    /**
     * Process webhook
     *
     * @param \stdClass $content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function processWebhook($content)
    {
        switch ($content->event_type) {
            case 'PAYMENT.AUTHORIZATION.VOIDED':
                $this->processAuthorizationVoided($content);
                break;
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->processCaptureCompleted($content);
                break;
            case 'PAYMENT.CAPTURE.PENDING':
                $this->processCapturePending($content);
                break;
            case 'PAYMENT.CAPTURE.DENIED':
                $this->processCaptureDenied($content);
                break;
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->processCaptureRefunded($content);
                break;
            case 'PAYMENT.CAPTURE.REVERSED':
                $this->processCaptureReversed($content);
                break;
        }

        die('0');
    }

    /**
     * @param stdClass $content Webhook content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processAuthorizationVoided($content)
    {
        $paypalOrder = \PayPalModule\PayPalOrder::getByPaymentId($content->resource->parent_payment);
        $order = new Order($paypalOrder['id_order']);
        if (!Validate::isLoadedObject($order)) {
            http_response_code(400);
            die('1');
        }
        $orderState = $order->getCurrentOrderState();
        if (!$orderState->paid) {
            // This effectively cancels the order which cannot be in a paid state right now, so just cancel will do
            $order->setCurrentState(Configuration::get('PS_OS_CANCEL'));
        }
    }

    /**
     * @param stdClass $content Webhook content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processCaptureCompleted($content)
    {
        $paypalOrder = \PayPalModule\PayPalOrder::getByPaymentId($content->resource->parent_payment);
        $order = new Order($paypalOrder['id_order']);
        if (!Validate::isLoadedObject($order)) {
            http_response_code(400);
            die('1');
        }
        $orderState = $order->getCurrentOrderState();
        if (!$orderState->paid) {
            // If the order has not been marked as paid yet, it now is
            $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
        }
    }

    /**
     * @param \stdClass $content Webhook content
     */
    protected function processCapturePending($content)
    {
        // Nothing yet
    }

    /**
     * @param stdClass $content Webhook content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processCaptureDenied($content)
    {
        $paypalOrder = \PayPalModule\PayPalOrder::getByPaymentId($content->resource->parent_payment);
        $order = new Order($paypalOrder['id_order']);
        if (!Validate::isLoadedObject($order)) {
            http_response_code(400);
            die('1');
        }
        $orderState = $order->getCurrentOrderState();
        if (!$orderState->paid) {
            $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
        }
    }

    /**
     * @param stdClass $content Webhook content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processCaptureRefunded($content)
    {
        $paypalOrder = \PayPalModule\PayPalOrder::getByPaymentId($content->resource->parent_payment);
        $order = new Order($paypalOrder['id_order']);
        if (!Validate::isLoadedObject($order)) {
            http_response_code(400);
            die('1');
        }
        $orderState = $order->getCurrentOrderState();
        if ($orderState->paid) {
            // If the order has already been paid it is now refunded
            $order->setCurrentState(Configuration::get('PS_OS_REFUND'));
        } else {
            // Else, the order is just canceled because we can no longer use the authorization
            $order->setCurrentState(Configuration::get('PS_OS_CANCEL'));
        }
    }

    /**
     * @param stdClass $content Webhook content
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processCaptureReversed($content)
    {
        $paypalOrder = \PayPalModule\PayPalOrder::getByPaymentId($content->resource->parent_payment);
        $order = new Order($paypalOrder['id_order']);
        if (!Validate::isLoadedObject($order)) {
            http_response_code(400);
            die('1');
        }

        $orderState = $order->getCurrentOrderState();
        if ($orderState->paid) {
            // If the order has already been paid it has been refunded
            $order->setCurrentState(Configuration::get('PS_OS_REFUND'));
        } else {
            // Otherwise we handle this as a payment error
            $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
        }
    }
}
