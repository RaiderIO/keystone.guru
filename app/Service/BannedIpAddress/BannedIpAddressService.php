<?php

namespace App\Service\BannedIpAddress;

use App\Models\BannedIpAddress;
use App\Repositories\Interfaces\BannedIpAddressRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\IpUtils;

class BannedIpAddressService implements BannedIpAddressServiceInterface
{
    private const string CACHE_KEY = 'banned_ip_addresses';

    public function __construct(
        private readonly BannedIpAddressRepositoryInterface $bannedIpAddressRepository,
        private readonly CacheServiceInterface              $cacheService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function ban(string $ipAddress, ?string $reason, ?Carbon $expiresAt, ?int $createdBy): BannedIpAddress
    {
        $bannedIpAddress = $this->bannedIpAddressRepository->create([
            'ip_address' => $ipAddress,
            'reason'     => $reason,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);

        $this->refreshCache();

        return $bannedIpAddress;
    }

    /**
     * {@inheritDoc}
     */
    public function unban(BannedIpAddress $bannedIpAddress): void
    {
        $this->bannedIpAddressRepository->delete($bannedIpAddress);

        $this->refreshCache();
    }

    /**
     * {@inheritDoc}
     */
    public function isBanned(string $ipAddress): bool
    {
        $entries = $this->cacheService->remember(self::CACHE_KEY, fn() => $this->buildCacheEntries(), '5 minutes');

        // This is checked on every request by the global enforcement middleware, so a cache that
        // misbehaves (returns something other than the array we wrote) must never crash the
        // request - fall back to a direct, uncached read instead.
        if (!is_array($entries)) {
            $entries = $this->buildCacheEntries();
        }

        foreach ($entries as $entry) {
            if ($entry['expires_at'] !== null && Carbon::parse($entry['expires_at'])->isPast()) {
                continue;
            }

            if (IpUtils::checkIp($ipAddress, $entry['ip_address'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): Collection
    {
        return $this->bannedIpAddressRepository->all();
    }

    /**
     * Recomputes the full ban list from the database and writes it straight into the cache, so
     * ban/unban take effect immediately instead of waiting for the read-through TTL to expire.
     */
    private function refreshCache(): void
    {
        $this->cacheService->set(self::CACHE_KEY, $this->buildCacheEntries());
    }

    /**
     * @return array<int, array{ip_address: string, expires_at: string|null}>
     */
    private function buildCacheEntries(): array
    {
        return $this->bannedIpAddressRepository->all()
            ->map(static fn(BannedIpAddress $bannedIpAddress): array => [
                'ip_address' => $bannedIpAddress->ip_address,
                'expires_at' => $bannedIpAddress->expires_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
