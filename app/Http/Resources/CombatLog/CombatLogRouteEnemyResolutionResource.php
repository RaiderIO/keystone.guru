<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteEnemyResolution")
 * @OA\Property(property="id", type="integer", example=123)
 * @OA\Property(property="dungeon_id", type="integer", example=72)
 * @OA\Property(property="floor_id", type="integer", example=394)
 * @OA\Property(property="mapping_version_id", type="integer", example=904)
 * @OA\Property(property="npc_id", type="integer", nullable=true, example=261557)
 * @OA\Property(property="enemy_id", type="integer", example=139620)
 * @OA\Property(property="dungeon_route_id", type="integer", nullable=true, example=456)
 * @OA\Property(property="dungeon_route_public_key", type="string", nullable=true, example="MS4cR1S")
 * @OA\Property(property="lat", type="number", format="float", example=-128.5)
 * @OA\Property(property="lng", type="number", format="float", example=192.25)
 * @OA\Property(property="enemy_lat", type="number", format="float", example=-130.75)
 * @OA\Property(property="enemy_lng", type="number", format="float", example=188.5)
 * @OA\Property(property="distance", type="number", format="float", example=49.7)
 * @OA\Property(property="weighted_distance", type="number", format="float", example=49.7)
 * @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-16T10:00:00+00:00")
 *
 * @mixin CombatLogRouteEnemyResolution
 */
class CombatLogRouteEnemyResolutionResource extends JsonResource
{
    public function __construct(
        CombatLogRouteEnemyResolution $resource,
        private readonly ?string      $dungeonRoutePublicKey,
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
            'enemy_id'                 => $this->enemy_id,
            'dungeon_route_id'         => $this->dungeon_route_id,
            'dungeon_route_public_key' => $this->dungeonRoutePublicKey,
            'lat'                      => $this->lat,
            'lng'                      => $this->lng,
            'enemy_lat'                => $this->enemy_lat,
            'enemy_lng'                => $this->enemy_lng,
            'distance'                 => $this->distance,
            'weighted_distance'        => $this->weighted_distance,
            'created_at'               => $this->created_at->toIso8601String(),
        ];
    }
}
