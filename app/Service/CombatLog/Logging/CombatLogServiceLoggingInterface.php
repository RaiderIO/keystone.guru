<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogServiceLoggingInterface
{

    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent);
}
