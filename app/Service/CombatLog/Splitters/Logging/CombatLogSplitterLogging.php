<?php

namespace App\Service\CombatLog\Splitters\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogSplitterLogging extends StructuredLogging implements CombatLogSplitterLoggingInterface
{
    use InteractsWithRollbar;

    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
