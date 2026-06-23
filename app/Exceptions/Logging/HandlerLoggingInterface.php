<?php

namespace App\Exceptions\Logging;

use Throwable;

interface HandlerLoggingInterface
{
    public function tooManyRequests(
        string    $ip,
        string    $url,
        ?int      $userId,
        ?string   $username,
        Throwable $throwable,
    ): void;

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
    ): void;
}
