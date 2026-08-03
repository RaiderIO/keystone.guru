<?php

namespace App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCorrectionRequestDTO;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDTO;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface CombatLogRouteDungeonRouteServiceInterface
{
    public function convertCombatLogRouteToDungeonRoute(CombatLogRouteRequestDTO $combatLogRoute): DungeonRoute;

    /**
     * @return Collection<int, CombatLogEvent>
     */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRouteRequestDTO $combatLogRoute): Collection;

    public function correctCombatLogRoute(
        CombatLogRouteRequestDTO $combatLogRoute,
    ): CombatLogRouteCorrectionRequestDTO;

    public function getCombatLogRoute(string $combatLogFilePath, bool $dungeonOrRaid = false): ?CombatLogRouteRequestDTO;
}
