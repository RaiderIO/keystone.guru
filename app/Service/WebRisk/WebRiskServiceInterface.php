<?php

namespace App\Service\WebRisk;

interface WebRiskServiceInterface
{
    /**
     * Whether Google Web Risk lists the URL as malware, social engineering or unwanted software.
     *
     * `false` when no API key is configured or the lookup fails, so an unavailable API never blocks the caller.
     */
    public function isThreat(string $url): bool;
}
