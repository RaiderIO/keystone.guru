<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Http\Models\Response\RouteThumbnailJob\RouteThumbnailJobEnvelopeResponseModel;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
     */
    public function toArray(Request $request): array
    {
        return RouteThumbnailJobEnvelopeResponseModel::createFromArray([
            'data' => $this->collection->map(
                static fn(DungeonRouteThumbnailJob $dungeonRoute) => new DungeonRouteThumbnailJobResource($dungeonRoute)
            )->toArray(),
        ])->toArray();
    }
}
