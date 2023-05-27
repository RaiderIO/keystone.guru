<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute;
use Illuminate\Support\Collection;

interface CombatLogDungeonRouteServiceInterface
{
    public function convertCombatLogToDungeonRoute(string $combatLogFilePath): DungeonRoute;
}
