<?php

namespace App\Http\Middleware\Api\Logging;

interface ApiAuthenticationLoggingInterface
{
    public function handleAuthenticationFailed(string $result): void;
}
