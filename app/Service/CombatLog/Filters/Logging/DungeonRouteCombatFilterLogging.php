<?php

namespace App\Service\CombatLog\Filters\Logging;

use App\Logging\RollbarStructuredLogging;

class DungeonRouteCombatFilterLogging extends RollbarStructuredLogging implements DungeonRouteCombatFilterLoggingInterface
{
    /**
     * {@inheritDoc}
     */
    public function parseChallengeModeStartFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
