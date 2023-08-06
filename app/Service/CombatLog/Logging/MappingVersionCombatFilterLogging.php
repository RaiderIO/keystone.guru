<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;
use App\Service\CombatLog\Logging\MappingVersionCombatFilterLoggingInterface;

class MappingVersionCombatFilterLogging extends StructuredLogging implements MappingVersionCombatFilterLoggingInterface
{

    /**
     * @inheritDoc
     */
    public function parseZoneChangeFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
