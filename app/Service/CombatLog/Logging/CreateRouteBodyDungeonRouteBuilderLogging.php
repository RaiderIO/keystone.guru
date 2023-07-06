<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CreateRouteBodyDungeonRouteBuilderLogging extends StructuredLogging implements CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    /**
     * @param array $guids
     * @return void
     */
    public function buildKillZonesCreateNewFinalPull(array $guids): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
