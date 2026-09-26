<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteDeleteBulkFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteListFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSimulateFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSubmitFormRequest;
use App\Http\Requests\DungeonRoute\ScheduledPublishFormRequest;
use App\Http\Requests\PublishFormRequest;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\AuthorNameColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAffixesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAttributesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\EnemyForcesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\RatingColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\TitleColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\ViewsColumnHandler;
use App\Logic\Datatables\DungeonRoutesDatatablesHandler;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\DungeonRoute\DungeonRouteScheduledPublish;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Patreon\PatreonBenefit;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\KillZoneEnemyForces;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\DungeonRouteKillZoneServiceInterface;
use App\Service\DungeonRoute\DungeonRouteSaveServiceInterface;
use App\Service\DungeonRoute\DungeonRouteSeasonContinuationServiceInterface;
use App\Service\DungeonRoute\Exceptions\SeasonContinuationException;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\SimulationCraft\RaidEventsServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Random\RandomException;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonRouteController extends Controller
{
    use ChangesDungeonRoute;

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function get(
        AjaxDungeonRouteListFormRequest                $request,
        ThumbnailServiceInterface                      $thumbnailService,
        SeasonServiceInterface                         $seasonService,
        SeasonAffixGroupServiceInterface               $seasonAffixGroupService,
        DungeonRouteKillZoneServiceInterface           $dungeonRouteKillZoneService,
        DungeonRouteSeasonContinuationServiceInterface $seasonContinuationService,
    ) {
        // Check if we're filtering based on team or not
        $teamPublicKey = $request->get('team_public_key', false);
        $userId        = (int)$request->get('user_id', 0);
        // Check if we should load the team's tags or the personal tags
        $tagCategoryName = $teamPublicKey ? TagCategory::DUNGEON_ROUTE_TEAM : TagCategory::DUNGEON_ROUTE_PERSONAL;
        $tagCategoryId   = TagCategory::ALL[$tagCategoryName];

        // Which relationship should be load?
        $tagsRelationshipName = $teamPublicKey ? 'tagsteam' : 'tagspersonal';

        $withRelations = [
            'faction',
            'specializations',
            'classes',
            'races',
            'dungeon',
            'dungeon.floors',
            'affixes',
            'thumbnails',
            'author',
            'routeattributes',
            'ratings',
            'metricAggregations',
            'upgradeDraft',
            $tagsRelationshipName,
        ];

        if ($teamPublicKey) {
            $withRelations[] = 'scheduledPublish';
        }

        $routes = DungeonRoute::with($withRelations)
            // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
            // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
            // dungeon_latest_mapping_version_id is intentionally NOT computed here. A joined MAX() would fan out rows
            // per dungeon mapping version, corrupting the non-distinct COUNT() aggregates that
            // RatingColumnHandler/DungeonRouteAttributesColumnHandler add via their own joins further down. A correlated
            // subquery in the select list avoids that, but MySQL materializes the GROUP BY result before applying
            // ORDER BY/LIMIT, so it would run once per pre-LIMIT grouped row of the whole filtered set - not once per the
            // handful of rows actually returned - which is expensive on this public, unauthenticated endpoint. Instead,
            // DungeonRoutesDatatablesHandler::getResult() stamps dungeon_latest_mapping_version_id onto the already
            // limited result set, using a single cheap query over mapping_versions (a small table).
            ->selectRaw('dungeon_routes.*, mapping_versions.enemy_forces_required_teeming, mapping_versions.enemy_forces_required, mapping_versions.game_version_id as mapping_version_game_version_id')
            ->join('dungeons', 'dungeons.id', '=', 'dungeon_routes.dungeon_id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function (Builder $query) {
                $query->where('expires_at', 0);
                $query->orWhereNull('expires_at');
            })
            // required for the enemy forces calculation
            ->groupBy([
                'dungeon_routes.id',
                'mapping_versions.dungeon_id',
            ])
            ->when($request->gameVersion(), static fn(Builder $query, GameVersion $gameVersion) => $query->where('mapping_versions.game_version_id', $gameVersion->id))
            ->when($request->season(), static fn(Builder $query, Season $season) => $query->where('dungeon_routes.season_id', $season->id))
            ->when($request->dungeons(), static fn(Builder $query, Collection $dungeons) => $query->whereIn('dungeon_routes.dungeon_id', $dungeons->pluck('id')));

        /** @var User $user */
        $user = Auth::user();
        $mine = false;

        // If we're viewing a team's route this will be filled
        $team = null;

        // A requirements select that isn't rendered for the current view sends the literal string
        // 'undefined' instead of omitting the param, same as the tags select below
        $requirements = $request->get('requirements', []);
        if (!is_array($requirements)) {
            $requirements = [];
        }

        // Enough enemy forces
        if (in_array('enough_enemy_forces', $requirements, true)) {
            // Clear group by
            $routes = $routes
                ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required)');
        }

        // A tags select that isn't rendered for the current view (e.g. the team edit page's Route
        // Publishing tab) sends the literal string 'undefined' instead of omitting the param
        $tags = $request->get('tags', []);
        if (!is_array($tags)) {
            $tags = [];
        }

        // Must have these tags
        if (!empty($tags)) {
            $routes = $routes
                ->join('tags', 'dungeon_routes.id', '=', 'tags.model_id')
                ->where('tags.tag_category_id', $tagCategoryId)
                ->whereIn('tags.name', $tags)
                // https://stackoverflow.com/a/3267635/771270; this enables AND behaviour for multiple tags
                ->havingRaw(sprintf('COUNT(DISTINCT tags.name) >= %d', count($tags)));
        }

        // If logged in
        if ($user !== null) { // @phpstan-ignore notIdentical.alwaysTrue
            $mine = $request->get('mine', false);

            // Handle favorites
            if (in_array('favorite', $requirements, true) || $request->get('favorites', false)) {
                $routes = $routes->whereHas('favorites', function ($query) use (&$user) {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', $user->id);
                });
            }

            // Filter by our own user if logged in. $mine is what exempts the query from the published state
            // filter further down, so it must narrow the results down to the user's own routes on its own
            if ($mine) {
                $routes = $routes->where('author_id', $user->id);
            }

            // Handle team if set
            if ($teamPublicKey) {
                // You must be a member of this team to retrieve their routes - TeamPolicy::edit is
                // exactly that check
                $team = Team::where('public_key', $teamPublicKey)->firstOrFail();
                Gate::authorize('edit', $team);

                // If available, we need all routes which MAY be assigned to this team, so all routes where
                // team_id = null and the author is one of the team members
                $available = intval($request->get('available', 0));
                if ($available === 1) {
                    $routes = $routes->whereNull('team_id');
                    $routes = $routes->whereIn('author_id', $team->members->pluck(['id'])->toArray());
                } else {
                    // Where the route is part of the requested team
                    $routes = $routes->where('team_id', $team->id);
                }

                $routes = $routes->whereIn(
                    'published_state_id',
                    [
                        PublishedState::ALL[PublishedState::TEAM],
                        PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
                        PublishedState::ALL[PublishedState::WORLD],
                    ],
                );
                //                $routes = $routes->whereHas('teams', function ($query) use (&$user, $teamId) {
                //                    /** @var $query Builder */
                //                    $query->where('team_dungeon_routes.team_id', $teamId);
                //                });
            }
        }

        // Add a filter for a specific user if the request called for it
        if ($userId > 0) {
            $routes = $routes->where('author_id', $userId);
        }

        // Only show routes that are visible to the world, unless we're viewing our own routes
        if ((!$mine && !$teamPublicKey) || $userId !== 0) {
            $routes = $routes->where('published_state_id', PublishedState::ALL[PublishedState::WORLD]);
        }

        // Visible here to allow proper usage of indexes
        if (!$mine) {
            $routes = $routes->visible();
        }

        $dtHandler = new DungeonRoutesDatatablesHandler($request);

        $result = $dtHandler->setBuilder($routes)->addColumnHandler([
            // Route titles
            new TitleColumnHandler($dtHandler),
            // Handles any searching/filtering based on dungeon
            new DungeonColumnHandler($dtHandler),
            // Handles any searching/filtering based on DR Affixes
            new DungeonRouteAffixesColumnHandler($dtHandler, $seasonService, $seasonAffixGroupService),
            // Sort by the amount of attributes
            new DungeonRouteAttributesColumnHandler($dtHandler),
            // Allow sorting by author name
            new AuthorNameColumnHandler($dtHandler),
            // Allow sorting by enemy forces
            new EnemyForcesColumnHandler($dtHandler),
            // Allow sorting by views
            new ViewsColumnHandler($dtHandler),
            // Allow sorting by rating
            new RatingColumnHandler($dtHandler),
        ])->applyRequestToBuilder()->getResult();

        // Ensure that the resulting routes have their thumbnails refreshed if they are missing
        if (isset($result['data'])) {
            /** @var array<int, mixed> $data */
            $data = $result['data'];
            $thumbnailService->dungeonRoutesDisplayed(collect($data));

            if ($request->wantsPullForces()) {
                self::addPullForces(collect($data), $dungeonRouteKillZoneService);
            }

            // Only the author's and the team's own tables render the actions that use this
            if ($mine || $teamPublicKey) {
                self::addContinuationSeasons(collect($data), $seasonContinuationService);
            }
        }

        return $result;
    }

    /**
     * Stamps the newer season each route of one page can be continued in (or null) onto it.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    private static function addContinuationSeasons(
        Collection                                     $dungeonRoutes,
        DungeonRouteSeasonContinuationServiceInterface $seasonContinuationService,
    ): void {
        $continuationSeasons = $seasonContinuationService->getContinuationSeasons($dungeonRoutes);

        foreach ($dungeonRoutes as $dungeonRoute) {
            $continuationSeason = $continuationSeasons->get($dungeonRoute->id);

            $dungeonRoute->setAttribute(
                'continuation_season',
                $continuationSeason === null ? null : [
                    'id'   => $continuationSeason->id,
                    'name' => $continuationSeason->name_long,
                ],
            );
        }
    }

    /**
     * Stamps the enemy forces of every pull onto the routes of one page, so a caller can draw the route's
     * pull graph. Batched over the page (see DungeonRouteEnemyForcesPageResolver) rather than queried per
     * route, and stamped after the limit so only the rows actually returned are paid for.
     *
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    private static function addPullForces(
        Collection                           $dungeonRoutes,
        DungeonRouteKillZoneServiceInterface $dungeonRouteKillZoneService,
    ): void {
        $forcesByRouteId = $dungeonRouteKillZoneService->getEnemyForcesPerKillZoneForRoutes($dungeonRoutes);

        foreach ($dungeonRoutes as $dungeonRoute) {
            $dungeonRoute->setAttribute(
                'pull_forces',
                $forcesByRouteId->get($dungeonRoute->id, collect())
                    ->map(static fn(KillZoneEnemyForces $pull): array => [
                        'enemy_forces' => $pull->enemyForces,
                        'has_boss'     => $pull->hasBoss,
                    ])
                    ->values()
                    ->all(),
            );
        }
    }

    /**
     * @return Response|string
     */
    public function htmlsearchcategory(
        Request                   $request,
        string                    $category,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        ThumbnailServiceInterface $thumbnailService,
    ) {
        $result = collect();

        // Prevent jokesters from playing around
        $offset        = max($request->get('offset', 10), 0);
        $limit         = min($request->get('limit', 10), 20);
        $dungeonId     = (int)$request->get('dungeon');
        $gameVersionId = (int)$request->get('gameVersion');

        // Fetch the dungeon if it was set, and only if it is active
        $dungeon = $dungeonId !== 0 ? Dungeon::active()->where('id', $dungeonId)->first() : null;

        $gameVersion = $gameVersionId !== 0 ? GameVersion::where('id', $gameVersionId)->first() : null;

        if ($request->has('expansion')) {
            $expansion = Expansion::where('shortname', $request->get('expansion'))->first();
        } else {
            $expansion = $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion());
        }

        // Apply an offset and a limit by default for all subsequent queries
        $closure = static function (Builder $builder) use ($offset, $limit) {
            $builder->offset($offset)->limit($limit);
        };

        // Prime the discover service
        $discoverService = $discoverService
            ->excludeTeam(Team::getRaiderIOTeam())
            ->withBuilder($closure);

        if ($gameVersion !== null) {
            $discoverService = $discoverService->withGameVersion($gameVersion);
        } elseif ($expansion !== null) {
            $discoverService = $discoverService->withExpansion($expansion);
        }

        $region = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $expansion !== null ? $expansionService->getCurrentAffixGroup($expansion, $region) : null;

        switch ($category) {
            case 'popular':
                if ($dungeon instanceof Dungeon) {
                    $result = $discoverService->popularByDungeon($dungeon);
                } else {
                    $result = $discoverService->popular();
                }

                break;
            case 'new':
                if ($dungeon instanceof Dungeon) {
                    $result = $discoverService->newByDungeon($dungeon);
                } else {
                    $result = $discoverService->new();
                }

                break;
        }

        if ($result->isEmpty()) {
            return response()->noContent();
        } else {
            $thumbnailService->dungeonRoutesDisplayed($result);

            return view('common.dungeonroute.cardlist', [
                'currentAffixGroup' => $currentAffixGroup,
                'dungeonroutes'     => $result,
                'affixgroup'        => null,
                'showAffixes'       => true,
                'showDungeonImage'  => $dungeon === null,
                'cols'              => 4,
            ])->render();
        }
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function store(
        AjaxDungeonRouteSubmitFormRequest $request,
        DungeonRouteSaveServiceInterface  $saveService,
        ?DungeonRoute                     $dungeonRoute = null,
    ): DungeonRoute {
        Gate::authorize('edit', $dungeonRoute);

        $beforeDungeonRoute = null;

        if ($dungeonRoute === null) {
            $dungeonRoute = new DungeonRoute();
        } else {
            $beforeDungeonRoute = clone $dungeonRoute;
        }

        // Update or insert it
        if (!$saveService->save($dungeonRoute, $request->validated())) {
            abort(500, 'Unable to save dungeonroute');
        }

        $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);

        return $dungeonRoute->makeHidden([
            'dungeon',
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function storePullGradient(Request $request, DungeonRoute $dungeonRoute): Response
    {
        Gate::authorize('edit', $dungeonRoute);

        $beforeDungeonRoute = clone $dungeonRoute;

        $dungeonRoute->pull_gradient              = $request->get('pull_gradient', '');
        $dungeonRoute->pull_gradient_apply_always = $request->get('pull_gradient_apply_always', false);

        // Update or insert it
        if (!$dungeonRoute->save()) {
            abort(500, 'Unable to save dungeonroute');
        }

        $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);

        return response()->noContent();
    }

    /**
     * @throws Exception
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute): Response
    {
        Gate::authorize('delete', $dungeonRoute);

        if (!$dungeonRoute->delete()) {
            abort(500, 'Unable to delete dungeonroute');
        }

        $this->dungeonRouteChanged($dungeonRoute, $dungeonRoute, null);

        return response()->noContent();
    }

    /**
     * Deletes several routes at once, the way the route picker drawer's delete mode sends them.
     *
     * @throws Exception
     */
    public function deleteBulk(AjaxDungeonRouteDeleteBulkFormRequest $request): JsonResponse
    {
        return $this->deleteDungeonRoutes($request->dungeonRoutes());
    }

    /**
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @throws Exception
     */
    private function deleteDungeonRoutes(Collection $dungeonRoutes): JsonResponse
    {
        // Every route is authorized before any of them is deleted, so a request holding one route the caller
        // may not delete deletes nothing at all
        foreach ($dungeonRoutes as $dungeonRoute) {
            Gate::authorize('delete', $dungeonRoute);
        }

        $deletedPublicKeys = [];

        foreach ($dungeonRoutes as $dungeonRoute) {
            try {
                // Per route rather than around the batch: deleting one route also writes to the combatlog
                // connection and removes its thumbnails from disk, neither of which a rollback undoes
                DB::transaction(function () use ($dungeonRoute): void {
                    if (!$dungeonRoute->delete()) {
                        throw new Exception('Unable to delete dungeonroute');
                    }

                    $this->dungeonRouteChanged($dungeonRoute, $dungeonRoute, null);
                });
            } catch (Throwable $throwable) {
                // The routes deleted so far are gone for good, so the caller is told which ones those were
                // instead of an error carrying nothing: a retry of the whole selection fails validation on
                // the deleted keys, which would leave the rest of the batch undeletable
                report($throwable);

                break;
            }

            $deletedPublicKeys[] = $dungeonRoute->public_key;
        }

        return response()->json([
            'dungeon_routes' => $deletedPublicKeys,
        ]);
    }

    /**
     * @throws Exception
     */
    public function publishedState(PublishFormRequest $request, DungeonRoute $dungeonRoute): Response
    {
        $publishedState = $request->get('published_state', PublishedState::UNPUBLISHED);

        Gate::authorize('publish', [$dungeonRoute, $publishedState]);

        if (!PublishedState::getAvailablePublishedStates($dungeonRoute, Auth::user())->contains($publishedState)) {
            abort(422, 'This sharing state is not available for this route');
        }

        $beforeDungeonRoute = clone $dungeonRoute;

        $dungeonRoute->published_state_id = PublishedState::ALL[$publishedState];
        if ($dungeonRoute->published_state_id === PublishedState::ALL[PublishedState::WORLD]) {
            $dungeonRoute->published_at = now();
        }

        $dungeonRoute->save();

        $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function storeScheduledPublish(ScheduledPublishFormRequest $request, DungeonRoute $dungeonRoute): Response
    {
        Gate::authorize('schedule-publish', $dungeonRoute);

        $publishedState = $request->validated('published_state');

        /** @var User $user */
        $user = Auth::user();
        if ($publishedState === PublishedState::WORLD_WITH_LINK) {
            if (!$user->hasPatreonBenefit(PatreonBenefit::UNLISTED_ROUTES)) {
                abort(422, 'The world_with_link publish state requires a Patreon subscription.');
            }
        }

        DungeonRouteScheduledPublish::updateOrCreate(
            ['dungeon_route_id' => $dungeonRoute->id],
            [
                'published_state' => $publishedState,
                'publish_at'      => Carbon::parse(
                    $request->validated('publish_at'),
                    $user->timezone ?? config('app.timezone'),
                )->setTimezone(config('app.timezone')),
            ],
        );

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function destroyScheduledPublish(Request $request, DungeonRoute $dungeonRoute): Response
    {
        Gate::authorize('schedule-publish', $dungeonRoute);

        $dungeonRoute->scheduledPublish?->delete();

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function cloneToTeam(
        Request                          $request,
        DungeonRouteSaveServiceInterface $saveService,
        DungeonRoute                     $dungeonRoute,
        Team                             $team,
    ): Response {
        Gate::authorize('clone', $dungeonRoute);

        /** @var User $user */
        $user = Auth::user();

        if ($user->canCreateDungeonRoute() && $team->canAddRemoveRoute($user)) {
            DB::transaction(function () use ($dungeonRoute, $saveService, $team): void {
                $newRoute = $saveService->cloneRoute($dungeonRoute, false);

                if (!$team->addRoute($newRoute)) {
                    throw new Exception('Unable to assign the cloned route to the team!');
                }
            });

            return response('', Http::NO_CONTENT);
        } else {
            return response(['result' => 'error']);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function continueInNewerSeason(
        DungeonRouteSeasonContinuationServiceInterface $seasonContinuationService,
        DungeonRoute                                   $dungeonRoute,
    ): Response {
        Gate::authorize('clone', $dungeonRoute);

        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->canCreateDungeonRoute(), Http::FORBIDDEN, __('controller.dungeonroute.continue_in_newer_season.limit_reached'));

        try {
            $continuation = $seasonContinuationService->continueInNewerSeason($dungeonRoute);
        } catch (SeasonContinuationException) {
            abort(422, __('controller.dungeonroute.continue_in_newer_season.no_newer_season'));
        }

        return response(['public_key' => $continuation->public_key], Http::CREATED);
    }

    /**
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function migrateToSeasonalType(
        ExpansionServiceInterface $expansionService,
        Request                   $request,
        DungeonRoute              $dungeonRoute,
        string                    $seasonalType,
    ): Response {
        Gate::authorize('migrate', $dungeonRoute);

        $dungeonRoute->migrateToSeasonalType($expansionService, $seasonalType);

        return response('', Http::NO_CONTENT);
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    /** @return array<string, float|int> */
    public function rate(Request $request, DungeonRoute $dungeonRoute): array
    {
        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('rate', $dungeonRoute);

        $value = $request->get('rating', -1);
        if ($value > 0) {
            $user = Auth::user();

            /** @var DungeonRouteRating $dungeonRouteRating */
            $dungeonRouteRating = DungeonRouteRating::firstOrNew([
                'dungeon_route_id' => $dungeonRoute->id,
                'user_id'          => $user->id,
            ]);
            $dungeonRouteRating->rating = max(1, min(10, $value));
            $dungeonRouteRating->save();
        }

        DungeonRoute::dropCaches($dungeonRoute->id);

        return ['new_rating' => $dungeonRoute->updateRating()];
    }

    /**
     * @return array<string, float|int>
     *
     * @throws Exception
     */
    public function rateDelete(Request $request, DungeonRoute $dungeonRoute): array
    {
        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('rate', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteRating $dungeonRouteRating */
        $dungeonRouteRating = DungeonRouteRating::query()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        $dungeonRouteRating->delete();

        $dungeonRoute->unsetRelation('ratings');
        DungeonRoute::dropCaches($dungeonRoute->id);

        return ['new_rating' => $dungeonRoute->updateRating()];
    }

    /**
     * @throws Exception
     */
    public function favorite(Request $request, DungeonRoute $dungeonRoute): Response
    {
        Gate::authorize('view', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrNew([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $user->id,
        ]);
        $dungeonRouteFavorite->save();

        return response()->noContent();
    }

    /**
     * @throws Exception
     */
    public function favoriteDelete(Request $request, DungeonRoute $dungeonRoute): Response
    {
        // Deliberately not gated on 'view' like favorite() is: a route may become unpublished after it was
        // favorited, and the user must still be able to remove it from their favorites afterwards
        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::query()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        $dungeonRouteFavorite->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, string|array<int, array<string, mixed>>>
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function mdtExport(
        Request                         $request,
        MDTExportStringServiceInterface $mdtExportStringService,
        DungeonRoute                    $dungeonRoute,
    ): array {
        Gate::authorize('view', $dungeonRoute);

        $useCache = (int)$request->get('useCache', 1) === 1;

        try {
            /** @var Collection<int, ImportWarning> $warnings */
            $warnings     = new Collection();
            $dungeonRoute = $mdtExportStringService
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString($warnings, $useCache);

            $warningResult = [];
            foreach ($warnings as $warning) {
                $warningResult[] = $warning->toArray();
            }

            return [
                'mdt_string' => $dungeonRoute,
                'warnings'   => $warningResult,
            ];
        } catch (InvalidMDTDungeonException $ex) {
            // Expected, user-input-driven case (#3908): every MDT export entrypoint gates on
            // Dungeon::mdt_supported (== Conversion::hasMDTDungeonName()), so reaching here means
            // the button was never rendered for this dungeon in the first place - not an
            // application defect. Skip the Log::error() the generic catch below does, since the
            // sentry log channel alerts on error-level logs (config/logging.php) and this isn't
            // worth paging on. Matches how MDTImportController skips logging its own known domain
            // exceptions.
            //
            // Deliberately NOT InvalidMDTExpansionException here: unlike the dungeon check, nothing
            // gates the UI on expansion support, so that exception firing means a user-visible
            // broken button with no signal anywhere if it's ever silenced - let it fall through to
            // the generic catch below instead.
            return abort(400, sprintf(__('controller.apidungeonroute.mdt_generate_error'), $ex->getMessage()));
        } catch (Exception $ex) {
            Log::error(sprintf('MDT export error: %s', $ex->getMessage()), ['dungeonroute' => $dungeonRoute]);

            return abort(400, sprintf(__('controller.apidungeonroute.mdt_generate_error'), $ex->getMessage()));
        } catch (Throwable $error) {
            Log::critical($error->getMessage(), [
                'dungeonroute' => $dungeonRoute->public_key,
            ]);

            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.apidungeonroute.mdt_generate_no_lua'));
            }

            throw $error;
        }
    }

    /**
     * @return array<string, string>
     *
     * @throws AuthorizationException
     * @throws RandomException
     */
    public function simulate(
        AjaxDungeonRouteSimulateFormRequest $request,
        RaidEventsServiceInterface          $raidEventsService,
        DungeonRoute                        $dungeonRoute,
    ): array {
        Gate::authorize('view', $dungeonRoute);

        $raidEventsCollection = $raidEventsService->getRaidEvents(
            SimulationCraftRaidEventsOptions::fromRequest($request, $dungeonRoute),
        );

        return [
            'string' => $raidEventsCollection->toString(),
        ];
    }

    public function refreshThumbnail(
        Request                   $request,
        ThumbnailServiceInterface $thumbnailService,
        DungeonRoute              $dungeonroute,
    ): Response {
        $thumbnailService->queueThumbnailRefresh($dungeonroute, true);

        return response()->noContent();
    }
}
