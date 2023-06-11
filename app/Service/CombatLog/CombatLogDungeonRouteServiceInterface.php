<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

interface CombatLogDungeonRouteServiceInterface
{
    /**
     * @param string $combatLogFilePath
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEvents(string $combatLogFilePath): Collection;

    /**
     * @param string $combatLogFilePath
     * @return Collection|DungeonRoute[]
     */
    public function convertCombatLogToDungeonRoutes(string $combatLogFilePath): Collection;

    /**
     * @param \App\Models\Dungeon $dungeon
     * @param \App\Models\Mapping\MappingVersion $mappingVersion
     * @param \Illuminate\Support\Collection $resultEvents
     * @param \App\Models\DungeonRoute|null $dungeonRoute
     * @return void
     */
    public function generateMapIconsFromEvents(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        Collection     $resultEvents,
        ?DungeonRoute  $dungeonRoute = null
    ): void;
}
