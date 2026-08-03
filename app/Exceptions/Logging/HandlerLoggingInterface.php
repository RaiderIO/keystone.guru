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
     * @param bool                      $reportedByErrorTracker Whether the exception handler also reports this
     *                                                          exception to the error tracker itself, with a full
     *                                                          stack trace. A sink receiving both would show the
     *                                                          same failure twice, the second time strictly worse.
     */
    public function uncaughtException(
        string  $ip,
        string  $url,
        ?int    $userId,
        ?string $username,
        ?array  $body,
        string  $exceptionClass,
        string  $message,
        bool    $reportedByErrorTracker,
    ): void;
}
