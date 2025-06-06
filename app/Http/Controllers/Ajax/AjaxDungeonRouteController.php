<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Controllers\Traits\ListsBrushlines;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Http\Controllers\Traits\ListsEnemies;
use App\Http\Controllers\Traits\ListsEnemyPacks;
use App\Http\Controllers\Traits\ListsEnemyPatrols;
use App\Http\Controllers\Traits\ListsMapIcons;
use App\Http\Controllers\Traits\ListsPaths;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteDataFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSearchFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSimulateFormRequest;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteSubmitFormRequest;
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
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\SimulationCraft\RaidEventsServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Random\RandomException;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonRouteController extends Controller
{
    use ListsBrushlines;
    use ListsDungeonFloorSwitchMarkers;
    use ListsEnemies;
    use ListsEnemyPacks;
    use ListsEnemyPatrols;
    use ListsMapIcons;
    use ListsPaths;
    use ChangesDungeonRoute;

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function get(Request $request, ThumbnailServiceInterface $thumbnailService)
    {
        // Check if we're filtering based on team or not
        $teamPublicKey = $request->get('team_public_key', false);
        $userId        = (int)$request->get('user_id', 0);
        // Check if we should load the team's tags or the personal tags
        $tagCategoryName = $teamPublicKey ? TagCategory::DUNGEON_ROUTE_TEAM : TagCategory::DUNGEON_ROUTE_PERSONAL;
        $tagCategoryId   = TagCategory::ALL[$tagCategoryName];

        // Which relationship should be load?
        $tagsRelationshipName = $teamPublicKey ? 'tagsteam' : 'tagspersonal';

        $routes = DungeonRoute::with(['faction', 'specializations', 'classes', 'races', 'dungeon', 'affixes', 'thumbnails',
                                      'author', 'routeattributes', 'ratings', 'metricAggregations', $tagsRelationshipName])
            ->without(['season'])
            // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
            // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
            ->selectRaw('dungeon_routes.*, mapping_versions.enemy_forces_required_teeming, mapping_versions.enemy_forces_required, MAX(mapping_versions.id) as dungeon_latest_mapping_version_id')
            ->join('dungeons', 'dungeons.id', '=', 'dungeon_routes.dungeon_id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function (Builder $query) {
                $query->where('expires_at', 0);
                $query->orWhereNull('expires_at');
            })
            // required for the enemy forces calculation
            ->groupBy(['dungeon_routes.id', 'mapping_versions.dungeon_id']);

        /** @var User $user */
        $user = Auth::user();
        $mine = false;

        // If we're viewing a team's route this will be filled
        $team = null;

        $requirements = $request->get('requirements', []);

        // Enough enemy forces
        if (in_array('enough_enemy_forces', $requirements, true)) {
            // Clear group by
            $routes = $routes
                ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required)');
        }

        $tags = $request->get('tags', []);

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
        if ($user !== null) {
            $mine = $request->get('mine', false);

            // Handle favorites
            if (in_array('favorite', $requirements, true) || $request->get('favorites', false)) {
                $routes = $routes->whereHas('favorites', function ($query) use (&$user) {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', $user->id);
                });
            } else {
                // Filter by our own user if logged in
                if ($mine) {
                    $routes = $routes->where('author_id', $user->id);
                }
            }

            // Handle team if set
            if ($teamPublicKey) {
                // @TODO Policy?
                // You must be a member of this team to retrieve their routes
                $team = Team::where('public_key', $teamPublicKey)->firstOrFail();
                if (!$team->isUserMember($user)) {
                    abort(403, 'Unauthorized');
                }

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

                $routes = $routes->whereIn('published_state_id',
                    [PublishedState::ALL[PublishedState::TEAM], PublishedState::ALL[PublishedState::WORLD]]
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
            new DungeonRouteAffixesColumnHandler($dtHandler),
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
            $thumbnailService->queueThumbnailRefreshIfMissing(collect($result['data']));
        }

        return $result;
    }

    /**
     * @return Response|string
     *
     * @throws Exception
     */
    public function htmlsearch(
        AjaxDungeonRouteSearchFormRequest $request,
        ExpansionServiceInterface         $expansionService,
        ThumbnailServiceInterface         $thumbnailService
    ) {
        // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
        // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
        // May be modified/adjusted later on
        $selectRaw = 'dungeon_routes.*, mapping_versions.enemy_forces_required_teeming, mapping_versions.enemy_forces_required';
        $season    = null;
        $expansion = $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion());

        if ($request->has('expansion')) {
            $expansion = Expansion::where('shortname', $request->get('expansion'))->first();
        } else if ($request->has('season')) {
            $season = Season::find($request->get('season'));
        }

        $query = DungeonRoute::with(['faction', 'specializations', 'classes', 'races', 'author', 'affixes', 'thumbnails',
                                     'ratings', 'routeattributes', 'dungeon', 'dungeon.activeFloors', 'mappingVersion'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', 'dungeons.id')
            ->join('mapping_versions', 'mapping_versions.dungeon_id', 'dungeons.id')
            ->when($expansion !== null, static fn(Builder $builder) => $builder->where('dungeons.expansion_id', $expansion->id)
            )
            ->when($season !== null, static fn(Builder $builder) => $builder->where('dungeon_routes.season_id', $season->id)
            )
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(static function ($query) {
                /** @var $query \Illuminate\Database\Query\Builder */
                $query->where('expires_at', 0);
                $query->orWhereNull('expires_at');
            })
            ->groupBy('dungeon_routes.id');

        // Dungeon selector handling
        if ($request->has('dungeons') && !empty($request->get('dungeons'))) {
            $query->whereIn('dungeon_routes.dungeon_id', $request->get('dungeons'));
        }

        // Title handling
        if ($request->has('title')) {
            $query->where('title', 'LIKE', sprintf('%%%s%%', $request->get('title')));
        }

        // Level handling
        if ($request->has('level')) {
            $split = explode(';', (string)$request->get('level'));
            if (count($split) === 2) {
                $query->where(static function (Builder $query) use ($split) {
                    $query->where('level_min', '>=', (int)$split[0])
                        ->where('level_min', '<=', (int)$split[1]);
                });

                $query->where(static function (Builder $query) use ($split) {
                    $query->where('level_max', '>=', (int)$split[0])
                        ->where('level_max', '<=', (int)$split[1]);
                });
            }
        }

        // Affixes
        $hasAffixGroups = $request->has('affixgroups');
        $hasAffixes     = $request->has('affixes');

        $affixGroups = $request->get('affixgroups');

        // Always prioritize routes of most recent seasons
        $query->join('dungeon_route_affix_groups', 'dungeon_route_affix_groups.dungeon_route_id', '=', 'dungeon_routes.id')
            ->orderBy('dungeon_route_affix_groups.affix_group_id', 'desc');

        if ($hasAffixGroups || $hasAffixes) {
            if (!empty($affixGroups)) {
                $query->whereIn('dungeon_route_affix_groups.affix_group_id', $affixGroups);
            }
        }

        if ($hasAffixes) {
            $selectRaw .= ', COUNT(affix_group_couplings.affix_id) as affixMatches';
            /** @noinspection UnknownColumnInspection */
            $query->join('affix_groups', 'affix_groups.id', '=', 'dungeon_route_affix_groups.affix_group_id')
                ->join('affix_group_couplings', 'affix_group_couplings.affix_group_id', '=', 'affix_groups.id')
                ->whereIn('affix_group_couplings.affix_id', $request->get('affixes'))
                ->groupBy('affix_group_couplings.affix_group_id')
                ->having('affixMatches', '>=', count($request->get('affixes')));
        }

        // Enemy forces
        if ($request->has('enemy_forces') && (int)$request->get('enemy_forces') === 1) {
            $query->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required)');
        }

        // User handling
        if ($request->has('user')) {
            $query->join('users', 'dungeon_routes.author_id', '=', 'users.id');
            $query->where('users.name', $request->get('user'));
        }

        // Rating - prevent 1 rating from filtering out all routes without a rating
        if ($request->has('rating') && (int)$request->get('rating') > 1) {
            $query->join('dungeon_route_ratings', 'dungeon_route_ratings.dungeon_route_id', '=', 'dungeon_routes.id');
            $query->selectRaw('AVG(dungeon_route_ratings.rating) as rating');
            $query->having('rating', '>=', $request->get('rating'));
        }

        // Disable some checks when we're local - otherwise we'd get no routes at all
        $query->when(config('app.env') !== 'local', static function (Builder $builder) {
            $builder->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
                ->where('dungeons.active', 1);
        })->offset((int)$request->get('offset', 0))
            ->limit((int)$request->get('limit', 20))
            ->selectRaw($selectRaw);

        $result = $query->get();

        if ($result->isEmpty()) {
            return response()->noContent();
        } else {
            $userRegion = GameServerRegion::getUserOrDefaultRegion();

            // Ensure that the resulting routes have their thumbnails refreshed if they are missing
            $thumbnailService->queueThumbnailRefreshIfMissing($result);

            return view('common.dungeonroute.cardlist', [
                'currentAffixGroup' =>
                    $season?->getCurrentAffixGroupInRegion($userRegion) ??
                        $expansionService->getCurrentAffixGroup($expansion, $userRegion) ??
                        null,
                'dungeonroutes'     => $result,
                'showAffixes'       => true,
                'showDungeonImage'  => true,
                'orientation'       => 'horizontal',
            ])->render();
        }
    }

    /**
     * @return Response|string
     */
    public function htmlsearchcategory(
        Request $request,
        string $category,
        DiscoverServiceInterface $discoverService,
        ExpansionServiceInterface $expansionService,
        ThumbnailServiceInterface $thumbnailService
    ) {
        $result = collect();

        // Prevent jokesters from playing around
        $offset    = max($request->get('offset', 10), 0);
        $limit     = min($request->get('limit', 10), 20);
        $dungeonId = (int)$request->get('dungeon');

        // Fetch the dungeon if it was set, and only if it is active
        $dungeon = $dungeonId !== 0 ? Dungeon::active()->where('id', $dungeonId)->first() : null;

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
        $discoverService = $discoverService->withExpansion($expansion)->withBuilder($closure);

        $region = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $region);

        $affixGroup = null;
        switch ($category) {
            case 'popular':
                if ($dungeon instanceof Dungeon) {
                    $result = $discoverService->popularByDungeon($dungeon);
                } else {
                    $result = $discoverService->popular();
                }

                break;
            case 'thisweek':
                if ($currentAffixGroup !== null) {
                    if ($dungeon instanceof Dungeon) {
                        $result = $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup = $currentAffixGroup);
                    } else {
                        $result = $discoverService->popularByAffixGroup($affixGroup = $currentAffixGroup);
                    }
                }

                break;
            case 'nextweek':
                if ($currentAffixGroup !== null) {
                    if ($dungeon instanceof Dungeon) {
                        $result = $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup = $currentAffixGroup);
                    } else {
                        $result = $discoverService->popularByAffixGroup($affixGroup = $expansionService->getNextAffixGroup($expansion, $region));
                    }
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
            $thumbnailService->queueThumbnailRefreshIfMissing($result);

            return view('common.dungeonroute.cardlist', [
                'currentAffixGroup' => $currentAffixGroup,
                'dungeonroutes'     => $result,
                'affixgroup'        => $affixGroup,
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
        SeasonService                     $seasonService,
        ExpansionServiceInterface         $expansionService,
        ThumbnailServiceInterface         $thumbnailService,
        ?DungeonRoute                     $dungeonRoute = null
    ): DungeonRoute {
        $this->authorize('edit', $dungeonRoute);

        $beforeDungeonRoute = null;

        if ($dungeonRoute === null) {
            $dungeonRoute = new DungeonRoute();
        } else {
            $beforeDungeonRoute = clone $dungeonRoute;
        }

        // Update or insert it
        if (!$dungeonRoute->saveFromRequest($request, $seasonService, $expansionService, $thumbnailService)) {
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
        $this->authorize('edit', $dungeonRoute);

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
        $this->authorize('delete', $dungeonRoute);

        if (!$dungeonRoute->delete()) {
            abort(500, 'Unable to delete dungeonroute');
        }

        $this->dungeonRouteChanged($dungeonRoute, $dungeonRoute, null);

        return response()->noContent();
    }

    /**
     * @throws Exception
     */
    public function publishedState(PublishFormRequest $request, DungeonRoute $dungeonRoute): Response
    {
        $this->authorize('publish', $dungeonRoute);

        $publishedState = $request->get('published_state', PublishedState::UNPUBLISHED);

        if (!PublishedState::getAvailablePublishedStates($dungeonRoute, Auth::user())->contains($publishedState)) {
            abort(422, 'This sharing state is not available for this route');
        }

        $beforeDungeonRoute = clone $dungeonRoute;

        $dungeonRoute->published_state_id = PublishedState::ALL[$publishedState];
        if ($dungeonRoute->published_state_id === PublishedState::ALL[PublishedState::WORLD]) {
            $dungeonRoute->published_at = date('Y-m-d H:i:s', time());
        }

        $dungeonRoute->save();

        $this->dungeonRouteChanged($dungeonRoute, $beforeDungeonRoute, $dungeonRoute);

        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function cloneToTeam(Request $request, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute, Team $team): Response
    {
        $this->authorize('clone', $dungeonRoute);

        /** @var User $user */
        $user = Auth::user();

        if ($user->canCreateDungeonRoute() && $team->canAddRemoveRoute($user)) {
            $newRoute = $dungeonRoute->cloneRoute($thumbnailService, false);
            $team->addRoute($newRoute);

            return response('', Http::NO_CONTENT);
        } else {
            return response(['result' => 'error']);
        }
    }

    /**
     * @return Application|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function migrateToSeasonalType(
        ExpansionServiceInterface $expansionService,
        Request                   $request,
        DungeonRoute              $dungeonRoute,
        string                    $seasonalType
    ): Response {
        $this->authorize('migrate', $dungeonRoute);

        $dungeonRoute->migrateToSeasonalType($expansionService, $seasonalType);

        return response('', Http::NO_CONTENT);
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function rate(Request $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('rate', $dungeonRoute);

        $value = $request->get('rating', -1);
        if ($value > 0) {
            $user = Auth::user();

            /** @var DungeonRouteRating $dungeonRouteRating */
            $dungeonRouteRating         = DungeonRouteRating::firstOrNew(['dungeon_route_id' => $dungeonRoute->id, 'user_id' => $user->id]);
            $dungeonRouteRating->rating = max(1, min(10, $value));
            $dungeonRouteRating->save();
        }

        DungeonRoute::dropCaches($dungeonRoute->id);

        return ['new_rating' => $dungeonRoute->updateRating()];
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function rateDelete(Request $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('rate', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteRating $dungeonRouteRating */
        $dungeonRouteRating = DungeonRouteRating::firstOrFail()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('user_id', $user->id);
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
        $this->authorize('favorite', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrNew(['dungeon_route_id' => $dungeonRoute->id, 'user_id' => $user->id]);
        $dungeonRouteFavorite->save();

        return response()->noContent();
    }

    /**
     * @throws Exception
     */
    public function favoriteDelete(Request $request, DungeonRoute $dungeonRoute): Response
    {
        $this->authorize('favorite', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrFail()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('user_id', $user->id);
        $dungeonRouteFavorite->delete();

        return response()->noContent();
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function data(Request $request, string $publickey)
    {
        // Init the fields we should get for this request
        $fields = $request->get('fields', ['enemy,enemypack,enemypatrol,mapicon,dungeonfloorswitchmarker']);
        $fields = explode(',', (string)$fields);

        // Show enemies or raw data when fetching enemy packs
        $enemyPackEnemies = (int)$request->get('enemyPackEnemies', true) === 1;
        $teeming          = (int)$request->get('teeming', false) === 1;

        // Start parsing
        $result       = [];
        $dungeonRoute = $publickey === 'admin' ? null : DungeonRoute::findOrFail($publickey);
        if ($dungeonRoute !== null) {
            // Fetch dungeon route specific properties
            // Paths
            if (in_array('path', $fields)) {
                $result['path'] = $this->listPaths((int)$request->get('floor'), $dungeonRoute);
            }

            // Brushline
            if (in_array('brushline', $fields)) {
                $result['brushline'] = $this->listBrushlines((int)$request->get('floor'), $dungeonRoute);
            }
        }

        // Enemy packs
        if (in_array('enemypack', $fields)) {
            // If logged in, and we're NOT an admin
            if (Auth::check() && !Auth::user()->hasRole(Role::ROLE_ADMIN)) {
                // Don't expose vertices
                $enemyPackEnemies = true;
            }

            $result['enemypack'] = $this->listEnemyPacks((int)$request->get('floor'), $enemyPackEnemies, $teeming);
        }

        // Enemy patrols
        if (in_array('enemypatrol', $fields)) {
            $result['enemypatrol'] = $this->listEnemyPatrols((int)$request->get('floor'));
        }

        // Map icons
        if (in_array('mapicon', $fields)) {
            $result['mapicon'] = $this->listMapIcons((int)$request->get('floor'), $dungeonRoute);
        }

        // Dungeon floor switch markers
        if (in_array('dungeonfloorswitchmarker', $fields)) {
            $result['dungeonfloorswitchmarker'] = $this->listDungeonFloorSwitchMarkers((int)$request->get('floor'));
        }

        return $result;
    }

    /**
     * @return array|void
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function mdtExport(Request                         $request,
                              MDTExportStringServiceInterface $mdtExportStringService,
                              DungeonRoute                    $dungeonRoute)
    {
        $this->authorize('view', $dungeonRoute);

        try {
            $warnings     = new Collection();
            $dungeonRoute = $mdtExportStringService
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString($warnings);

            $warningResult = [];
            foreach ($warnings as $warning) {
                /** @var $warning ImportWarning */
                $warningResult[] = $warning->toArray();
            }

            return ['mdt_string' => $dungeonRoute, 'warnings' => $warningResult];
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
     * @throws AuthorizationException
     * @throws RandomException
     */
    public function simulate(AjaxDungeonRouteSimulateFormRequest $request, RaidEventsServiceInterface $raidEventsService, DungeonRoute $dungeonRoute): array
    {
        $this->authorize('view', $dungeonRoute);

        $raidEventsCollection = $raidEventsService->getRaidEvents(
            SimulationCraftRaidEventsOptions::fromRequest($request, $dungeonRoute)
        );

        return [
            'string' => $raidEventsCollection->toString(),
        ];
    }

    public function refreshThumbnail(Request $request, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonroute): Response
    {
        $thumbnailService->queueThumbnailRefresh($dungeonroute, true);

        return response()->noContent();
    }

    public function getDungeonRoutesData(AjaxDungeonRouteDataFormRequest $request, CoordinatesServiceInterface $coordinatesService): Collection
    {
        $publicKeys = $request->validated()['public_keys'];

        return $this->getDungeonRoutesProperties($coordinatesService, $publicKeys);
    }
}
