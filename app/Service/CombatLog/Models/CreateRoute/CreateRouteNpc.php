<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;

class CreateRouteNpc
{
    public int $npcId;

    public string $spawnUid;

    public string $engagedAt;

    public string $diedAt;

    public CreateRouteNpcCoord $coord;

    /**
     * @param int $npcId
     * @param string $spawnUid
     * @param string $engagedAt
     * @param string $diedAt
     * @param CreateRouteNpcCoord $coord
     */
    public function __construct(int $npcId, string $spawnUid, string $engagedAt, string $diedAt, CreateRouteNpcCoord $coord)
    {
        $this->npcId     = $npcId;
        $this->spawnUid  = $spawnUid;
        $this->engagedAt = $engagedAt;
        $this->diedAt    = $diedAt;
        $this->coord     = $coord;
    }

    /**
     * @param array $body
     * @return CreateRouteNpc
     */
    public static function createFromArray(array $body): CreateRouteNpc
    {
        return new CreateRouteNpc(
            $body['npcId'],
            $body['spawnUid'],
            $body['engagedAt'],
            $body['diedAt'],
            CreateRouteNpcCoord::createFromArray($body['coord'])
        );
    }
}
