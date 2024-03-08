<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class DungeonRouteCombatFilterLogging extends StructuredLogging implements DungeonRouteCombatFilterLoggingInterface
{
    /**
     * {@inheritDoc}
     */
    public function parseChallengeModeStartFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
