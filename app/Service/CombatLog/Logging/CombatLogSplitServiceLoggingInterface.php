<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLoggingInterface;

interface CombatLogSplitServiceLoggingInterface
{
    public function splitCombatLogOnChallengeModesStart(string $filePath): void;

    public function splitCombatLogOnChallengeModesNoChallengeModesFound(): void;

    public function splitCombatLogOnChallengeModesTooBigTimestampGap(float $seconds, string $previousTimestamp, string $timestamp): void;

    public function splitCombatLogOnChallengeModesChallengeModeStartEvent(): void;

    public function splitCombatLogOnChallengeModesCombatLogVersionEvent(): void;

    public function splitCombatLogOnChallengeModesZoneChangeEvent(): void;

    public function splitCombatLogOnChallengeModesMapChangeEvent(): void;

    public function splitCombatLogOnChallengeModesLastRunNotCompleted(): void;

    public function splitCombatLogOnChallengeModesEnd(): void;

    public function resetCurrentChallengeMode(): void;

    public function reset(): void;

    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void;

    public function splitCombatLogOnChallengeModesTimestampNotSet(): void;
}
