<?php

namespace App\Service\WebRisk;

use App\Service\WebRisk\Logging\WebRiskServiceLoggingInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Looks URLs up with the Web Risk Lookup API. The free Safe Browsing API is licensed for non-commercial use only.
 */
class WebRiskService implements WebRiskServiceInterface
{
    private const string SEARCH_URL = 'https://webrisk.googleapis.com/v1/uris:search';

    private const array THREAT_TYPES = [
        'MALWARE',
        'SOCIAL_ENGINEERING',
        'UNWANTED_SOFTWARE',
    ];

    private const int TIMEOUT_SECONDS = 2;

    public function __construct(
        private readonly WebRiskServiceLoggingInterface $log,
    ) {
    }

    public function isThreat(string $url): bool
    {
        $apiKey = config('services.google_web_risk.api_key');

        if (!is_string($apiKey) || $apiKey === '') {
            return false;
        }

        // threatTypes must repeat as a bare key, which Laravel's array query encoding (threatTypes[0]=) does not produce
        $query = sprintf(
            '%s&%s',
            http_build_query(['uri' => $url]),
            implode('&', array_map(static fn(string $threatType): string => sprintf('threatTypes=%s', $threatType), self::THREAT_TYPES)),
        );

        try {
            // The key goes in a header: a connection error's message carries the full request URL into the logs
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders(['X-Goog-Api-Key' => $apiKey])
                ->get(sprintf('%s?%s', self::SEARCH_URL, $query));
        } catch (ConnectionException $exception) {
            $this->log->isThreatRequestFailed($url, $exception->getMessage());

            return false;
        }

        if (!$response->successful()) {
            $this->log->isThreatInvalidResponse($url, $response->status());

            return false;
        }

        /** @var array<int, string> $threatTypes */
        $threatTypes = $response->json('threat.threatTypes') ?? [];

        if ($threatTypes === []) {
            return false;
        }

        $this->log->isThreatFound($url, $threatTypes);

        return true;
    }
}
