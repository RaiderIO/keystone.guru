<?php

namespace App\Service\CombatLog\Splitters\Logging;

use App\Logging\StructuredLoggingInterface;

interface CombatLogSplitterLoggingInterface extends StructuredLoggingInterface
{
    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void;
}
