<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogRouteDungeonRouteServiceLogging extends RollbarStructuredLogging implements CombatLogRouteDungeonRouteServiceLoggingInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCombatLogRouteStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function getCombatLogRouteEnemyEngagedInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function getCombatLogRouteEnemyKilledInvalidNpcId(int $npcId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function getCombatLogRouteEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function saveChallengeModeRunUnableToFindFloor(int $uiMapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * {@inheritDoc}
     */
    public function generateMapIconsUnableToFindFloor(string $uniqueId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
