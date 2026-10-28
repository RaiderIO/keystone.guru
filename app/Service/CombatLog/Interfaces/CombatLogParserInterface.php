<?php

namespace App\Service\CombatLog\Interfaces;

use App\Logic\CombatLog\BaseEvent;

/**
 * Do we want to keep this combat log event or not? (for whatever purpose)
 */
interface CombatLogParserInterface
{
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool;
}
