<?php

namespace App\Service\CombatLog\Splitters\Logging;

class ChallengeModeSplitterLogging extends CombatLogSplitterLogging implements ChallengeModeSplitterLoggingInterface
{
    public function splitCombatLogOnChallengeModesStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function splitCombatLogOnChallengeModesNoChallengeModesFound(): void
    {
        $this->info(__METHOD__);
    }

    public function parseCombatLogEventTimestampNotSet(): void
    {
        $this->info(__METHOD__);
    }

    public function parseCombatLogEventTooBigTimestampGap(float $seconds, string $previousTimestamp, string $timestamp): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function parseCombatLogEventChallengeModeStartEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function parseCombatLogEventCombatLogVersionEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function parseCombatLogEventZoneChangeEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function parseCombatLogEventMapChangeEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogNoChallengeModesFound(): void
    {
        $this->info(__METHOD__);
    }

    public function splitCombatLogLastRunNotCompleted(): void
    {
        $this->debug(__METHOD__);
    }

    public function splitCombatLogChallengeModeAndResultMismatched(): void
    {
        $this->warning(__METHOD__);
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
}
