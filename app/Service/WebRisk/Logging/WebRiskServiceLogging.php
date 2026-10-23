<?php

namespace App\Service\WebRisk\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class WebRiskServiceLogging extends StructuredLogging implements WebRiskServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function isThreatRequestFailed(string $url, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function isThreatInvalidResponse(string $url, int $status): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param array<int, string> $threatTypes
     */
    public function isThreatFound(string $url, array $threatTypes): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
