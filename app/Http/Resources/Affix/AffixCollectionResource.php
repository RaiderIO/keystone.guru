<?php

namespace App\Http\Resources\Affix;

use App\Models\Affix;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class AffixCollectionResource
 *
 * @author Wouter
 *
 * @since 31/07/2023
 */
class AffixCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(static fn(Affix $affix) => new AffixResource($affix))->toArray();
    }
}
