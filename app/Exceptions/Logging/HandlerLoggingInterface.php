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
     * @param bool $hasSignature     False for a url that carries no signature at all: a scraper, or a tab still
     *                               running front-end code from before the signed-url gate.
     * @param bool $signatureExpired A signature that was valid once - typically a page left open past its expiry.
     */
    public function invalidSignature(
        string  $ip,
        string  $url,
        ?int    $userId,
        ?string $username,
        bool    $hasSignature,
        bool    $signatureExpired,
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
