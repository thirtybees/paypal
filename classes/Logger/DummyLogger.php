<?php

namespace PayPalModule\Logger;

use Throwable;

class DummyLogger implements Logger
{
    /**
     * @param string $message
     * @return void
     */
    public function log(string $message)
    {
        // no-op
    }

    /**
     * @param string $message
     * @return void
     */
    public function error(string $message)
    {
        // no-op
    }

    /**
     * @param Throwable $e
     * @return void
     */
    public function exception(Throwable $e)
    {
        // no-op
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return false;
    }

}