<?php

namespace App\Service\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use Illuminate\Support\Collection;

interface LiveSessionOverpullDetectionServiceInterface
{
    /**
     * Classify each resolved kill as on-route (killed) or off-route (overpulled),
     * persist the result, broadcast the appropriate events, and recompute
     * the obsolete enemy set if any new overpulls were recorded. Also persists and
     * broadcasts the current in-combat enemy set, which seeds the "current pull" used
     * to attribute off-route kills.
     *
     * @param Collection<int, Enemy> $resolvedKillsInOrder Resolved enemies in temporal/stream order.
     * @param Collection<int, Enemy> $inCombatEnemies      Enemies currently in combat (resolved).
     */
    public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder, Collection $inCombatEnemies): void;
}
