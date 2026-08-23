<?php

namespace App\Service\Floor;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Floor\Dtos\ResolvedFloor;

interface FloorResolutionServiceInterface
{
    /**
     * Resolve the floor a dungeon/mapping version should default to (facade floor if the current
     * user is on the facade map style and the mapping version supports it, otherwise the floor
     * flagged `default`).
     */
    public function resolveDefaultFloor(Dungeon $dungeon, MappingVersion $mappingVersion): Floor;

    /**
     * Resolve a raw, request-supplied floor index (a route parameter, so always a string) to an
     * actual Floor - falling back to the default floor when the raw index is non-numeric or
     * matches no floor, and forcing the facade floor when the current user is on the facade map
     * style.
     *
     * @param string $rawFloorIndex The requested floor index, as it arrived on the route.
     */
    public function resolveRequestedFloor(Dungeon $dungeon, MappingVersion $mappingVersion, string $rawFloorIndex): ResolvedFloor;
}
