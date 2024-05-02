<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Illuminate\Support\Collection;

interface CreateRouteDungeonRouteServiceInterface
{
    public function convertCreateRouteBodyToDungeonRoute(CreateRouteBody $createRouteBody): DungeonRoute;

    /** @return Collection<CombatLogEvent */
    public function convertCreateRouteBodyToCombatLogEvents(CreateRouteBody $createRouteBody): Collection;

    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody;
}
