<?php

namespace App\Service\CombatLog\Filters\Logging;

use App\Logging\RollbarStructuredLogging;

class MappingVersionCombatFilterLogging extends RollbarStructuredLogging implements MappingVersionCombatFilterLoggingInterface
{
    /**
     * {@inheritDoc}
     */
    public function parseZoneChangeFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
