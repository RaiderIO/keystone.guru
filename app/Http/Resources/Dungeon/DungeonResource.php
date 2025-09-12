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
 * @OA\Schema(
 *     schema="Dungeon",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=69),
 *     @OA\Property(property="expansion", type="string", example="tww", description="The key of the expansion this dungeon belongs to"),
 *     @OA\Property(property="name", type="string", example="The Stonevault", description="The English dungeon name"),
 *     @OA\Property(property="slug", type="string", example="the-stonevault", description="The URL friendly slug of the dungeon's name"),
 *     @OA\Property(property="key", type="string", example="thestonevault", description="The unique identifier for this dungeon"),
 *     @OA\Property(property="zoneId", type="integer", example=14938, description="The zone ID of the dungeon. Found on the Wowhead page of this dungeon"),
 *     @OA\Property(property="mapId", type="integer", example=2652, description="The map ID of the dungeon. Found on https://wago.tools/db2/Map"),
 *     @OA\Property(property="instanceId", type="integer", example=1269, description="The instance ID of the dungeon. Found on https://wago.tools/db2/JournalInstance"),
 *     @OA\Property(property="challengeModeId", type="integer", example=501, description="The challenge mode ID of the dungeon. Found on https://wago.tools/db2/MapChallengeMode"),
 *     @OA\Property(property="raid", type="boolean", example=0, description="True if this dungeon is a raid"),
 *     @OA\Property(property="heatmapEnabled", type="boolean", example=0, description="Whether a heatmap is available for this dungeon"),
 *     @OA\Property(property="combinedViewEnabled", type="boolean", example=0, description="Whether a combined view (MDT-style) is available for this dungeon"),
 *     @OA\Property(property="speedrunEnabled", type="boolean", example=0, description="Whether speedrun is enabled for this dungeon"),
 *     @OA\Property(property="speedrunDifficulty10ManEnabled", type="boolean", example=0, description="Whether 10-man difficulty is enabled for speedrunning"),
 *     @OA\Property(property="speedrunDifficulty25ManEnabled", type="boolean", example=0, description="Whether 25-man difficulty is enabled for speedrunning"),
 *     @OA\Property(
 *         property="floors",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Floor")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DungeonWrap",
 *     type="object",
 *     required={"data"},
 *     @OA\Property(property="data", ref="#/components/schemas/Dungeon")
 * )
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
            'heatmapEnabled'                 => $this->heatmap_enabled,
            'combinedViewEnabled'            => (int)$this->floors->contains(fn(Floor $floor) => $floor->facade),
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
