<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesAddFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesOrderFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesPublishFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionRoutesRemoveFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteCollectionsForRouteFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use App\Service\DungeonRoute\DungeonRouteCollectionServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Changes which routes a collection holds, one change at a time, so the edit page saves immediately.
 */
class AjaxDungeonRouteCollectionController extends Controller
{
    use ChangesDungeonRoute;

    /**
     * The current user's collections as seen from one of their own routes: whether the route is in each of them and,
     * when it is not, whether it may be added or why not. Collections the route may join come first. Names are
     * returned as translation keys or model attributes; the client words the reasons itself.
     */
    public function forDungeonRoute(
        AjaxDungeonRouteCollectionsForRouteFormRequest $request,
        DungeonRouteCollectionServiceInterface         $dungeonRouteCollectionService,
    ): JsonResponse {
        /** @var User $user */
        $user         = $request->user();
        $dungeonRoute = $request->dungeonRoute();

        $dungeonRouteCollections = $dungeonRouteCollectionService->sortForOverview(
            $user->dungeonRouteCollections()
                ->with(['gameVersion', 'season.expansion', 'season.dungeons', 'dungeonRoutes.mappingVersion'])
                ->get(),
        );

        $collectionCount = $dungeonRouteCollections->count();

        $rows = $dungeonRouteCollections->map(static function (DungeonRouteCollection $dungeonRouteCollection) use (
            $dungeonRoute,
            $dungeonRouteCollectionService,
        ): array {
            $routeCount           = $dungeonRouteCollection->dungeonRoutes->count();
            $containsDungeonRoute = $dungeonRouteCollection->dungeonRoutes->contains('id', $dungeonRoute->id);
            $blockedReason        = $containsDungeonRoute ? null : $dungeonRouteCollectionService->getAddBlockedReason($dungeonRouteCollection, $dungeonRoute, $routeCount);
            $season               = $dungeonRouteCollection->season;

            return [
                'public_key'   => $dungeonRouteCollection->public_key,
                'name'         => $dungeonRouteCollection->name,
                'game_version' => $dungeonRouteCollection->gameVersion->name,
                'season'       => $dungeonRouteCollection->isSeasonSet() && $season !== null ? [
                    'name'          => $season->name,
                    'name_long'     => $season->name_long,
                    'dungeon_count' => $season->dungeons->count(),
                ] : null,
                'covered_dungeon_count'  => $dungeonRouteCollectionService->getCoveredDungeonCount($dungeonRouteCollection, $dungeonRouteCollection->dungeonRoutes),
                'route_count'            => $routeCount,
                'max_routes'             => DungeonRouteCollection::MAX_ROUTES,
                'contains_dungeon_route' => $containsDungeonRoute,
                'blocked_reason'         => $blockedReason,
                'store_url'              => route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                'delete_url'             => route('ajax.collection.routes.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            ];
        });

        [$available, $blocked] = $rows->partition(static fn(array $row): bool => $row['contains_dungeon_route'] || $row['blocked_reason'] === null);

        return response()->json([
            'collections'      => $available->concat($blocked)->values(),
            'collection_count' => $collectionCount,
            'max_collections'  => DungeonRouteCollection::MAX_COLLECTIONS,
            'may_create'       => $collectionCount < DungeonRouteCollection::MAX_COLLECTIONS,
            'create_url'       => route('collections.new', ['dungeon_route' => $dungeonRoute->public_key]),
        ]);
    }

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

        $addedDungeonRoutes = DB::transaction(static function () use ($dungeonRouteCollection, $dungeonRoutes, $dungeonRouteCollectionRouteRepository): Collection {
            // Serialises concurrent adds to one collection, so two requests that each fit cannot overshoot the cap together
            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->lockForUpdate()->first();

            $dungeonRouteCollectionRoutes = DungeonRouteCollectionRoute::query()
                ->where('dungeon_route_collection_id', $dungeonRouteCollection->id);

            // Validation ran before the lock, so a concurrent request may have added some of these routes since
            $existingDungeonRouteIds = (clone $dungeonRouteCollectionRoutes)->pluck('dungeon_route_id');
            $dungeonRoutes           = $dungeonRoutes->whereNotIn('id', $existingDungeonRouteIds)->values();

            if ($existingDungeonRouteIds->count() + $dungeonRoutes->count() > DungeonRouteCollection::MAX_ROUTES) {
                throw ValidationException::withMessages([
                    'dungeon_routes' => __('validation.custom.collection_dungeon_routes.max', ['max' => DungeonRouteCollection::MAX_ROUTES]),
                ]);
            }

            $nextOrder = (int)$dungeonRouteCollectionRoutes->max('order') + 1;

            foreach ($dungeonRoutes as $index => $dungeonRoute) {
                $dungeonRouteCollectionRouteRepository->create([
                    'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                    'dungeon_route_id'            => $dungeonRoute->id,
                    'order'                       => $nextOrder + $index,
                ]);
            }

            DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);

            return $dungeonRoutes;
        });

        return response()->json([
            'dungeon_routes' => $addedDungeonRoutes->map(static fn(DungeonRoute $dungeonRoute): array => [
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

    /**
     * Raises every route of the collection that is less visible than the collection's own published state, up to
     * that same state - offered as a confirmation after the collection's own published state was raised. The routes
     * to raise are computed here, from the collection itself, never taken from the request: only routes the acting
     * user may actually publish are raised, and the response reports how many were skipped for that reason.
     *
     * @throws AuthorizationException
     */
    public function publishRoutes(
        AjaxDungeonRouteCollectionRoutesPublishFormRequest $request,
        DungeonRouteCollection                             $dungeonRouteCollection,
        DungeonRouteCollectionServiceInterface             $dungeonRouteCollectionService,
    ): JsonResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        /** @var User $user */
        $user                     = $request->user();
        $publishedState           = $dungeonRouteCollection->getPublishedStateName();
        $lessVisibleDungeonRoutes = $dungeonRouteCollectionService->getRoutesLessVisibleThanCollection($dungeonRouteCollection);

        $raisableDungeonRoutes = $dungeonRouteCollectionService->filterRoutesRaisableToCollection(
            $dungeonRouteCollection,
            $lessVisibleDungeonRoutes,
            $user,
        );

        $this->raiseDungeonRoutes($raisableDungeonRoutes, $publishedState);

        return response()->json([
            'raised_count'  => $raisableDungeonRoutes->count(),
            'skipped_count' => $lessVisibleDungeonRoutes->count() - $raisableDungeonRoutes->count(),
        ]);
    }

    /**
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    private function raiseDungeonRoutes(Collection $dungeonRoutes, string $publishedState): void
    {
        DB::transaction(function () use ($dungeonRoutes, $publishedState): void {
            foreach ($dungeonRoutes as $dungeonRoute) {
                $beforeDungeonRoute = clone $dungeonRoute;

                $dungeonRoute->published_state_id = PublishedState::ALL[$publishedState];
                if ($publishedState === PublishedState::WORLD) {
                    $dungeonRoute->published_at = now();
                }
                $dungeonRoute->save();

                $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);
            }
        });
    }
}
