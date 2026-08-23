<?php

namespace App\Service\Floor;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Floor\Dtos\ResolvedFloor;

class FloorResolutionService implements FloorResolutionServiceInterface
{
    public function resolveDefaultFloor(Dungeon $dungeon, MappingVersion $mappingVersion): Floor
    {
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($mappingVersion)
            ->first();

        return $defaultFloor;
    }

    public function resolveRequestedFloor(Dungeon $dungeon, MappingVersion $mappingVersion, string $rawFloorIndex): ResolvedFloor
    {
        $floorIndex = is_numeric($rawFloorIndex) ? (int)$rawFloorIndex : 1;

        /** @var Floor|null $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($mappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            return new ResolvedFloor($this->resolveDefaultFloor($dungeon, $mappingVersion), false);
        }

        return new ResolvedFloor($floor, $floor->index === $floorIndex);
    }
}
