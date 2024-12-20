<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CombatLogRouteCorrectionBuilderLoggingInterface
{
    public function getCombatLogRouteStart(): void;

    public function getCombatLogRouteEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void;

    public function getCombatLogRouteEnd(): void;
}
