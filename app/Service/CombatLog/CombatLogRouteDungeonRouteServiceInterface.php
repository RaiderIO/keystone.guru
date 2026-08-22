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

    /**
     * @param bool $debugIcons Whether the resulting body asks the Auto Route Creator for debug map icons. Off by
     *                         default: generating them costs a pass over the whole route and is only of use when
     *                         debugging the ARC itself.
     */
    public function getCombatLogRoute(
        string $combatLogFilePath,
        bool   $dungeonOrRaid = false,
        bool   $debugIcons = false,
    ): ?CombatLogRouteRequestDto;
}
