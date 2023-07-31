<?php

namespace App\Http\Resources\AffixGroup;

use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JsonSerializable;

/**
 * Class AffixGroupCollectionResource
 *
 * @package App\Http\Resources
 * @author Wouter
 * @since 30/07/2023
 */
class AffixGroupCollectionResource extends ResourceCollection
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
        return $this->collection->map(function(AffixGroup $affixGroup){
            return new AffixGroupResource($affixGroup);
        });
    }
}
