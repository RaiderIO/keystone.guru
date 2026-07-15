<?php

namespace App\Logging\Concerns;

use Psr\Log\LoggerInterface;
use Rollbar\Rollbar;

trait InteractsWithRollbar
{
    /** @return array<int, LoggerInterface> */
    protected function getDefaultLoggers(): array
    {
        $loggers = parent::getDefaultLoggers();

        // Rollbar::logger() is null until Rollbar::init() has run (AppServiceProvider::boot()'s booted()
        // callback) - reachable for anything instantiated before that callback fires
        if (($rollbarLogger = Rollbar::logger()) !== null) {
            $loggers[] = $rollbarLogger;
        }

        return $loggers;
    }
}
