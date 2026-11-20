<?php

namespace App\Service\UrlThreat\Logging;

use App\Logging\StructuredLogging;

class UrlThreatServiceLogging extends StructuredLogging implements UrlThreatServiceLoggingInterface
{
    public function isThreatRequestFailed(string $url, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function isThreatInvalidResponse(string $url, int $status): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function isThreatFound(string $url, string $host): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
