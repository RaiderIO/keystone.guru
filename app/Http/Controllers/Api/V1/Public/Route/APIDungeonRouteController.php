<?php

namespace App\Http\Controllers\Api\V1\Public\Route;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DungeonRoute\DungeonRouteListRequest;
use App\Http\Requests\Api\V1\DungeonRoute\DungeonRouteThumbnailRequest;
use App\Http\Resources\DungeonRoute\DungeonRouteCollectionResource;
use App\Http\Resources\DungeonRouteThumbnailJob\DungeonRouteThumbnailJobCollectionResource;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Controller\Api\V1\APIDungeonRouteControllerServiceInterface;
use Auth;
use Illuminate\Database\Eloquent\Builder;

class APIDungeonRouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/route",
     *     summary="Get a list of routes",
     *     tags={"Route"},
     *
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function get(DungeonRouteListRequest $request): DungeonRouteCollectionResource
    {
        $validated = $request->validated();

        return new DungeonRouteCollectionResource(
            DungeonRoute::withOnly(['dungeon', 'author', 'killZones', 'affixes'])
                ->where('author_id', Auth::id())
                ->when($validated['dungeon_id'] ?? false, static function (Builder $builder) use ($validated) {
                    $builder->where('dungeon_id', $validated['dungeon_id']);
                })
                ->paginate()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/route/{route}/thumbnail",
     *     summary="Create a new thumbnail for a route you can view",
     *     tags={"Route"},
     *
     *     @OA\Parameter(
     *         description="Public key of the route you want to generate a thumbnail for",
     *         in="path",
     *         name="route",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *          description="Request object containing all parameters required to generate a thumbnail",
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/RouteThumbnailRequest")
     *      ),
     *
     *     @OA\Response(response=200, description="Successful operation")
     * )
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
                $validated['viewport_width'] ?? null,
                $validated['viewport_height'] ?? null,
                $validated['image_width'] ?? null,
                $validated['image_height'] ?? null,
                $validated['zoom_level'] ?? null,
                $validated['quality'] ?? null
            )
        );
    }
}
