<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\Models\ChallengeMode;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use Illuminate\Support\Collection;

interface CombatLogServiceInterface
{
    /**
     * @return Collection|BaseEvent[]
     */
    public function parseCombatLogToEvents(string $filePath): Collection;

    /**
     *
     * @return void
     */
    public function parseCombatLogStreaming(string $filePath, callable $callable): void;

    /**
     * @return Collection|ChallengeMode[]
     */
    public function getChallengeModes(string $filePath): Collection;

    /**
     * @return Collection|ChallengeMode[]
     */
    public function getUiMapIds(string $filePath): Collection;

    /**
     * @return Collection|BaseResultEvent[]
     */
    public function getResultEventsForChallengeMode(string $combatLogFilePath): Collection;

    /**
     * @return Collection
     */
    public function getResultEventsForDungeonOrRaid(string $combatLogFilePath): Collection;

    /**
     * @return string|null
     */
    public function extractCombatLog(string $filePath): ?string;

    /**
     * @return string
     */
    public function compressCombatLog(string $filePathToTxt): string;

    /**
     *
     * @return void
     */
    public function parseCombatLog(string $filePath, callable $callback): void;

    /**
     *
     * @return bool
     */
    public function saveCombatLogToFile(Collection $rawEvents, string $filePath): bool;

}
