<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogSplitServiceLoggingInterface
{

    public function splitCombatLogOnChallengeModesStart(string $filePath): void;

    public function splitCombatLogOnChallengeModesNoChallengeModesFound(): void;

    public function splitCombatLogOnChallengeModesTooBigTimestampGap(float $seconds): void;

    public function splitCombatLogOnChallengeModesChallengeModeStartEvent(string $rawEvent): void;

    public function splitCombatLogOnChallengeModesCombatLogVersionEvent(string $rawEvent): void;

    public function splitCombatLogOnChallengeModesZoneChangeEvent(string $rawEvent): void;

    public function splitCombatLogOnChallengeModesMapChangeEvent(string $rawEvent): void;

    public function splitCombatLogOnChallengeModesLastRunNotCompleted(): void;

    public function splitCombatLogOnChallengeModesEnd(): void;

    public function resetCurrentChallengeMode(): void;

    public function reset(): void;

    public function generateTargetCombatLogFileNameAttempt(string $saveFilePath): void;
}
