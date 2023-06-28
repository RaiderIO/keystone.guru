<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

interface CreateRouteDungeonRouteServiceInterface
{
    /**
     * @param CreateRouteBody $createRouteBody
     * @return DungeonRoute
     */
    public function convertCreateRouteBodyToDungeonRoute(CreateRouteBody $createRouteBody): DungeonRoute;

    /**
     * @param string $combatLogFilePath
     *
     * @return CreateRouteBody
     */
    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody;
}
