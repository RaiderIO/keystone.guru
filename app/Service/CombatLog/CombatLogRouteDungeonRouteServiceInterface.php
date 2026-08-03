<?php

namespace App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface CombatLogRouteDungeonRouteServiceInterface
{
    public function convertCombatLogRouteToDungeonRoute(CombatLogRouteRequestDto $combatLogRoute): DungeonRoute;

    /**
     * @return Collection<int, CombatLogEvent>
     */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRouteRequestDto $combatLogRoute): Collection;

    public function correctCombatLogRoute(
        CombatLogRouteRequestDto $combatLogRoute,
    ): CombatLogRouteCorrectionRequestDto;

    public function getCombatLogRoute(string $combatLogFilePath, bool $dungeonOrRaid = false): ?CombatLogRouteRequestDto;
}
