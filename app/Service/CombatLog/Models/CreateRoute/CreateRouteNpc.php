<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use App\Models\Enemy;
use Carbon\Carbon;

class CreateRouteNpc
{
    private Carbon $engagedAtCarbon;

    private Carbon $diedAtCarbon;

    private ?Enemy $resolvedEnemy = null;

    public function __construct(public int $npcId, public string $spawnUid, public string $engagedAt, public string $diedAt, public CreateRouteCoord $coord)
    {
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
    public function getUniqueId(): string
    {
        return sprintf('%d-%s', $this->npcId, $this->spawnUid);
    }

    /**
     * @return Enemy|null
     */
    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    /**
     * @param Enemy|null $enemy
     * @return self
     */
    public function setResolvedEnemy(?Enemy $enemy): self
    {
        $this->resolvedEnemy = $enemy;

        return $this;
    }

    /**
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
