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
        private readonly CombatLogSplitServiceLoggingInterface $log)
    {
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
        } finally {
            $this->log->splitCombatLogUsingSplitterEnd();
        }

        return $result;
    }
}
