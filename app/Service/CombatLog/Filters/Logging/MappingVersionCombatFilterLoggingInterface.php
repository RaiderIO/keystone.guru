<?php

namespace App\Service\CombatLog\Filters\Logging;

interface MappingVersionCombatFilterLoggingInterface
{
    public function parseZoneChangeFound(int $lineNr): void;
}
