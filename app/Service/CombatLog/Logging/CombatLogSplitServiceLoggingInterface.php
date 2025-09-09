<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogSplitServiceLoggingInterface
{

    public function splitCombatLogUsingSplitterStart(string $filePath, string $splitter): void;

    public function splitCombatLogUsingSplitterNoChallengeModesFound(): void;

    public function splitCombatLogUsingSplitterMovingFile(
        string $originalCombatLogPath,
        string $targetCombatLogPath
    ): void;

    public function splitCombatLogUsingSplitterMovingFileFailed(
        string $originalCombatLogPath,
        string $targetCombatLogPath
    ): void;

    public function splitCombatLogUsingSplitterEnd(): void;
}
