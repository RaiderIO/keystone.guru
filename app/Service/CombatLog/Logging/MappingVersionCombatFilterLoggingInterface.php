<?php

namespace App\Service\CombatLog\Logging;

interface MappingVersionCombatFilterLoggingInterface
{

    /**
     * @return void
     */
    public function parseZoneChangeFound(int $lineNr): void;
}
