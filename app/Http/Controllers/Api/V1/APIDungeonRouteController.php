<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DungeonRoute\DungeonRouteListRequest;
use App\Http\Resources\DungeonRoute\DungeonRouteCollectionResource;
use App\Models\DungeonRoute;
use Auth;
use Illuminate\Database\Eloquent\Builder;

class APIDungeonRouteController extends Controller
{
    /**
     * @param DungeonRouteListRequest $request
     * @return DungeonRouteCollectionResource
     */
    public function list(DungeonRouteListRequest $request): DungeonRouteCollectionResource
    {
        $validated = $request->validated();

        return new DungeonRouteCollectionResource(
            DungeonRoute::withOnly(['dungeon', 'author', 'killZones', 'affixes'])
                ->where('author_id', Auth::id())
                ->when($validated['dungeon_id'] ?? false, function (Builder $builder) use ($validated) {
                    $builder->where('dungeon_id', $validated['dungeon_id']);
                })
                ->paginate()
        );
    }
}