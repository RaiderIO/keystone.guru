<?php

namespace App\Service\CombatLog\Filters\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class DungeonRouteCombatFilterLogging extends StructuredLogging implements DungeonRouteCombatFilterLoggingInterface
{
    use InteractsWithRollbar;

    /**
     * {@inheritDoc}
     */
    public function parseChallengeModeStartFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
