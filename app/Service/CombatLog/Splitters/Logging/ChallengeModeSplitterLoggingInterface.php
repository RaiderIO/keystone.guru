<?php

namespace App\Service\CombatLog\Splitters\Logging;

interface ChallengeModeSplitterLoggingInterface extends CombatLogSplitterLoggingInterface
{
    public function splitCombatLogOnChallengeModesStart(string $filePath): void;

    public function splitCombatLogOnChallengeModesNoChallengeModesFound(): void;

    public function parseCombatLogEventTooBigTimestampGap(float $seconds, string $previousTimestamp, string $timestamp): void;

    public function parseCombatLogEventChallengeModeStartEvent(): void;

    public function parseCombatLogEventCombatLogVersionEvent(): void;

    public function parseCombatLogEventZoneChangeEvent(): void;

    public function parseCombatLogEventMapChangeEvent(): void;

    public function splitCombatLogNoChallengeModesFound(): void;

    public function splitCombatLogLastRunNotCompleted(): void;

    public function splitCombatLogOnChallengeModesEnd(): void;

    public function resetCurrentChallengeMode(): void;

    public function reset(): void;

    public function parseCombatLogEventTimestampNotSet(): void;
}
