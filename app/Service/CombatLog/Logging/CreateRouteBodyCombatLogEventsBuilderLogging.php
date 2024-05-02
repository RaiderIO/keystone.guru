<?php

namespace App\Service\CombatLog\Logging;

class CreateRouteBodyCombatLogEventsBuilderLogging extends CreateRouteBodyDungeonRouteBuilderLogging implements CreateRouteBodyCombatLogEventsBuilderLoggingInterface
{
    public function getCombatLogEventsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCombatLogEventsEnd(): void
    {
        $this->end(__METHOD__);
    }

}
