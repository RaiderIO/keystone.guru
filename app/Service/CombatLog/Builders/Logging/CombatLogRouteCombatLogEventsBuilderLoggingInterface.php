<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CombatLogRouteCombatLogEventsBuilderLoggingInterface extends CombatLogRouteDungeonRouteBuilderLoggingInterface
{
    public function getCombatLogEventsStart(string $runId): void;

    public function getCombatLogEventsEnemyNotFound(int $npcId, int $mdtId): void;

    public function getCombatLogEventsEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void;

    public function getCombatLogEventsSpellFloorCouldNotBeResolved(?int $uiMapId): void;

    public function getCombatLogEventsPlayerDeathFloorCouldNotBeResolved(?int $uiMapId): void;

    public function getCombatLogEventsEnd(): void;
}
