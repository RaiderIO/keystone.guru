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
}
