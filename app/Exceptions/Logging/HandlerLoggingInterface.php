<?php

namespace App\Exceptions\Logging;

interface HandlerLoggingInterface
{
    public function tooManyRequests(string $message, ?int $userId, ?string $username, \Throwable $throwable): void;
}
