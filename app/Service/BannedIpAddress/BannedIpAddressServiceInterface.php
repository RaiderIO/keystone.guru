<?php

namespace App\Service\BannedIpAddress;

use App\Models\BannedIpAddress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface BannedIpAddressServiceInterface
{
    /**
     * Bans an IP address (or CIDR range) and refreshes the cached lookup used by the enforcement middleware.
     * $createdBy must always identify the admin who requested the ban - CLI incident response is
     * expected to pass the ID of the admin performing it, not a system/null actor.
     */
    public function ban(string $ipAddress, ?string $reason, ?Carbon $expiresAt, int $createdBy): BannedIpAddress;

    /**
     * Removes a ban and refreshes the cached lookup used by the enforcement middleware.
     */
    public function unban(BannedIpAddress $bannedIpAddress): void;

    /**
     * Whether the given IP address is currently banned - matches single IPs and CIDR ranges, and
     * ignores bans whose expires_at has passed. Backed by a cached lookup so this is safe to call
     * on every request.
     */
    public function isBanned(string $ipAddress): bool;

    /**
     * @return Collection<int, BannedIpAddress>
     */
    public function all(): Collection;
}
