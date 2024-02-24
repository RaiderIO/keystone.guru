<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;

interface CreateRouteDungeonRouteServiceInterface
{
    /**
     * @return DungeonRoute
     */
    public function convertCreateRouteBodyToDungeonRoute(CreateRouteBody $createRouteBody): DungeonRoute;

    /**
     * @return CreateRouteBody
     */
    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody;
}
