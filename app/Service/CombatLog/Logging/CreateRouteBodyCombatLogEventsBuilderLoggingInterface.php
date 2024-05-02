<?php

namespace App\Service\CombatLog\Logging;

interface CreateRouteBodyCombatLogEventsBuilderLoggingInterface extends CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    public function getCombatLogEventsStart(): void;

    public function getCombatLogEventsEnd(): void;
}
