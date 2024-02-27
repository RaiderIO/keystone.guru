<?php

namespace App\Http\Resources\Dungeon;

use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
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
            'id' => $this->id,
            'expansion_id' => $this->expansion_id,
            'name' => __($this->name, [], 'en-US'),
            'slug' => $this->slug,
            'key' => $this->key,
            'speedrun_enabled' => $this->speedrun_enabled,
        ];
    }
}
