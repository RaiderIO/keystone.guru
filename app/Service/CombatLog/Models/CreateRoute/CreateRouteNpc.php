<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;
use DateTime;

class CreateRouteNpc
{
    public int $npcId;

    public string $spawnUid;

    public string $engagedAt;

    public string $diedAt;

    public CreateRouteCoord $coord;

    private Carbon $engagedAtCarbon;

    private Carbon $diedAtCarbon;

    /**
     * @param int              $npcId
     * @param string           $spawnUid
     * @param string           $engagedAt
     * @param string           $diedAt
     * @param CreateRouteCoord $coord
     */
    public function __construct(int $npcId, string $spawnUid, string $engagedAt, string $diedAt, CreateRouteCoord $coord)
    {
        $this->npcId     = $npcId;
        $this->spawnUid  = $spawnUid;
        $this->engagedAt = $engagedAt;
        $this->diedAt    = $diedAt;
        $this->coord     = $coord;
    }

    /**
     * @return Carbon
     */
    public function getEngagedAt(): Carbon
    {
        return $this->engagedAtCarbon ??
            $this->engagedAtCarbon = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->engagedAt);
    }

    /**
     * @return Carbon
     */
    public function getDiedAt(): Carbon
    {
        return $this->diedAtCarbon ??
            $this->diedAtCarbon = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->diedAt);
    }

    /**
     * @return string
     */
    public function getUniqueUid(): string
    {
        return sprintf('%d-%s', $this->npcId, $this->spawnUid);
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
            CreateRouteCoord::createFromArray($body['coord'])
        );
    }
}
