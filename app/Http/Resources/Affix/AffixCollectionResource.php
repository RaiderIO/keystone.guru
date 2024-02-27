<?php

namespace App\Http\Resources\Affix;

use App\Models\Affix;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JsonSerializable;

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
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return $this->collection->map(static fn (Affix $affix) => new AffixResource($affix));
    }
}
