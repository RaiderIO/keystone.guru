<?php

namespace App\Exceptions\Logging;

use App\Logging\RollbarStructuredLogging;
use Throwable;

class HandlerLogging extends RollbarStructuredLogging implements HandlerLoggingInterface
{
    public function tooManyRequests(string $ip, string $url, ?int $userId, ?string $username, Throwable $throwable): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function uncaughtException(string $ip, string $url, ?int $userId, ?string $username, ?array $body, string $exceptionClass, string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
