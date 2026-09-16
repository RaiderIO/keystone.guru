<?php

namespace App\Service\CombatLog\Builders\Logging;

use App\Logging\StructuredLogging;

class CombatLogRouteEnemyRecordingsBuilderLogging extends StructuredLogging implements CombatLogRouteEnemyRecordingsBuilderLoggingInterface
{
    public function buildUnableToCalculateMapLocation(int $dungeonRouteId, ?int $npcId, int $floorId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function buildSkippingNpcWithoutEnemyForces(int $dungeonRouteId, int $npcId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function replaceFailed(int $dungeonRouteId, string $exception): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
