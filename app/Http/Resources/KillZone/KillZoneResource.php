<?php

namespace App\Http\Resources\KillZone;

use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Spell\Spell;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="Pull")
 * @OA\Property(type="string",property="description",example="Pull description, if any",nullable=true)
 * @OA\Property(type="array",property="npcs",@OA\Items(type="integer",example="222923"))
 * @OA\Property(type="array",property="spells",@OA\Items(type="integer",example="403631"))
 * @mixin KillZone
 */
class KillZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'npcs'        => $this->killZoneEnemies->map(fn(KillZoneEnemy $enemy) => $enemy->npc_id)->toArray(),
            'spells'      => $this->spells->map(fn(Spell $spell) => $spell->id)->toArray(),
        ];
    }
}
