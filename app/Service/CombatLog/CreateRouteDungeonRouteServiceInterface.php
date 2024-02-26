<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;

interface CreateRouteDungeonRouteServiceInterface
{
    public function convertCreateRouteBodyToDungeonRoute(CreateRouteBody $createRouteBody): DungeonRoute;

    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody;
}
