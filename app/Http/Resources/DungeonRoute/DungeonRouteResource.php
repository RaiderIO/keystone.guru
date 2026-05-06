<?php

namespace App\Http\Resources\DungeonRoute;

use App\Http\Resources\KillZone\KillZoneResource;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
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
 * @OA\Schema(
 *      schema="DungeonRouteWrap",
 *      type="object",
 *      required={"data"},
 *      @OA\Property(property="data", ref="#/components/schemas/DungeonRoute")
 *  )
 *
 * @mixin DungeonRoute
 */
class DungeonRouteResource extends DungeonRouteSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return array_insert_after(parent::toArray($request), 'expiresAt', [
            'pulls' => $this->killZones()->with(['killZoneEnemies.npc.npcEnemyForces', 'dungeonRoute'])->get()->map(
                fn(KillZone $killZone) => new KillZoneResource($killZone),
            )->toArray(),
        ]);
    }
}
