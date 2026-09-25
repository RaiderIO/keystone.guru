<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;

interface DungeonRouteServiceInterface
{
    public function updatePopularity(): int;

    public function updateRating(): int;

    public function refreshOutdatedThumbnails(): int;

    public function deleteExpiredDungeonRoutes(): int;

    public function touchRoutesForTeam(int $teamId): int;

    public function upgradeMappingVersion(DungeonRoute $dungeonRoute): void;

    /**
     * Finds the dungeon start map icon in $mappingVersionId that matches the route's currently chosen start,
     * matched by the map_icons.comment field. Returns null when the route has no chosen start, the old icon is
     * gone, it has no comment to match on, or no matching icon exists in that mapping version (which later falls
     * back to the first dungeon start).
     */
    public function findDungeonStartMapIconIdForMappingVersion(DungeonRoute $dungeonRoute, int $mappingVersionId): ?int;

    public function upgradeMappingVersionBulk(MappingVersion $mappingVersion): int;

    public function publishScheduledDungeonRoutes(): int;
}
