<?php

namespace App\Service\Cloudflare;

use App\Service\Cache\CacheServiceInterface;
use App\Service\Cloudflare\Logging\CloudflareServiceLoggingInterface;
use App\Service\Traits\Curl;

/**
 * https://www.cloudflare.com/ips/
 */
class CloudflareService implements CloudflareServiceInterface
{
    use Curl;

    private const CLOUDFLARE_BASE_URL = 'https://cloudflare.com/';

    public function __construct(
        private readonly CacheServiceInterface             $cacheService,
        private readonly CloudflareServiceLoggingInterface $log,
    ) {
    }

    public function getIpRanges(bool $useCache = true): array
    {
        return array_merge($this->getIpRangesV4($useCache), $this->getIpRangesV6($useCache));
    }

    public function getIpRangesV4(bool $useCache = true): array
    {
        return $this->cacheService->rememberWhen($useCache, 'cloudflare:ip-ranges-v4', function () {
            $response = $this->curlGet(sprintf('%s/ips-v4', self::CLOUDFLARE_BASE_URL));

            return $this->validateIpAddressRanges($response, FILTER_FLAG_IPV4);
        });
    }

    public function getIpRangesV6(bool $useCache = true): array
    {
        return $this->cacheService->rememberWhen($useCache, 'cloudflare:ip-ranges-v6', function () {
            $response = $this->curlGet(sprintf('%s/ips-v6', self::CLOUDFLARE_BASE_URL));

            return $this->validateIpAddressRanges($response, FILTER_FLAG_IPV6);
        });
    }

    private function validateIpAddressRanges(string $ipAddresses, int $options): array
    {
        $result = [];

        // Comes from the internet - so don't use PHP_EOL, filter to remove empty lines
        $ipRanges = array_filter(explode("\n", $ipAddresses));

        foreach ($ipRanges as $ipRange) {
            if ($this->validateCidr($ipRange)) {
                $result[] = $ipRange;
            } else {
                $this->log->getIpRangesInvalidIpAddress($ipRange);
            }
        }

        return $result;
    }

    /**
     * @param  string $cidr
     * @return bool
     *                https://gist.github.com/pavinjosdev/cb1d636ea9dc2bd201d54107d10650c5
     */
    private function validateCidr(string $cidr): bool
    {
        $parts = explode('/', $cidr);
        if (count($parts) != 2) {
            return false;
        }

        $ip      = $parts[0];
        $netmask = $parts[1];

        if (!preg_match("/^\d+$/", $netmask)) {
            return false;
        }

        $netmask = intval($parts[1]);

        if ($netmask < 0) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $netmask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $netmask <= 128;
        }

        return false;
    }
}
