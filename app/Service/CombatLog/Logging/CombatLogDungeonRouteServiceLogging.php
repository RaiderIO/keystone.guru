<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogDungeonRouteServiceLogging extends StructuredLogging implements CombatLogDungeonRouteServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function convertCombatLogToDungeonRoutesStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function convertCombatLogToDungeonRoutesEnd(): void
    {
        $this->end(__METHOD__);
    }
}
