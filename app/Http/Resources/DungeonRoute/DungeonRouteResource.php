<?php

namespace App\Http\Resources\DungeonRoute;

use App\Http\Resources\AffixGroup\AffixGroupResource;
use App\Http\Resources\KillZone\KillZoneResource;
use App\Http\Resources\User\UserResource;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="DungeonRoute")
 * @OA\Property(property="dungeonId", type="integer", example=1)
 * @OA\Property(property="publicKey", type="string", example="MS4cR1S")
 * @OA\Property(property="title", type="string", example="My dungeon route")
 * @OA\Property(property="enemyForces", type="integer", example=100)
 * @OA\Property(property="enemyForcesRequired", type="integer", example=100)
 * @OA\Property(property="expiresAt", type="string", format="date-time", example="2021-01-01T00:00:00Z")
 * @OA\Property(property="pulls", type="array", @OA\Items(ref="#/components/schemas/Pull"))
 * @OA\Property(property="author",type="object",ref="#/components/schemas/User")
 * @OA\Property(property="affixGroups", type="array", @OA\Items(ref="#/components/schemas/AffixGroup"))
 * @OA\Property(property="links",type="object",ref="#/components/schemas/DungeonRouteLinks")
 *
 * @mixin DungeonRoute
 */
class DungeonRouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'dungeonId'           => $this->dungeon_id,
            'mappingVersion'      => $this->mappingVersion->version,
            'publicKey'           => $this->public_key,
            'title'               => $this->title,
            'enemyForces'         => $this->enemy_forces,
            'enemyForcesRequired' => $this->mappingVersion->enemy_forces_required,
            'expiresAt'           => $this->expires_at,
            'pulls'               => $this->killZones()->with('killZoneEnemies')->get()->map(
                fn(KillZone $killZone) => new KillZoneResource($killZone)
            )->toArray(),
            'author'              => new UserResource($this->author),
            'affixGroups'         => $this->affixes->map(
                fn(AffixGroup $affixGroup
                ) => new AffixGroupResource($affixGroup->setRelation('expansion', $this->dungeon->expansion))
            )->toArray(),
            'links'               => new DungeonRouteLinksResource($this),
        ];
    }
}
