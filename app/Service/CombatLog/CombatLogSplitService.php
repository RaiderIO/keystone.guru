<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use App\Service\CombatLog\Splitters\ChallengeModeSplitter;
use App\Service\CombatLog\Splitters\CombatLogSplitterInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CombatLogSplitService implements CombatLogSplitServiceInterface
{

    public function __construct(
        private readonly CombatLogServiceInterface             $combatLogService,
        private readonly CombatLogSplitServiceLoggingInterface $log)
    {
    }

    public function splitCombatLogOnChallengeModes(string $filePath): Collection
    {
        return $this->splitCombatLogUsingSplitter($filePath, new ChallengeModeSplitter($this->combatLogService));
    }

    public function splitCombatLogOnDungeonZoneChanges(string $filePath): Collection
    {
        return collect();
//        return $this->splitCombatLogUsingSplitter($filePath, new ZoneChangeSplitter($this->combatLogService));
    }

    private function splitCombatLogUsingSplitter(string $filePath, CombatLogSplitterInterface $splitter) : Collection
    {
        $this->log->splitCombatLogUsingSplitterStart($filePath, get_class($splitter));
        try {
            $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;
            $result         = collect();
            // We don't need to do anything if there are no runs
            // If there's one run, we may still want to trim the fat of the log and keep just
            // the one challenge mode that's in there
            if ($this->combatLogService->getChallengeModes($targetFilePath)->count() <= 0) {
                $this->log->splitCombatLogUsingSplitterNoChallengeModesFound();

                return $result;
            }

            $result = $splitter->splitCombatLog($targetFilePath);
        } finally {
            $this->log->splitCombatLogUsingSplitterEnd();
        }

        return $result;
    }
}
