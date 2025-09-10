<?php

namespace App\Http\Resources\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(schema="DungeonRouteEnvelopeResource")
 * @OA\Property(property="data",type="array",@OA\Items(ref="#/components/schemas/DungeonRoute"))
 *
 * @since 30/07/2023
 */
class DungeonRouteEnvelopeResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                static fn(DungeonRoute $dungeonRoute) => new DungeonRouteResource($dungeonRoute),
            )->toArray(),
        ];
    }
}
