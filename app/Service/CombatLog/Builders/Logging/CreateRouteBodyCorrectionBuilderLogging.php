<?php

namespace App\Service\CombatLog\Builders\Logging;

use App\Logging\StructuredLogging;

class CreateRouteBodyCorrectionBuilderLogging extends StructuredLogging implements CreateRouteBodyCorrectionBuilderLoggingInterface
{

    public function getCreateRouteBodyStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCreateRouteBodyEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getCreateRouteBodyEnd(): void
    {
        $this->end(__METHOD__);
    }
}
