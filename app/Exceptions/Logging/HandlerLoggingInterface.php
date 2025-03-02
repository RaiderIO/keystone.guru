<?php

namespace App\Exceptions\Logging;

interface HandlerLoggingInterface
{
    public function tooManyRequests(string $ip, string $url, ?int $userId, ?string $username, \Throwable $throwable): void;

    public function uncaughtException(string $ip, string $url, ?int $userId, ?string $username, ?array $body, string $exceptionClass, string $message): void;
}
