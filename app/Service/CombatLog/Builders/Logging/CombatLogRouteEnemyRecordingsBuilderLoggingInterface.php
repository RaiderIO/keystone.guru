<?php

namespace App\Service\CombatLog\Builders\Logging;

interface CombatLogRouteEnemyRecordingsBuilderLoggingInterface
{
    public function buildUnableToCalculateMapLocation(int $dungeonRouteId, ?int $npcId, int $floorId): void;

    public function buildSkippingNpcWithoutEnemyForces(int $dungeonRouteId, int $npcId): void;

    public function replaceFailed(int $dungeonRouteId, string $exception): void;
}
