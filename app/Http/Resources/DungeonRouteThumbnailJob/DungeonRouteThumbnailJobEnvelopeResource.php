<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(schema="RouteThumbnailJobEnvelope")
 * @OA\Property(property="data",type="array",@OA\Items(ref="#/components/schemas/RouteThumbnailJob"))
 *
 * @author Wouter
 *
 * @since 20/01/2024
 */
class DungeonRouteThumbnailJobEnvelopeResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                static fn(DungeonRouteThumbnailJob $dungeonRoute) => new DungeonRouteThumbnailJobResource($dungeonRoute)
            )->toArray(),
        ];
    }
}
