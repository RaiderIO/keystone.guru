<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;

class CreateRouteChallengeMode
{
    public Carbon $start;

    public Carbon $end;

    public int $durationMs;

    public int $zoneId;

    public int $level;

    public array $affixes;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $durationMs
     * @param int    $zoneId
     * @param int    $level
     * @param array  $affixes
     */
    public function __construct(Carbon $start, Carbon $end, int $durationMs, int $zoneId, int $level, array $affixes)
    {
        $this->start      = $start;
        $this->end        = $end;
        $this->durationMs = $durationMs;
        $this->zoneId     = $zoneId;
        $this->level      = $level;
        $this->affixes    = $affixes;
    }
}
