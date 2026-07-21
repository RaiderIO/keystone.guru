<?php

namespace App\Service\CombatLog\Filters\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class MappingVersionCombatFilterLogging extends StructuredLogging implements MappingVersionCombatFilterLoggingInterface
{
    use InteractsWithRollbar;

    /**
     * {@inheritDoc}
     */
    public function parseZoneChangeFound(int $lineNr): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
