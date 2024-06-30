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

    public function splitCombatLogUsingSplitterEnd(): void
    {
        $this->end(__METHOD__);
    }

}
