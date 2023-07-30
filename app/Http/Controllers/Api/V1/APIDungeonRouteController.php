<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DungeonRouteCollectionResource;
use App\Http\Resources\DungeonRouteResource;
use App\Models\DungeonRoute;
use Auth;
use Request;

class APIDungeonRouteController
{
    /**
     * @param Request $request
     * @return DungeonRouteCollectionResource
     */
    public function list(Request $request): DungeonRouteCollectionResource
    {
        return new DungeonRouteCollectionResource(
            DungeonRoute::withOnly(['dungeon'])
                ->where('author_id', Auth::id())
                ->paginate()
        );
    }
}