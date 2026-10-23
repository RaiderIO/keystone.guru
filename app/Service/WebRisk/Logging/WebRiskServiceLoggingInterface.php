<?php

namespace App\Service\WebRisk\Logging;

interface WebRiskServiceLoggingInterface
{
    public function isThreatRequestFailed(string $url, string $message): void;

    public function isThreatInvalidResponse(string $url, int $status): void;

    /**
     * @param array<int, string> $threatTypes
     */
    public function isThreatFound(string $url, array $threatTypes): void;
}
