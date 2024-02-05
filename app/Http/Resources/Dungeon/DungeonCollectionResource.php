<?php

namespace App\Http\Resources\Dungeon;

use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JsonSerializable;

/**
 * Class DungeonCollectionResource
 *
 * @package App\Http\Resources
 * @author Wouter
 * @since 31/07/2023
 */
class DungeonCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return ['data' => $this->collection->map(function(Dungeon $dungeon){
            return new DungeonResource($dungeon);
        })];
    }
}
