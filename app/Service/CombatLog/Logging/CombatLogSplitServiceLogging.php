<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\RollbarStructuredLogging;

class CombatLogSplitServiceLogging extends RollbarStructuredLogging implements CombatLogSplitServiceLoggingInterface
{
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
        string $targetCombatLogPath
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogUsingSplitterMovingFileFailed(
        string $originalCombatLogPath,
        string $targetCombatLogPath
    ): void {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogUsingSplitterEnd(): void
    {
        $this->end(__METHOD__);
    }

}
