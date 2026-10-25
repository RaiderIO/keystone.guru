<?php

namespace App\Service\UrlThreat;

interface UrlThreatServiceInterface
{
    /**
     * Whether the URL's host is blocked as malware or phishing.
     *
     * `false` when the check is disabled or the lookup fails, so an unavailable resolver never blocks the caller.
     */
    public function isThreat(string $url): bool;
}
