<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\Models\ChallengeMode;
use Illuminate\Support\Collection;

interface CombatLogServiceInterface
{
    /**
     * @param string $filePath
     *
     * @return Collection|BaseEvent[]
     */
    public function parseCombatLogToEvents(string $filePath): Collection;

    /**
     * @param string $filePath
     *
     * @return Collection|ChallengeMode[]
     */
    public function getChallengeModes(string $filePath): Collection;

    /**
     * @param string $filePath
     * @return Collection A list of file paths that the logs were saved to
     */
    public function splitCombatLogOnChallengeModes(string $filePath): Collection;
}
