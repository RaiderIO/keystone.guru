<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface ResultEventDungeonRouteServiceInterface
{
    /**
     * @return Collection<DungeonRoute>
     */
    public function convertCombatLogToDungeonRoutes(string $combatLogFilePath): Collection;
}
