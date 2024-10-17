<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\Dtos\ChallengeMode;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

interface CombatLogServiceInterface
{
    /**
     * @return Collection<BaseEvent>
     */
    public function parseCombatLogToEvents(string $filePath): Collection;

    public function parseCombatLogStreaming(string $filePath, callable $callable): void;

    /**
     * @return Collection<ChallengeMode>
     */
    public function getChallengeModes(string $filePath): Collection;

    /**
     * @return Collection<ChallengeMode>
     */
    public function getUiMapIds(string $filePath): Collection;

    /**
     * @return Collection<BaseResultEvent>
     */
    public function getResultEventsForChallengeMode(string $combatLogFilePath): Collection;

    /**
     * @return Collection<BaseResultEvent>
     */
    public function getResultEventsForDungeonOrRaid(string $combatLogFilePath): Collection;

    public function extractCombatLog(string $filePath): ?string;

    public function compressCombatLog(string $filePathToTxt): string;

    /**
     * Iterates over a combat log and calls the callback for each event.
     */
    public function parseCombatLog(string $filePath, callable $callback): void;

    public function saveCombatLogToFile(Collection $rawEvents, string $filePath): bool;
}
