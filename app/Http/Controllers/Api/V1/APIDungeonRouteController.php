<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DungeonRoute\DungeonRouteListRequest;
use App\Http\Requests\Api\V1\DungeonRoute\DungeonRouteThumbnailRequest;
use App\Http\Resources\DungeonRoute\DungeonRouteCollectionResource;
use App\Http\Resources\DungeonRoute\DungeonRouteThumbnailJobCollectionResource;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Controller\Api\V1\APIDungeonRouteControllerServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
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

    /**
     * @param DungeonRouteThumbnailRequest              $request
     * @param APIDungeonRouteControllerServiceInterface $apiDungeonRouteControllerService
     * @param DungeonRoute                              $dungeonRoute
     * @return DungeonRouteThumbnailJobCollectionResource
     */
    public function createThumbnails(
        DungeonRouteThumbnailRequest              $request,
        APIDungeonRouteControllerServiceInterface $apiDungeonRouteControllerService,
        DungeonRoute                              $dungeonRoute
    ): DungeonRouteThumbnailJobCollectionResource {
        $validated = $request->validated();

        return new DungeonRouteThumbnailJobCollectionResource(
            $apiDungeonRouteControllerService->createThumbnails(
                $dungeonRoute,
                $validated['width'],
                $validated['height'],
                $validated['quality']
            )
        );
    }
}
