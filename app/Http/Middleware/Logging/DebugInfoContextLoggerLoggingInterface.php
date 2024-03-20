<?php

namespace App\Http\Middleware\Logging;

interface DebugInfoContextLoggerLoggingInterface
{
    public function handleStart(string $url, string $method): void;

    public function handleEnd(): void;
}
