<?php

namespace App\Http\Middleware\Api\Logging;

use App\Logging\StructuredLogging;

class ApiAuthenticationLogging extends StructuredLogging implements ApiAuthenticationLoggingInterface
{
    public function handleAuthenticationFailed(string $result): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
