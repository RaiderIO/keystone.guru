<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteBodyDungeonRouteBuilderLoggingInterface
{

    /**
     * @param array $guids
     * @return void
     */
    public function buildKillZonesCreateNewFinalPull(array $guids): void;
}
