<?php

namespace App\Http\Resources\Floor;

use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="Floor")
 * @OA\Property(type="integer",example="69",property="id")
 * @OA\Property(type="integer",example="259",property="uiMapId")
 * @OA\Property(type="string",example="The Stonevault",property="name",description="The English name of the floor")
 * @OA\Property(type="boolean",example="true",property="default",description="True if this is the floor any route for the dungeon opens up to")
 * @OA\Property(type="float",example="true",property="ingameMinX",description="In-game coordinates of the minimum X value of the floor")
 * @OA\Property(type="float",example="true",property="ingameMinY",description="In-game coordinates of the minimum Y value of the floor")
 * @OA\Property(type="float",example="true",property="ingameMaxX",description="In-game coordinates of the maximum X value of the floor")
 * @OA\Property(type="float",example="true",property="ingameMaxY",description="In-game coordinates of the maximum Y value of the floor")
 *
 * @mixin Floor
 */
class FloorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'uiMapId'    => $this->ui_map_id,
            'name'       => __($this->name, [], 'en_US'),
            'default'    => $this->default,
            'ingameMinX' => $this->ingame_min_x,
            'ingameMinY' => $this->ingame_min_y,
            'ingameMaxX' => $this->ingame_max_x,
            'ingameMaxY' => $this->ingame_max_y,
        ];
    }
}
