<?php

namespace App\Service\CombatLog;

use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use App\Service\CombatLog\Splitters\ChallengeModeSplitter;
use App\Service\CombatLog\Splitters\CombatLogSplitterInterface;
use App\Service\CombatLog\Splitters\ZoneChangeSplitter;
use Illuminate\Support\Collection;

class CombatLogSplitService implements CombatLogSplitServiceInterface
{

    public function __construct(
        private readonly CombatLogServiceInterface             $combatLogService,
        private readonly DungeonRepositoryInterface            $dungeonRepository,
        private readonly CombatLogSplitServiceLoggingInterface $log
    ) {
    }

    public function splitCombatLogOnChallengeModes(string $filePath): Collection
    {
        return $this->splitCombatLogUsingSplitter(
            $filePath,
            new ChallengeModeSplitter($this->combatLogService)
        );
    }

    public function splitCombatLogOnDungeonZoneChanges(string $filePath): Collection
    {
        return $this->splitCombatLogUsingSplitter(
            $filePath,
            new ZoneChangeSplitter(
                $this->combatLogService,
                $this->dungeonRepository
            )
        );
    }

    private function splitCombatLogUsingSplitter(string $filePath, CombatLogSplitterInterface $splitter): Collection
    {
        $this->log->splitCombatLogUsingSplitterStart($filePath, get_class($splitter));
        try {
            $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;
            $result         = $splitter->splitCombatLog($targetFilePath);

            // If we extracted the file somewhere, move it to where we originally found the file
            if ($targetFilePath !== $filePath) {
                $newResult = collect();
                foreach ($result as $originalCombatLogPath) {
                    // Move file to the correct location
                    $targetCombatLogPath = sprintf('%s/%s', dirname($filePath), basename($originalCombatLogPath));
                    if (rename($originalCombatLogPath, $targetCombatLogPath)) {
                        $newResult->push($targetCombatLogPath);
                        $this->log->splitCombatLogUsingSplitterMovingFile($originalCombatLogPath, $targetCombatLogPath);
                    } else {
                        $this->log->splitCombatLogUsingSplitterMovingFileFailed($originalCombatLogPath, $targetCombatLogPath);
                    }
                }

                $result = $newResult;
            }
        } finally {
            $this->log->splitCombatLogUsingSplitterEnd();
        }

        return $result;
    }
}
