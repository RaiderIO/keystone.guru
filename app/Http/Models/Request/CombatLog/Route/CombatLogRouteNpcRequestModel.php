<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use App\Models\Enemy;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * @OA\Schema(schema="CombatLogRouteNpc")
 * @OA\Property(property="npcId", type="integer")
 * @OA\Property(property="spawnUid", type="string")
 * @OA\Property(property="engagedAt", type="string", format="date-time")
 * @OA\Property(property="diedAt", type="string", format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRouteNpcRequestModel extends RequestModel implements Arrayable
{
    private Carbon $engagedAtCarbon;

    private Carbon $diedAtCarbon;

    private ?Enemy $resolvedEnemy = null;

    public function __construct(
        public ?int                             $npcId = null,
        public ?string                          $spawnUid = null,
        public ?string                          $engagedAt = null,
        public ?string                          $diedAt = null,
        public ?CombatLogRouteCoordRequestModel $coord = null)
    {
    }

    public function getEngagedAt(): Carbon
    {
        return $this->engagedAtCarbon ??
            $this->engagedAtCarbon = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->engagedAt);
    }

    public function getDiedAt(): Carbon
    {
        return $this->diedAtCarbon ??
            $this->diedAtCarbon = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->diedAt);
    }

    public function getUniqueId(): string
    {
        return sprintf('%d-%s', $this->npcId, $this->spawnUid);
    }

    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    public function setResolvedEnemy(?Enemy $enemy): self
    {
        $this->resolvedEnemy = $enemy;

        return $this;
    }
}
