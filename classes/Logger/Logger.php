<?php

namespace PayPalModule\Logger;

use Throwable;

interface Logger
{
    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message);

    /**
     * @param string $message
     *
     * @return void
     */
    public function error(string $message);

    /**
     * @param Throwable $e
     *
     * @return void
     */
    public function exception(Throwable $e);

    /**
     * @return bool
     */
    public function isEnabled(): bool;
}