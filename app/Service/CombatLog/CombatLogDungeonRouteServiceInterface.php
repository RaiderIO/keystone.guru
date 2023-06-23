<?php

namespace App\Service\CombatLog;

use App\Models\DungeonRoute;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

interface CombatLogDungeonRouteServiceInterface
{
    /**
     * @param string $combatLogFilePath
     *
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEvents(string $combatLogFilePath): Collection;

    /**
     * @param string $combatLogFilePath
     *
     * @return Collection|DungeonRoute[]
     */
    public function convertCombatLogToDungeonRoutes(string $combatLogFilePath): Collection;

    /**
     * @param string $combatLogFilePath
     *
     * @return CreateRouteBody
     */
    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody;
}
