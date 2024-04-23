<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogDungeonRouteServiceLogging extends RollbarStructuredLogging implements CombatLogDungeonRouteServiceLoggingInterface
{
    public function convertCombatLogToDungeonRoutesStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function convertCombatLogToDungeonRoutesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function saveEnemyPositionFromResultEventsStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveEnemyPositionFromResultEventsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
