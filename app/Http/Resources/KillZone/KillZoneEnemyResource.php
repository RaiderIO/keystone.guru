<?php

namespace App\Http\Resources\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="PullEnemy")
 * @OA\Property(type="int",property="npcId",example="222923")
 * @OA\Property(type="int",property="mdtIndex",example="5")
 * @OA\Property(type="int",property="enemyForces",example="4")
 *
 * @property KillZoneEnemy $resource
 *
 * @mixin KillZoneEnemy
 */
class KillZoneEnemyResource extends JsonResource
{
    public function __construct(
        KillZoneEnemy                   $resource,
        private readonly MappingVersion $mappingVersion,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        /** @var NpcEnemyForces|null $enemyForces */
        $enemyForces = $this->npc->enemyForcesByMappingVersion($this->mappingVersion->id);

        return [
            'npcId' => $this->npc_id,
            //            'mdtIndex'    => $this->mdt_id,
            'enemyForces' => $enemyForces?->enemy_forces ?? 0,
        ];
    }
}
