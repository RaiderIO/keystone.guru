<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CreateRouteBodyCorrectionBuilderLoggingInterface
{
    public function getCreateRouteBodyStart(): void;

    public function getCreateRouteBodyEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void;

    public function getCreateRouteBodyEnd(): void;
}
