<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteEnemyFailure")
 * @OA\Property(property="id", type="integer", example=123)
 * @OA\Property(property="dungeon_id", type="integer", example=72)
 * @OA\Property(property="floor_id", type="integer", example=394)
 * @OA\Property(property="mapping_version_id", type="integer", example=904)
 * @OA\Property(property="npc_id", type="integer", nullable=true, example=261557)
 * @OA\Property(property="dungeon_route_id", type="integer", nullable=true, example=456)
 * @OA\Property(property="dungeon_route_public_key", type="string", nullable=true, example="MS4cR1S")
 * @OA\Property(property="lat", type="number", format="float", example=-128.5)
 * @OA\Property(property="lng", type="number", format="float", example=192.25)
 * @OA\Property(property="created_at", type="string", format="date-time", example="2026-08-21T10:00:00+00:00")
 *
 * @mixin CombatLogRouteEnemyFailure
 */
class CombatLogRouteEnemyFailureResource extends JsonResource
{
    public function __construct(
        CombatLogRouteEnemyFailure $resource,
        private readonly ?string   $dungeonRoutePublicKey,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'dungeon_id'               => $this->dungeon_id,
            'floor_id'                 => $this->floor_id,
            'mapping_version_id'       => $this->mapping_version_id,
            'npc_id'                   => $this->npc_id,
            'dungeon_route_id'         => $this->dungeon_route_id,
            'dungeon_route_public_key' => $this->dungeonRoutePublicKey,
            'lat'                      => $this->lat,
            'lng'                      => $this->lng,
            'created_at'               => $this->created_at->toIso8601String(),
        ];
    }
}
