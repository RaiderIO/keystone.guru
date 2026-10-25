<?php

namespace App\Service\UrlThreat\Logging;

interface UrlThreatServiceLoggingInterface
{
    public function isThreatRequestFailed(string $url, string $message): void;

    public function isThreatInvalidResponse(string $url, int $status): void;

    public function isThreatFound(string $url, string $host): void;
}
