<?php

namespace App\Http\Middleware\Logging;

use App\Logging\RollbarStructuredLogging;

class DebugInfoContextLoggerLogging extends RollbarStructuredLogging implements DebugInfoContextLoggerLoggingInterface
{
    public function handleStart(string $url, string $method): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleEnd(): void
    {
        $this->end(__METHOD__);
    }
}
