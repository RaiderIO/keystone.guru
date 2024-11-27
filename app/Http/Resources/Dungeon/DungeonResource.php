<?php

namespace App\Http\Resources\Dungeon;

use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="Dungeon")
 * @OA\Property(type="integer",example="69",property="id")
 * @OA\Property(type="string",example="tww",property="expansion",description="The key of the expansion this dungeon belongs to")
 * @OA\Property(type="string",example="The Stonevault",property="name",description="The English dungeon name")
 * @OA\Property(type="string",example="the-stonevault",property="slug",description="The URL friendly slug of the dungeon's name")
 * @OA\Property(type="string",example="thestonevault",property="key",description="The unique identifier for this dungeon")
 * @OA\Property(type="speedrunEnabled",example="0",property="speedrunEnabled",description="Whether speedrun is enabled for this dungeon")
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
            'id'              => $this->id,
            'expansion'       => $this->expansion->shortname,
            'name'            => __($this->name, [], 'en_US'),
            'slug'            => $this->slug,
            'key'             => $this->key,
            'speedrunEnabled' => $this->speedrun_enabled,
        ];
    }
}
