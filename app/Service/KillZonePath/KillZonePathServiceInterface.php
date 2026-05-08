<?php

namespace App\Service\KillZonePath;

use App\Models\DungeonRoute\DungeonRoute;

interface KillZonePathServiceInterface
{
    /**
     * Calculate the full kill-zone path for a route.
     *
     * Returns one segment per consecutive kill-zone pair (plus an optional
     * dungeon-start-to-first-pull segment). Each segment is an ordered array
     * of lat/lng/floor_id objects representing the shortest path between those
     * two kill-zones, passing through floor-switch markers as needed.
     *
     * @return array<array<array{lat: float, lng: float, floor_id: int|null}>>
     */
    public function calculateForRoute(DungeonRoute $dungeonRoute, bool $useFacade): array;
}
