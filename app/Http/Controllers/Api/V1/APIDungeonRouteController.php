<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DungeonRoute\DungeonRouteCollectionResource;
use App\Models\DungeonRoute;
use Auth;
use Request;

class APIDungeonRouteController extends Controller
{
    /**
     * @param Request $request
     * @return DungeonRouteCollectionResource
     */
    public function list(Request $request): DungeonRouteCollectionResource
    {
        return new DungeonRouteCollectionResource(
            DungeonRoute::withOnly(['dungeon', 'author', 'killZones', 'affixes'])
                ->where('author_id', Auth::id())
                ->paginate()
        );
    }
}