<?php

namespace App\Service\CombatLog\Logging;

interface MappingVersionCombatFilterLoggingInterface
{

    /**
     * @param int $lineNr
     * @return void
     */
    public function parseZoneChangeFound(int $lineNr): void;
}
