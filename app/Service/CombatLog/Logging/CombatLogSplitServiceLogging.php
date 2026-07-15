<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogSplitServiceLogging extends StructuredLogging implements CombatLogSplitServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function splitCombatLogUsingSplitterStart(string $filePath, string $splitter): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogUsingSplitterNoChallengeModesFound(): void
    {
        $this->warning(__METHOD__);
    }

    public function splitCombatLogUsingSplitterMovingFile(
        string $originalCombatLogPath,
        string $targetCombatLogPath,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogUsingSplitterMovingFileFailed(
        string $originalCombatLogPath,
        string $targetCombatLogPath,
    ): void {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogUsingSplitterEnd(): void
    {
        $this->end(__METHOD__);
    }
}
