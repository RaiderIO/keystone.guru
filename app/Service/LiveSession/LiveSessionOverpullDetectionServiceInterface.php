<?php

namespace App\Service\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;

interface LiveSessionOverpullDetectionServiceInterface
{
    /**
     * Classify each resolved kill as on-route (killed) or off-route (overpulled) and persist the result, after
     * persisting the current in-combat enemy set, which seeds the "current pull" that off-route kills are
     * attributed to. Broadcasting the resulting state is up to the caller.
     *
     * @param Collection<int, Enemy> $resolvedKillsInOrder Resolved enemies in temporal/stream order.
     * @param Collection<int, Enemy> $inCombatEnemies      Enemies currently in combat (resolved).
     *
     * @throws LockTimeoutException When a viewer's overpull edit holds the overpull lock for too long; the overpulls
     *                              of off-route kills are then not written
     */
    public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder, Collection $inCombatEnemies): void;
}
