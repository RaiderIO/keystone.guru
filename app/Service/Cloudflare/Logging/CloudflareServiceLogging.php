<?php

namespace App\Service\Cloudflare\Logging;

use App\Logging\StructuredLogging;

class CloudflareServiceLogging extends StructuredLogging implements CloudflareServiceLoggingInterface
{
    public function getIpRangesInvalidIpAddress(string $ipAddress): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
