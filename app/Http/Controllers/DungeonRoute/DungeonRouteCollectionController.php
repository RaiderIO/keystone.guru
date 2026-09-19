<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionIndexFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use App\Service\DungeonRoute\DungeonRouteCollectionServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Session;

class DungeonRouteCollectionController extends Controller
{
    /**
     * Lists the collections of the currently logged in user for one game version, optionally narrowed to a season.
     */
    public function index(
        DungeonRouteCollectionIndexFormRequest $request,
        DungeonRouteCollectionServiceInterface $dungeonRouteCollectionService,
    ): View {
        /** @var User $user */
        $user        = Auth::user();
        $gameVersion = $request->gameVersion();
        $season      = $request->season();

        $dungeonRouteCollections = $user->dungeonRouteCollections()
            ->with([
                'team',
                'dungeonRouteCollectionCategory',
                'gameVersion',
                'season.dungeons',
                'dungeonRoutes.mappingVersion',
            ])
            // The overview shows a route count per collection - counted in the query rather than
            // per row, which would be a query per collection
            ->withCount('dungeonRouteCollectionRoutes')
            ->where(static function (Builder $query) use ($gameVersion): void {
                $query->where('game_version_id', $gameVersion->id);

                if ($gameVersion->id === GameVersion::getDefaultGameVersion()->id) {
                    $query->orWhereNull('game_version_id');
                }
            })
            ->when($season !== null, static fn(Builder $query) => $query->where('season_id', $season?->id))
            ->when($request->isFreeFormOnly(), static fn(Builder $query) => $query->whereNull('season_id'))
            ->get();

        $dungeonRouteCollections = $dungeonRouteCollectionService->sortForOverview($dungeonRouteCollections);

        return view('collection.index', [
            'dungeonRouteCollections' => $dungeonRouteCollections,
            'kindLabels'              => $dungeonRouteCollections->mapWithKeys(
                static fn(DungeonRouteCollection $dungeonRouteCollection): array => [
                    $dungeonRouteCollection->id => $dungeonRouteCollectionService->getKindLabel(
                        $dungeonRouteCollection,
                        $dungeonRouteCollection->dungeonRoutes,
                    ),
                ],
            ),
            'gameVersions'         => $request->selectableGameVersions(),
            'selectedGameVersion'  => $gameVersion,
            'seasons'              => $dungeonRouteCollectionService->getSelectableSeasons($gameVersion),
            'selectedSeasonFilter' => $request->isFreeFormOnly()
                ? DungeonRouteCollectionIndexFormRequest::SEASON_NONE
                : $season?->id,
        ]);
    }

    /**
     * Shows the form for a brand new collection.
     */
    public function create(
        Request                                $request,
        DungeonRouteCollectionServiceInterface $dungeonRouteCollectionService,
    ): View {
        /** @var User $user */
        $user         = Auth::user();
        $gameVersions = GameVersion::active()->get();

        // After a failed validation the form shows what was submitted, otherwise the user's current game version and
        // its current season
        $oldGameVersionId = $request->old('game_version_id');
        $gameVersion      = $gameVersions->firstWhere('id', (int)$oldGameVersionId) ?? GameVersion::getUserOrDefaultGameVersion();

        $season = $request->session()->hasOldInput()
            ? Season::query()->find((int)$request->old('season_id'))
            : $dungeonRouteCollectionService->getCurrentSeason($gameVersion);
        $season?->load(['expansion', 'dungeons']);

        $ownDungeonRoutes = $this->getOwnDungeonRoutes($user);

        return view('collection.new', [
            'dungeonRouteCollection' => null,
            'editSections'           => $dungeonRouteCollectionService->getEditSections($gameVersion, $season, $ownDungeonRoutes, collect()),
            'ownDungeonRoutes'       => $ownDungeonRoutes,
            'enemyForcesDetails'     => $dungeonRouteCollectionService->getEnemyForcesDetails($ownDungeonRoutes),
            'hasOwnDungeonRoutes'    => $ownDungeonRoutes->isNotEmpty(),
            'gameVersions'           => $gameVersions,
            'selectedGameVersion'    => $gameVersion,
            'seasonsPerGameVersion'  => $this->getSeasonsPerGameVersion($gameVersions, $dungeonRouteCollectionService),
            'selectedSeason'         => $season,
            'teams'                  => $user->teams,
            'categories'             => DungeonRouteCollectionCategory::all(),
        ]);
    }

    /**
     * Stores a brand new collection.
     */
    public function savenew(
        DungeonRouteCollectionFormRequest              $request,
        DungeonRouteCollectionRepositoryInterface      $dungeonRouteCollectionRepository,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if ($user->dungeonRouteCollections()->count() >= DungeonRouteCollection::MAX_COLLECTIONS) {
            Session::flash('warning', __('controller.dungeonroutecollection.flash.max_collections_reached', [
                'max' => DungeonRouteCollection::MAX_COLLECTIONS,
            ]));

            return redirect()->route('collections.index');
        }

        // The collection and its routes are saved together: a failure partway through would
        // otherwise leave an empty collection behind
        $dungeonRouteCollection = DB::transaction(function () use (
            $user,
            $request,
            $dungeonRouteCollectionRepository,
            $dungeonRouteCollectionRouteRepository,
        ): DungeonRouteCollection {
            $dungeonRouteCollection = $dungeonRouteCollectionRepository->create([
                'user_id'                              => $user->id,
                'team_id'                              => $request->team()?->id,
                'dungeon_route_collection_category_id' => $request->dungeonRouteCollectionCategory()?->id,
                'game_version_id'                      => $request->gameVersion()?->id,
                'season_id'                            => $request->season()?->id,
                'public_key'                           => DungeonRouteCollection::generateRandomPublicKey(),
                'published_state_id'                   => $request->publishedStateId(),
                'name'                                 => $request->validated('name'),
                'description'                          => $request->validated('description'),
            ]);

            self::syncDungeonRoutes($dungeonRouteCollection, $request->dungeonRoutes(), $dungeonRouteCollectionRouteRepository);

            return $dungeonRouteCollection;
        });

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_created'));

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    /**
     * Shows the form for an existing collection.
     *
     * @throws AuthorizationException
     */
    public function edit(
        Request                                $request,
        DungeonRouteCollection                 $dungeonRouteCollection,
        DungeonRouteCollectionServiceInterface $dungeonRouteCollectionService,
    ): View {
        Gate::authorize('edit', $dungeonRouteCollection);

        $dungeonRouteCollection->load([
            'dungeonRoutes.dungeon',
            'dungeonRoutes.mappingVersion',
            'user',
            'gameVersion',
            'season.expansion',
            'season.dungeons',
        ]);

        // Scoped to the collection's own owner, not the acting user - otherwise an admin
        // editing someone else's collection would see an empty picker and no shared teams
        $ownDungeonRoutes = $this->getOwnDungeonRoutes($dungeonRouteCollection->user);

        return view('collection.edit', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'editSections'           => $dungeonRouteCollectionService->getEditSections(
                $dungeonRouteCollection->gameVersion,
                $dungeonRouteCollection->season,
                $ownDungeonRoutes,
                $dungeonRouteCollection->dungeonRoutes,
            ),
            'ownDungeonRoutes'   => $ownDungeonRoutes,
            'enemyForcesDetails' => $dungeonRouteCollectionService->getEnemyForcesDetails(
                $ownDungeonRoutes->concat($dungeonRouteCollection->dungeonRoutes),
            ),
            'hasOwnDungeonRoutes'     => $ownDungeonRoutes->isNotEmpty() || $dungeonRouteCollection->dungeonRoutes->isNotEmpty(),
            'selectedDungeonRouteIds' => $dungeonRouteCollection->dungeonRoutes->pluck('id')->all(),
            'gameVersions'            => GameVersion::query()
                ->where('active', 1)
                ->when($dungeonRouteCollection->game_version_id !== null, static fn(Builder $query) => $query->orWhere('id', $dungeonRouteCollection->game_version_id))
                ->orderBy('id')
                ->get(),
            'selectedGameVersion' => $dungeonRouteCollection->gameVersion,
            'selectedSeason'      => $dungeonRouteCollection->season,
            'teams'               => $dungeonRouteCollection->user->teams,
            'categories'          => DungeonRouteCollectionCategory::all(),
        ]);
    }

    /**
     * Updates an existing collection.
     *
     * @throws AuthorizationException
     */
    public function update(
        DungeonRouteCollectionFormRequest              $request,
        DungeonRouteCollection                         $dungeonRouteCollection,
        DungeonRouteCollectionRepositoryInterface      $dungeonRouteCollectionRepository,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): RedirectResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        // The collection and its routes are saved together: a failure partway through would
        // otherwise leave the collection renamed while its routes still describe the old state
        DB::transaction(function () use (
            $request,
            $dungeonRouteCollection,
            $dungeonRouteCollectionRepository,
            $dungeonRouteCollectionRouteRepository,
        ): void {
            $dungeonRouteCollectionRepository->update($dungeonRouteCollection, [
                'team_id'                              => $request->team()?->id,
                'dungeon_route_collection_category_id' => $request->dungeonRouteCollectionCategory()?->id,
                'game_version_id'                      => $request->gameVersion()?->id,
                'season_id'                            => $request->season()?->id,
                'published_state_id'                   => $request->publishedStateId(),
                'name'                                 => $request->validated('name'),
                'description'                          => $request->validated('description'),
            ]);

            self::syncDungeonRoutes($dungeonRouteCollection, $request->dungeonRoutes(), $dungeonRouteCollectionRouteRepository);
        });

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_updated'));

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    /**
     * Deletes an existing collection.
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRouteCollection $dungeonRouteCollection): RedirectResponse
    {
        Gate::authorize('delete', $dungeonRouteCollection);

        try {
            $dungeonRouteCollection->delete();
        } catch (Exception) {
            abort(500);
        }

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_deleted'));

        return redirect()->route('collections.index');
    }

    /**
     * The public page of a collection, shared by its public key.
     *
     * @throws AuthorizationException
     */
    public function view(
        Request                                $request,
        DungeonRouteCollection                 $dungeonRouteCollection,
        DungeonRouteCollectionServiceInterface $dungeonRouteCollectionService,
    ): View {
        Gate::authorize('view', $dungeonRouteCollection);

        // The routes render through the shared route card, which needs the same relation set
        // DiscoverService eager loads - lazy loading is disabled, so a miss here is a 500
        $dungeonRouteCollection->load([
            'user',
            'dungeonRouteCollectionCategory',
            'gameVersion',
            'season.expansion',
            'season.dungeons',
            'dungeonRoutes.author.iconfile',
            'dungeonRoutes.affixes',
            'dungeonRoutes.ratings',
            'dungeonRoutes.mappingVersion',
            'dungeonRoutes.thumbnails',
            'dungeonRoutes.dungeon',
            'dungeonRoutes.season.expansion',
            // Needed by mayUserView() for team published routes
            'dungeonRoutes.team',
        ]);

        // A collection being public never publishes the routes in it - an unpublished route
        // stays hidden from everyone but its author
        $dungeonRoutes = $dungeonRouteCollection->getViewableDungeonRoutes(Auth::user());

        return view('collection.view', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'dungeonRoutes'          => $dungeonRoutes,
            'dungeonRouteGroups'     => $dungeonRouteCollectionService->getDungeonRouteGroups($dungeonRouteCollection, $dungeonRoutes),
            'kindLabel'              => $dungeonRouteCollectionService->getKindLabel($dungeonRouteCollection, $dungeonRoutes),
        ]);
    }

    /**
     * The routes a user may put in a collection. Sandbox routes expire, so they are deliberately
     * not offered.
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getOwnDungeonRoutes(User $user): Collection
    {
        return DungeonRoute::query()
            ->where('author_id', $user->id)
            ->whereNull('expires_at')
            ->with(['dungeon', 'mappingVersion'])
            ->orderBy('title')
            ->get();
    }

    /**
     * The seasons a season set may be bound to, for every game version with seasons.
     *
     * @param  Collection<int, GameVersion>             $gameVersions
     * @return Collection<int, Collection<int, Season>> Keyed by game version id.
     */
    private function getSeasonsPerGameVersion(
        Collection                             $gameVersions,
        DungeonRouteCollectionServiceInterface $dungeonRouteCollectionService,
    ): Collection {
        return $gameVersions
            ->filter(static fn(GameVersion $gameVersion): bool => (bool)$gameVersion->has_seasons)
            ->mapWithKeys(static fn(GameVersion $gameVersion): array => [
                $gameVersion->id => $dungeonRouteCollectionService->getSelectableSeasons($gameVersion),
            ]);
    }

    /**
     * Replaces the routes of a collection with the passed set, in the passed order. Replacing
     * rather than diffing keeps the ordering trivially correct - a collection is capped at
     * DungeonRouteCollection::MAX_ROUTES rows, so there is nothing to gain from reconciling.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    private static function syncDungeonRoutes(
        DungeonRouteCollection                         $dungeonRouteCollection,
        Collection                                     $dungeonRoutes,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): void {
        $dungeonRouteCollection->dungeonRouteCollectionRoutes()->delete();

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            $dungeonRouteCollectionRouteRepository->create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        $dungeonRouteCollection->unsetRelation('dungeonRoutes');
        $dungeonRouteCollection->unsetRelation('dungeonRouteCollectionRoutes');
    }
}
