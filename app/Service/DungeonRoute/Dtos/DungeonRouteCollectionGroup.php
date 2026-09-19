<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * One section of a collection as it is shown: a dungeon's slot or group, or the flat list of a free-form collection
 * being edited.
 */
readonly class DungeonRouteCollectionGroup
{
    /**
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    public function __construct(
        /** The dungeon of this slot or group; null for the flat edit list. */
        public ?Dungeon   $dungeon,
        /** The routes of this section, in the order they are shown in. */
        public Collection $dungeonRoutes,
    ) {
    }
}
