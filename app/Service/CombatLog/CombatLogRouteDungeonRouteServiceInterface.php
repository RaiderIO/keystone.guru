<?php

namespace App\Service\CombatLog;

use App\Http\Models\Request\CombatLog\Route\CombatLogRouteCorrectionRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface CombatLogRouteDungeonRouteServiceInterface
{
    public function convertCombatLogRouteToDungeonRoute(CombatLogRouteRequestModel $combatLogRoute): DungeonRoute;

    /** @return Collection<CombatLogEvent> */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRouteRequestModel $combatLogRoute): Collection;

    public function correctCombatLogRoute(
        CombatLogRouteRequestModel $combatLogRoute,
    ): CombatLogRouteCorrectionRequestModel;

    public function getCombatLogRoute(string $combatLogFilePath): ?CombatLogRouteRequestModel;
}
