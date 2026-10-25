<?php

namespace App\Service\UrlThreat;

use App\Service\UrlThreat\Logging\UrlThreatServiceLoggingInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Looks a URL's host up on Cloudflare's malware-blocking resolver (1.1.1.2) over DNS-over-HTTPS. It answers a
 * blocked host with 0.0.0.0 instead of its real address. Free and keyless, but it only judges the host, not the path.
 */
class UrlThreatService implements UrlThreatServiceInterface
{
    private const string DNS_QUERY_URL = 'https://security.cloudflare-dns.com/dns-query';

    private const int DNS_TYPE_A = 1;

    private const string BLOCKED_ADDRESS = '0.0.0.0';

    private const int TIMEOUT_SECONDS = 2;

    public function __construct(
        private readonly UrlThreatServiceLoggingInterface $log,
    ) {
    }

    public function isThreat(string $url): bool
    {
        if (!config('services.url_threat_check.enabled')) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        // An IP address has nothing to resolve
        if (!is_string($host) || $host === '' || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->accept('application/dns-json')
                ->get(self::DNS_QUERY_URL, ['name' => $host, 'type' => 'A']);
        } catch (ConnectionException $exception) {
            $this->log->isThreatRequestFailed($url, $exception->getMessage());

            return false;
        }

        if (!$response->successful()) {
            $this->log->isThreatInvalidResponse($url, $response->status());

            return false;
        }

        /** @var array<int, array{type?: int, data?: string}> $answers */
        $answers = $response->json('Answer') ?? [];

        foreach ($answers as $answer) {
            if (($answer['type'] ?? null) === self::DNS_TYPE_A && ($answer['data'] ?? null) === self::BLOCKED_ADDRESS) {
                $this->log->isThreatFound($url, $host);

                return true;
            }
        }

        return false;
    }
}
