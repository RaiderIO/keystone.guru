<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CombatLogSplitServiceLogging extends StructuredLogging implements CombatLogSplitServiceLoggingInterface
{

    public function splitCombatLogOnChallengeModesStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogOnChallengeModesNoChallengeModesFound(): void
    {
        $this->info(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesTimestampNotSet(): void
    {
        $this->info(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesTooBigTimestampGap(float $seconds, string $previousTimestamp, string $timestamp): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogOnChallengeModesChallengeModeStartEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesCombatLogVersionEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesZoneChangeEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesMapChangeEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesLastRunNotCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogOnChallengeModesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function resetCurrentChallengeMode(): void
    {
        $this->debug(__METHOD__);
    }

    public function reset(): void
    {
        $this->debug(__METHOD__);
    }

    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


}
