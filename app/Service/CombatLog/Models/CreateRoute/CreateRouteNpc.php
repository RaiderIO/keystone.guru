<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;

class CreateRouteNpc
{
    public int $npcId;

    public string $spawnUid;

    public Carbon $engagedAt;

    public Carbon $diedAt;

    public CreateRouteNpcCoord $coord;

    /**
     * @param int                 $npcId
     * @param string              $spawnUid
     * @param Carbon              $engagedAt
     * @param Carbon              $diedAt
     * @param CreateRouteNpcCoord $coord
     */
    public function __construct(int $npcId, string $spawnUid, Carbon $engagedAt, Carbon $diedAt, CreateRouteNpcCoord $coord)
    {
        $this->npcId     = $npcId;
        $this->spawnUid  = $spawnUid;
        $this->engagedAt = $engagedAt;
        $this->diedAt    = $diedAt;
        $this->coord     = $coord;
    }
}
