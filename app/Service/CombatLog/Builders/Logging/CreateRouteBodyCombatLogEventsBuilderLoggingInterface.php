<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CreateRouteBodyCombatLogEventsBuilderLoggingInterface extends CreateRouteBodyDungeonRouteBuilderLoggingInterface
{
    public function getCombatLogEventsStart(): void;

    public function getCombatLogEventsEnemyNotFound(int $npcId, int $mdtId): void;

    public function getCombatLogEventsEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void;

    public function getCombatLogEventsEnd(): void;
}
