<?php

namespace PayPalModule\DependencyInjection;

use Configuration;
use PayPal;
use PayPalModule\Logger\DummyLogger;
use PayPalModule\Logger\FileLogger;
use PayPalModule\Logger\Logger;
use PayPalModule\PayPalRestApi;
use PrestaShopException;

class Factory
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var PayPalRestApi
     */
    private PayPalRestApi $restApi;

    /**
     * Constructor
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->logger = $this->createLogger();

        $this->restApi = new PayPalRestApi(
            (string)Configuration::get(PayPal::CLIENT_ID),
            (string)Configuration::get(PayPal::SECRET),
            $this->logger
        );
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @return PayPalRestApi
     */
    public function getRestApi(): PayPalRestApi
    {
        return $this->restApi;
    }

    /**
     * @return Logger
     * @throws PrestaShopException
     */
    private function createLogger(): Logger
    {
        if (Configuration::get(PayPal::SETTINGS_LOG)) {
            return new FileLogger();
        } else {
            return new DummyLogger();
        }
    }
}