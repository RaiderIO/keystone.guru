<?php

namespace App\Service\KillZonePath;

use App\Logic\Structs\LatLng;
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

    /**
     * Returns the shortest path to each kill zone, keyed by kill zone ID.
     * The first entry is the path from the dungeon start to the first kill zone.
     * Subsequent entries are paths between consecutive kill zones.
     * Cross-floor transitions are represented as adjacent waypoints on different floors.
     *
     * @return array<int, LatLng[]>
     */
    public function findPathsToKillZones(DungeonRoute $dungeonRoute): array;
}
