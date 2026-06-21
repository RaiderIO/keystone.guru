<?php

namespace App\Service\CombatLog;

use Illuminate\Support\Collection;

interface CombatLogSplitServiceInterface
{
    /**
     * @param  string             $filePath
     * @return Collection<string>
     */
    /**

     * @return Collection<int, mixed>
     */

    public function splitCombatLogOnChallengeModes(string $filePath): Collection;

    /**
     * @param  string             $filePath
     * @return Collection<string>
     */
    /**

     * @return Collection<int, mixed>
     */

    public function splitCombatLogOnDungeonZoneChanges(string $filePath): Collection;
}
