<?php

namespace App\Service\MDT;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

interface MDTMappingVersionServiceInterface
{
    public function getMappingVersionAccuracy(MappingVersion $mappingVersion): ?Collection;

    /**
     * Returns the average accuracy (0–100) for a specific floor within the mapping version.
     * Returns null if the dungeon is not supported by MDT or if there are no enemies on that floor.
     */
    public function getFloorAccuracy(MappingVersion $mappingVersion, Floor $floor, ?float $floorUnionSizeOverride = null): ?float;
}
