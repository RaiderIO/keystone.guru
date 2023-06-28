<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute;
use Illuminate\Support\Collection;

interface ResultEventDungeonRouteServiceInterface
{
    /**
     * @param string $combatLogFilePath
     *
     * @return Collection|DungeonRoute[]
     */
    public function convertCombatLogToDungeonRoutes(string $combatLogFilePath): Collection;
}
