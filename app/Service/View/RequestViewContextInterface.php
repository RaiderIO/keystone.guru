<?php

namespace App\Service\View;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;

/**
 * Per-request, request-scoped context exposing memoized values derived from the current user/request.
 *
 * Bound with the container's scoped() lifecycle so it is reset between requests under Octane.
 */
interface RequestViewContextInterface
{
    public function getUserOrDefaultRegion(): GameServerRegion;

    public function getCurrentExpansion(): Expansion;

    public function getCurrentUserGameVersion(): GameVersion;

    public function isUserAdmin(): bool;

    public function isAdFree(): bool;

    /**
     * The number of user reports still awaiting an admin's attention.
     */
    public function getNumOpenUserReports(): int;

    /**
     * The number of notifications the current user has not read, or 0 for a guest. Rendered into
     * the navigation on every page, so this is memoized per request.
     */
    public function getNumUnreadNotifications(): int;

    /**
     * Whether the current route is only reachable by a guest, i.e. it is behind the `guest`
     * middleware and would bounce an authenticated user elsewhere.
     */
    public function isCurrentRouteGuestOnly(): bool;
}
