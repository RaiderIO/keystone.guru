<?php

namespace App\Exceptions\Logging;

interface HandlerLoggingInterface
{
    public function tooManyRequests(string $ip, string $uri, ?int $userId, ?string $username, \Throwable $throwable): void;
}
