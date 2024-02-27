<?php

namespace App\Service\CombatLog\Logging;

interface MappingVersionCombatFilterLoggingInterface
{
    public function parseZoneChangeFound(int $lineNr): void;
}
