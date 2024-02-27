<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JsonSerializable;

/**
 * Class DungeonRouteThumbnailJobCollectionResource
 *
 * @author Wouter
 *
 * @since 20/01/2024
 */
class DungeonRouteThumbnailJobCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array
    {
        return ['data' => $this->collection->map(static fn (DungeonRouteThumbnailJob $dungeonRoute) => new DungeonRouteThumbnailJobResource($dungeonRoute))];
    }
}
