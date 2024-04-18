<?php

namespace App\Service\CombatLogEvent\Logging;

use App\Logging\StructuredLogging;
use Exception;

class CombatLogEventServiceLogging extends StructuredLogging implements CombatLogEventServiceLoggingInterface
{
    public function getCombatLogEventsStart(array $filters): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsException(Exception $e): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCombatLogEventsEnd(): void
    {
        $this->end(__METHOD__);
    }

}
