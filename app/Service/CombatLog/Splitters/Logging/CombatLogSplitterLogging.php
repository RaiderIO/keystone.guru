<?php

namespace App\Service\CombatLog\Splitters\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogSplitterLogging extends RollbarStructuredLogging implements CombatLogSplitterLoggingInterface
{

    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
