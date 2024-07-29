<?php

namespace App\Service\CombatLog\Builders\Logging;

class CreateRouteBodyCombatLogEventsBuilderLogging extends CreateRouteBodyDungeonRouteBuilderLogging implements CreateRouteBodyCombatLogEventsBuilderLoggingInterface
{
    public function getCombatLogEventsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCombatLogEventsEnemyNotFound(int $npcId, int $mdtId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }


    public function getCombatLogEventsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
