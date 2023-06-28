<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;
use Exception;

class CombatLogDungeonRouteServiceLogging extends StructuredLogging implements CombatLogDungeonRouteServiceLoggingInterface
{
    /**
     * @param string $combatLogFilePath
     * @return void
     */
    public function convertCombatLogToDungeonRoutesStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function convertCombatLogToDungeonRoutesEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function saveEnemyPositionFromResultEventsStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function saveEnemyPositionFromResultEventsEnd(): void
    {
        $this->end(__METHOD__);
    }


}
