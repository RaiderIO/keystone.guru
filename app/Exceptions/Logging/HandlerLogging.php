<?php

namespace App\Exceptions\Logging;

use App\Logging\RollbarStructuredLogging;
use Throwable;

class HandlerLogging extends RollbarStructuredLogging implements HandlerLoggingInterface
{
    public function tooManyRequests(string $ip, ?int $userId, ?string $username, Throwable $throwable): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
