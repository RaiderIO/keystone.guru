<?php

namespace App\Exceptions\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use Throwable;

class HandlerLogging extends StructuredLogging implements HandlerLoggingInterface
{
    use InteractsWithRollbar;

    public function tooManyRequests(
        string    $ip,
        string    $url,
        ?int      $userId,
        ?string   $username,
        Throwable $throwable,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<string, mixed>|null $body
     */
    public function uncaughtException(
        string  $ip,
        string  $url,
        ?int    $userId,
        ?string $username,
        ?array  $body,
        string  $exceptionClass,
        string  $message,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }
}
