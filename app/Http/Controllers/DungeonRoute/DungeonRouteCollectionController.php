<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionCreateFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionDuplicateFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionIndexFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
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
            ->where('game_version_id', $gameVersion->id)
            ->when($season !== null, static fn(Builder $query) => $query->where('season_id', $season?->id))
            ->when($request->isFreeFormOnly(), static fn(Builder $query) => $query->whereNull('season_id'))
            ->get();

        $dungeonRouteCollections = $dungeonRouteCollectionService->sortForOverview($dungeonRouteCollections);

        return view('collection.index', [
            'dungeonRouteCollections' => $dungeonRouteCollections,
            'coveredDungeonCounts'    => $dungeonRouteCollections->mapWithKeys(
                static fn(DungeonRouteCollection $dungeonRouteCollection): array => [
                    $dungeonRouteCollection->id => $dungeonRouteCollectionService->getCoveredDungeonCount(
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
            'mayCreateCollection' => $user->dungeonRouteCollections()->count() < DungeonRouteCollection::MAX_COLLECTIONS,
        ]);
    }

    /**
     * Shows the form for a brand new collection, always for the game version selected on the site. The season_id
     * query parameter picks the season it opens with, which the form uses to rebuild its route picker when the user
     * picks another season. It may start from one of the user's routes or one of their tags, whose routes are then
     * pre-filled as far as they match that season.
     */
    public function create(
        DungeonRouteCollectionCreateFormRequest $request,
        DungeonRouteCollectionServiceInterface  $dungeonRouteCollectionService,
    ): View {
        /** @var User $user */
        $user        = Auth::user();
        $gameVersion = $request->gameVersion();
        $hasOldInput = $request->session()->hasOldInput();

        // The requested season wins, then what a failed validation submitted, then the season of a route the
        // collection starts from, then the current season

        if (!$gameVersion->has_seasons) {
            $season = null;
        } elseif ($request->hasRequestedSeason()) {
            $season = $request->season();
        } elseif ($hasOldInput) {
            $season = Season::query()
                ->where('expansion_id', $gameVersion->expansion_id)
                ->find((int)$request->old('season_id'));
        } elseif ($request->dungeonRoute() !== null) {
            $season = $request->season();
        } else {
            $season = $dungeonRouteCollectionService->getCurrentSeason($gameVersion);
        }
        $season?->load(['expansion', 'dungeons']);

        // A route of an inactive season starts a set of that season, so the season choice must offer it
        $selectableSeasons = $dungeonRouteCollectionService->getSelectableSeasons($gameVersion);
        if ($season !== null && !$selectableSeasons->contains('id', $season->id)) {
            $selectableSeasons->prepend($season);
        }

        $tagDungeonRoutesLeftOut = 0;
        $dungeonRoute            = $request->dungeonRoute();
        $tagName                 = $request->tagName();

        if ($hasOldInput) {
            // After a failed validation the sections must show what was submitted, not an empty collection -
            // otherwise resubmitting the corrected form saves the collection with no routes
            $selectedDungeonRoutes = $this->getSubmittedDungeonRoutes($user, (array)$request->old('dungeon_routes', []));
        } elseif ($dungeonRoute !== null) {
            $selectedDungeonRoutes = $dungeonRouteCollectionService->filterMatchingDungeonRoutes($gameVersion, $season, collect([$dungeonRoute]));
        } elseif ($tagName !== null) {
            $taggedDungeonRoutes = $this->getTaggedDungeonRoutes($user, $tagName);

            $selectedDungeonRoutes = $dungeonRouteCollectionService
                ->filterMatchingDungeonRoutes($gameVersion, $season, $taggedDungeonRoutes)
                ->take(DungeonRouteCollection::MAX_ROUTES)
                ->values();

            $tagDungeonRoutesLeftOut = $taggedDungeonRoutes->count() - $selectedDungeonRoutes->count();
        } else {
            $selectedDungeonRoutes = collect();
        }

        return view('collection.new', [
            'dungeonRouteCollection'  => null,
            'editSections'            => $dungeonRouteCollectionService->getEditSections($gameVersion, $season, collect(), $selectedDungeonRoutes),
            'hasOwnDungeonRoutes'     => $this->hasOwnDungeonRoutes($user),
            'selectedGameVersion'     => $gameVersion,
            'seasons'                 => $selectableSeasons,
            'selectedSeason'          => $season,
            'teams'                   => $user->teams,
            'categories'              => DungeonRouteCollectionCategory::all(),
            'tagNames'                => $this->getTagNames($user),
            'selectedTagName'         => $tagName,
            'tagDungeonRoutesLeftOut' => $tagDungeonRoutesLeftOut,
            'prefillName'             => $request->validated('name'),
            'prefillDescription'      => $request->validated('description'),
            'mayCreateCollection'     => $user->dungeonRouteCollections()->count() < DungeonRouteCollection::MAX_COLLECTIONS,
            // Picking another season rebuilds the routes section from this same form, which must keep starting
            // from the route or tag it started from - otherwise the pre-filled routes are silently dropped
            'startFromQuery' => array_filter([
                'dungeon_route' => $dungeonRoute?->public_key,
                'tag'           => $tagName,
            ], static fn(?string $value): bool => $value !== null),
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
                'game_version_id'                      => $request->gameVersion()->id,
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

        $duplicateGameVersion = $dungeonRouteCollection->gameVersion;
        $duplicateSeasons     = $dungeonRouteCollectionService->getSelectableSeasons($duplicateGameVersion);
        if ($dungeonRouteCollection->season !== null && !$duplicateSeasons->contains('id', $dungeonRouteCollection->season_id)) {
            $duplicateSeasons->prepend($dungeonRouteCollection->season);
        }

        /** @var array<int|string, int> $duplicateMatchingCounts */
        $duplicateMatchingCounts = $duplicateSeasons
            ->mapWithKeys(static fn(Season $season): array => [
                $season->id => $dungeonRouteCollectionService->filterMatchingDungeonRoutes($duplicateGameVersion, $season, $dungeonRouteCollection->dungeonRoutes)->count(),
            ])
            ->all();
        $duplicateMatchingCounts[''] = $dungeonRouteCollectionService->filterMatchingDungeonRoutes($duplicateGameVersion, null, $dungeonRouteCollection->dungeonRoutes)->count();

        /** @var User $user */
        $user = Auth::user();

        return view('collection.edit', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            // Only the routes in the collection are listed; new ones are picked in the route picker drawer
            'editSections' => $dungeonRouteCollectionService->getEditSections(
                $dungeonRouteCollection->gameVersion,
                $dungeonRouteCollection->season,
                $dungeonRouteCollection->dungeonRoutes,
                $dungeonRouteCollection->dungeonRoutes,
            ),
            // The picker lists the acting user's own routes, so it only offers what may join when that is the owner
            'mayAddDungeonRoutes' => $dungeonRouteCollection->isOwnedByUser(),
            // Scoped to the collection's own owner, not the acting user - an admin editing someone
            // else's collection must not be told the owner has no routes
            'hasOwnDungeonRoutes' => $this->hasOwnDungeonRoutes($dungeonRouteCollection->user)
                || $dungeonRouteCollection->dungeonRoutes->isNotEmpty(),
            'selectedGameVersion' => $dungeonRouteCollection->gameVersion,
            'selectedSeason'      => $dungeonRouteCollection->season,
            'teams'               => $dungeonRouteCollection->user->teams,
            'categories'          => DungeonRouteCollectionCategory::all(),
            // Duplicating copies the owner's own routes, so only the owner may do it
            'mayDuplicate'         => $dungeonRouteCollection->isOwnedByUser($user),
            'mayCreateCollection'  => $user->dungeonRouteCollections()->count() < DungeonRouteCollection::MAX_COLLECTIONS,
            'duplicateGameVersion' => $duplicateGameVersion,
            'duplicateSeasons'     => $duplicateSeasons,
            // How many of the collection's routes a duplicate keeps, per season option; '' is free-form
            'duplicateMatchingCounts' => $duplicateMatchingCounts,
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
        DungeonRouteCollectionServiceInterface         $dungeonRouteCollectionService,
    ): RedirectResponse {
        Gate::authorize('edit', $dungeonRouteCollection);

        $previousPublishedStateId = $dungeonRouteCollection->published_state_id;

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
                'game_version_id'                      => $request->gameVersion()->id,
                'season_id'                            => $request->season()?->id,
                'published_state_id'                   => $request->publishedStateId(),
                'name'                                 => $request->validated('name'),
                'description'                          => $request->validated('description'),
            ]);

            // The edit page saves its routes through the ajax endpoints; its details form does not post them
            if ($request->has('dungeon_routes')) {
                self::syncDungeonRoutes($dungeonRouteCollection, $request->dungeonRoutes(), $dungeonRouteCollectionRouteRepository);
            }
        });

        // Only a raise offers to bring the routes along - lowering the collection's own state never should
        if ($dungeonRouteCollection->published_state_id > $previousPublishedStateId) {
            /** @var User $user */
            $user = Auth::user();

            $raisableDungeonRouteCount = $dungeonRouteCollectionService
                ->getRoutesLessVisibleThanCollection($dungeonRouteCollection)
                ->filter(static fn(DungeonRoute $dungeonRoute): bool => Gate::forUser($user)->allows('publish', [
                    $dungeonRoute,
                    $dungeonRouteCollection->getPublishedStateName(),
                ]))
                ->count();

            if ($raisableDungeonRouteCount > 0) {
                Session::flash('collection_publish_routes_confirm', [
                    'count'           => $raisableDungeonRouteCount,
                    'published_state' => $dungeonRouteCollection->getPublishedStateName(),
                ]);
            }
        }

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_updated'));

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    /**
     * Copies a collection's name, description, category and routes into a new collection that is only visible to its
     * owner, with the chosen season. Routes that do not match the chosen season are left out.
     */
    public function duplicate(
        DungeonRouteCollectionDuplicateFormRequest     $request,
        DungeonRouteCollection                         $dungeonRouteCollection,
        DungeonRouteCollectionServiceInterface         $dungeonRouteCollectionService,
        DungeonRouteCollectionRepositoryInterface      $dungeonRouteCollectionRepository,
        DungeonRouteCollectionRouteRepositoryInterface $dungeonRouteCollectionRouteRepository,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if ($user->dungeonRouteCollections()->count() >= DungeonRouteCollection::MAX_COLLECTIONS) {
            Session::flash('warning', __('controller.dungeonroutecollection.flash.max_collections_reached', [
                'max' => DungeonRouteCollection::MAX_COLLECTIONS,
            ]));

            return redirect()->route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
        }

        $gameVersion = $request->gameVersion();
        $season      = $request->season();

        $dungeonRouteCollection->load(['dungeonRoutes.mappingVersion']);
        $dungeonRoutes = $dungeonRouteCollectionService->filterMatchingDungeonRoutes($gameVersion, $season, $dungeonRouteCollection->dungeonRoutes);

        $duplicate = DB::transaction(function () use (
            $user,
            $dungeonRouteCollection,
            $gameVersion,
            $season,
            $dungeonRoutes,
            $dungeonRouteCollectionRepository,
            $dungeonRouteCollectionRouteRepository,
        ): DungeonRouteCollection {
            $duplicate = $dungeonRouteCollectionRepository->create([
                'user_id'                              => $user->id,
                'team_id'                              => null,
                'dungeon_route_collection_category_id' => $dungeonRouteCollection->dungeon_route_collection_category_id,
                'game_version_id'                      => $gameVersion->id,
                'season_id'                            => $season?->id,
                'public_key'                           => DungeonRouteCollection::generateRandomPublicKey(),
                'published_state_id'                   => PublishedState::ALL[PublishedState::UNPUBLISHED],
                'name'                                 => $dungeonRouteCollection->name,
                'description'                          => $dungeonRouteCollection->description,
            ]);

            self::syncDungeonRoutes($duplicate, $dungeonRoutes, $dungeonRouteCollectionRouteRepository);

            return $duplicate;
        });

        Session::flash('status', __('controller.dungeonroutecollection.flash.collection_duplicated'));

        $leftOutCount = $dungeonRouteCollection->dungeonRoutes->count() - $dungeonRoutes->count();
        if ($leftOutCount > 0) {
            Session::flash('warning', trans_choice('controller.dungeonroutecollection.flash.collection_duplicated_left_out', $leftOutCount, [
                'count' => $leftOutCount,
            ]));
        }

        return redirect()->route('collections.edit', ['dungeonRouteCollection' => $duplicate]);
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
            'coveredDungeonCount'    => $dungeonRouteCollectionService->getCoveredDungeonCount($dungeonRouteCollection, $dungeonRoutes),
        ]);
    }

    /**
     * Whether the user has any route to put in a collection at all. Sandbox routes expire, so they are
     * deliberately not counted.
     */
    private function hasOwnDungeonRoutes(User $user): bool
    {
        return DungeonRoute::query()
            ->where('author_id', $user->id)
            ->whereNull('expires_at')
            ->exists();
    }

    /**
     * The routes a submission that failed validation held, in the order it held them, constrained to the
     * user's own routes.
     *
     * @param array<int, mixed> $publicKeys
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getSubmittedDungeonRoutes(User $user, array $publicKeys): Collection
    {
        $publicKeys = array_values(array_filter(array_map(strval(...), $publicKeys)));
        if ($publicKeys === []) {
            return collect();
        }

        $dungeonRoutes = DungeonRoute::query()
            ->where('author_id', $user->id)
            ->whereNull('expires_at')
            ->whereIn('public_key', $publicKeys)
            ->with(['dungeon', 'mappingVersion'])
            ->get()
            ->keyBy('public_key');

        return collect($publicKeys)
            ->map(static fn(string $publicKey): ?DungeonRoute => $dungeonRoutes->get($publicKey))
            ->filter()
            ->values();
    }

    /**
     * The names of the user's personal route tags that are on at least one route, alphabetically.
     *
     * @return Collection<int, string>
     */
    private function getTagNames(User $user): Collection
    {
        return Tag::query()
            ->where('context_id', $user->id)
            ->where('context_class', User::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('model_class', DungeonRoute::class)
            ->whereNotNull('model_id')
            ->distinct()
            ->orderBy('name')
            ->pluck('name');
    }

    /**
     * The user's own routes carrying the passed personal tag. Sandbox routes expire, so they are
     * deliberately left out.
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getTaggedDungeonRoutes(User $user, string $tagName): Collection
    {
        $taggedDungeonRouteIds = Tag::query()
            ->where('context_id', $user->id)
            ->where('context_class', User::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('model_class', DungeonRoute::class)
            ->where('name', $tagName)
            ->pluck('model_id')
            ->map(intval(...))
            ->all();

        if ($taggedDungeonRouteIds === []) {
            return collect();
        }

        return DungeonRoute::query()
            ->where('author_id', $user->id)
            ->whereNull('expires_at')
            ->whereIn('id', $taggedDungeonRouteIds)
            ->with(['dungeon', 'mappingVersion'])
            ->orderBy('id')
            ->get();
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
