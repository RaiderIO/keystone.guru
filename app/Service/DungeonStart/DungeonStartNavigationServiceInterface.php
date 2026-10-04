<?php

namespace App\Service\DungeonStart;

use App\Models\DungeonStart;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonStart\Dtos\DungeonStartNavigation;
use Illuminate\Support\Collection;

interface DungeonStartNavigationServiceInterface
{
    /**
     * Where clicking the dungeon start leads when viewed in the given game version: its target dungeon, or - when it
     * has no target - back to the start that points at its dungeon. Null when it leads nowhere.
     */
    public function resolveNavigation(DungeonStart $dungeonStart, GameVersion $gameVersion): ?DungeonStartNavigation;

    /**
     * @return Collection<int, DungeonStartNavigation> Keyed by dungeon start id; starts that lead nowhere are absent.
     */
    public function getNavigationsForMappingVersion(MappingVersion $mappingVersion, GameVersion $gameVersion): Collection;
}
