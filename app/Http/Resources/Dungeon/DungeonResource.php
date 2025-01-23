<?php

namespace App\Http\Resources\Dungeon;

use App\Http\Resources\Floor\FloorResource;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="Dungeon")
 * @OA\Property(type="integer",property="id",example="69")
 * @OA\Property(type="string",property="expansion",example="tww",description="The key of the expansion this dungeon belongs to")
 * @OA\Property(type="string",property="name",example="The Stonevault",description="The English dungeon name")
 * @OA\Property(type="string",property="slug",example="the-stonevault",description="The URL friendly slug of the dungeon's name")
 * @OA\Property(type="string",property="key",example="thestonevault",description="The unique identifier for this dungeon")
 * @OA\Property(type="integer",property="zoneId",example="14938",description="The zone ID of the dungeon. Found on the Wowhead page of this dungeon")
 * @OA\Property(type="integer",property="mapId",example="2652",description="The map ID of the dungeon. Found on https://wago.tools/db2/Map")
 * @OA\Property(type="integer",property="instanceId",example="1269",description="The instance ID of the dungeon. Found on https://wago.tools/db2/JournalInstance")
 * @OA\Property(type="integer",property="challengeModeId",example="501",description="The challenge mode ID of the dungeon. Found on https://wago.tools/db2/MapChallengeMode")
 * @OA\Property(type="boolean",property="raid",example="0",description="True if this dungeon is a raid")
 * @OA\Property(type="boolean",property="speedrunEnabled",example="0",description="Whether speedrun is enabled for this dungeon")
 * @OA\Property(type="boolean",property="speedrunDifficulty10ManEnabled",example="0",description="Whether 10-man difficulty is enabled for speedrunning")
 * @OA\Property(type="boolean",property="speedrunDifficulty25ManEnabled",example="0",description="Whether 25-man difficulty is enabled for speedrunning")
 * @OA\Property(type="array",property="floors",@OA\Items(ref="#/components/schemas/Floor"))
 *
 * @mixin Dungeon
 */
class DungeonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                             => $this->id,
            'expansion'                      => $this->expansion->shortname,
            'name'                           => __($this->name, [], 'en_US'),
            'slug'                           => $this->slug,
            'key'                            => $this->key,
            'zoneId'                         => $this->zone_id,
            'mapId'                          => $this->map_id,
            'instanceId'                     => $this->instance_id,
            'challengeModeId'                => $this->challenge_mode_id,
            'raid'                           => $this->raid,
            'speedrunEnabled'                => $this->speedrun_enabled,
            'speedrunDifficulty10ManEnabled' => $this->speedrun_difficulty_10_man_enabled,
            'speedrunDifficulty25ManEnabled' => $this->speedrun_difficulty_25_man_enabled,
            'floors'                         => $this->floors
                ->filter(static fn(Floor $floor) => !$floor->facade)
                ->map(static fn(Floor $floor) => new FloorResource($floor))
                ->toArray(),
        ];
    }
}
