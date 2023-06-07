<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

interface CombatLogDungeonRouteServiceInterface
{
    public function getResultEvents(string $combatLogFilePath): Collection;
    
    public function convertCombatLogToDungeonRoute(string $combatLogFilePath): DungeonRoute;

    public function generateMapIconsFromEvents(
        Dungeon $dungeon, 
        MappingVersion $mappingVersion, 
        Collection $resultEvents,
        ?DungeonRoute $dungeonRoute = null,
        bool $save = false
    ): Collection;
}
