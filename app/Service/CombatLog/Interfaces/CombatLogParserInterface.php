<?php

namespace App\Service\CombatLog\Interfaces;

use App\Logic\CombatLog\BaseEvent;

interface CombatLogParserInterface
{
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool;
}