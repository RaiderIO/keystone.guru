<?php

namespace App\Service\CombatLog\Builders\Logging;

class CombatLogRouteCombatLogEventsBuilderLogging extends CombatLogRouteDungeonRouteBuilderLogging implements CombatLogRouteCombatLogEventsBuilderLoggingInterface
{
    public function getCombatLogEventsStart(string $runId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsEnemyNotFound(int $npcId, int $mdtId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsSpellFloorCouldNotBeResolved(?int $uiMapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsPlayerDeathFloorCouldNotBeResolved(?int $uiMapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
