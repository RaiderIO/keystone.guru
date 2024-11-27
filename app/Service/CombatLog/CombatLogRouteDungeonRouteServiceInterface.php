<?php

namespace App\Service\CombatLog;

use App\Http\Models\Request\CombatLog\Route\CombatLogRoute;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface CombatLogRouteDungeonRouteServiceInterface
{
    public function convertCombatLogRouteToDungeonRoute(CombatLogRoute $combatLogRoute): DungeonRoute;

    /** @return Collection<CombatLogEvent> */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRoute $combatLogRoute): Collection;

    public function correctCombatLogRoute(CombatLogRoute $combatLogRoute): CombatLogRoute;

    public function getCombatLogRoute(string $combatLogFilePath): CombatLogRoute;
}
