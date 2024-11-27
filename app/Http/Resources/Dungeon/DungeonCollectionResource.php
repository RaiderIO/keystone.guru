<?php

namespace App\Http\Resources\Dungeon;

use App\Models\Dungeon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(schema="DungeonEnvelope")
 * @OA\Property(type="array",property="data",@OA\Items(ref="#/components/schemas/Dungeon"))
 *
 * @author Wouter
 *
 * @since 31/07/2023
 */
class DungeonCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                static fn(Dungeon $dungeon) => new DungeonResource($dungeon)
            )->toArray(),
        ];
    }
}
