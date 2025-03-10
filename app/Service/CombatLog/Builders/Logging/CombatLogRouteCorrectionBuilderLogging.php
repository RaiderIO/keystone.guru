<?php

namespace App\Service\CombatLog\Builders\Logging;

use App\Logging\StructuredLogging;

class CombatLogRouteCorrectionBuilderLogging extends StructuredLogging implements CombatLogRouteCorrectionBuilderLoggingInterface
{

    public function getCombatLogRouteStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getCombatLogRouteEnemyCouldNotBeResolved(int $npcId, string $spawnUid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteSpellFloorNotFound(?int $uiMapId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRoutePlayerDeathFloorNotFound(?int $uiMapId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteEnd(): void
    {
        $this->end(__METHOD__);
    }
}
