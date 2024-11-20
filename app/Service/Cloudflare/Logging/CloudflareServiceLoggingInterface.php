<?php

namespace App\Service\Cloudflare\Logging;

interface CloudflareServiceLoggingInterface
{
    public function getIpRangesInvalidIpAddress(string $ipAddress): void;
}
