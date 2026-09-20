<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesAddFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesOrderFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesRemoveFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Changes which routes a collection holds, one change at a time, so the edit page saves immediately.
 */
class AjaxDungeonRouteCollectionController extends Controller
{
    /**
     * Appends the posted routes to the end of the collection, in posted order.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeRoutes(
        AjaxDungeonRouteCollectionRoutesAddFormRequest $request,
        DungeonRouteCollection                         $dungeonRouteCollection,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): JsonResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        $dungeonRoutes = $request->dungeonRoutes();

        DB::transaction(static function () use ($dungeonRouteCollection, $dungeonRoutes, $dungeonRouteCollectionRouteRepository): void {
            // Serialises concurrent adds to one collection, so two requests that each fit cannot overshoot the cap together
            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->lockForUpdate()->first();

            $members = DungeonRouteCollectionRoute::query()
                ->where('dungeon_route_collection_id', $dungeonRouteCollection->id);

            if ((clone $members)->count() + $dungeonRoutes->count() > DungeonRouteCollection::MAX_ROUTES) {
                throw ValidationException::withMessages([
                    'dungeon_routes' => __('validation.custom.collection_dungeon_routes.max', ['max' => DungeonRouteCollection::MAX_ROUTES]),
                ]);
            }

            $nextOrder = (int)$members->max('order') + 1;

            foreach ($dungeonRoutes->values() as $index => $dungeonRoute) {
                $dungeonRouteCollectionRouteRepository->create([
                    'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                    'dungeon_route_id'            => $dungeonRoute->id,
                    'order'                       => $nextOrder + $index,
                ]);
            }

            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);
        });

        return response()->json([
            'dungeon_routes' => $dungeonRoutes->map(static fn(DungeonRoute $dungeonRoute): array => [
                'id'         => $dungeonRoute->id,
                'public_key' => $dungeonRoute->public_key,
                'title'      => $dungeonRoute->title,
                'dungeon_id' => $dungeonRoute->dungeon_id,
                'dungeon'    => __($dungeonRoute->dungeon->name),
                // The list the route joins shows its enemy forces against the requirement, so the row
                // it gets reads the same as the ones already rendered by the server
                'enemy_forces'          => $dungeonRoute->enemy_forces,
                'enemy_forces_required' => $dungeonRoute->mappingVersion->enemy_forces_required,
            ])->values(),
        ]);
    }

    /**
     * Removes the posted routes from the collection; the order of the remaining routes is kept.
     *
     * @throws AuthorizationException
     */
    public function deleteRoutes(
        AjaxDungeonRouteCollectionRoutesRemoveFormRequest $request,
        DungeonRouteCollection                            $dungeonRouteCollection,
    ): JsonResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        $dungeonRoutes = $request->dungeonRoutes();

        DB::transaction(static function () use ($dungeonRouteCollection, $dungeonRoutes): void {
            DungeonRouteCollectionRoute::query()
                ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
                ->whereIn('dungeon_route_id', $dungeonRoutes->pluck('id'))
                ->delete();

            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);
        });

        return response()->json([
            'dungeon_routes' => $dungeonRoutes->pluck('public_key')->values(),
        ]);
    }

    /**
     * Stores the posted order, which holds every route of the collection.
     *
     * @throws AuthorizationException
     */
    public function updateRoutesOrder(
        AjaxDungeonRouteCollectionRoutesOrderFormRequest $request,
        DungeonRouteCollection                           $dungeonRouteCollection,
    ): JsonResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        $dungeonRoutes = $request->dungeonRoutes();

        DB::transaction(static function () use ($dungeonRouteCollection, $dungeonRoutes): void {
            foreach ($dungeonRoutes->values() as $order => $dungeonRoute) {
                DungeonRouteCollectionRoute::query()
                    ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
                    ->where('dungeon_route_id', $dungeonRoute->id)
                    ->update(['order' => $order]);
            }

            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);
        });

        return response()->json([
            'dungeon_routes' => $dungeonRoutes->pluck('public_key')->values(),
        ]);
    }
}
