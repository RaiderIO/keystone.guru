<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CombatLogServiceLogging extends StructuredLogging implements CombatLogServiceLoggingInterface
{

    /**
     * @param string $rawEvent
     * @return void
     */
    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
