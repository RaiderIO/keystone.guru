<?php

namespace App\Service\CombatLog;

use Illuminate\Support\Collection;

interface CombatLogSplitServiceInterface
{
    /**
     * @param string $filePath
     * @return Collection<string>
     */
    public function splitCombatLogOnChallengeModes(string $filePath): Collection;

    /**
     * @param string $filePath
     * @return Collection<string>
     */
    public function splitCombatLogOnDungeonZoneChanges(string $filePath): Collection;
}
