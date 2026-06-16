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
     * the obsolete enemy set if any new overpulls were recorded.
     *
     * @param Collection<int, Enemy> $resolvedKillsInOrder Resolved enemies in temporal/stream order.
     */
    public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder): void;
}
