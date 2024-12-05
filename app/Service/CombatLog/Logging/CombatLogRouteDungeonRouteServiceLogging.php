<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogRouteDungeonRouteServiceLogging extends RollbarStructuredLogging implements CombatLogRouteDungeonRouteServiceLoggingInterface
{
    public function getCombatLogRouteStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteUnableToGenerateDungeonRoute(): void
    {
        $this->warning(__METHOD__);
    }

    public function getCombatLogRouteEnemyEngagedInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteEnemyKilledInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRoutePlayerDiedUnableToFindCombatantInfo(string $playerGuid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogRouteEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function generateMapIconsUnableToFindFloor(string $uniqueId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
