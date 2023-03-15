<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ListsBrushlines;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Http\Controllers\Traits\ListsEnemies;
use App\Http\Controllers\Traits\ListsEnemyPacks;
use App\Http\Controllers\Traits\ListsEnemyPatrols;
use App\Http\Controllers\Traits\ListsMapIcons;
use App\Http\Controllers\Traits\ListsPaths;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Http\Requests\DungeonRoute\APIDungeonRouteFormRequest;
use App\Http\Requests\DungeonRoute\APIDungeonRouteMDTExportFormRequest;
use App\Http\Requests\DungeonRoute\APIDungeonRouteSearchFormRequest;
use App\Http\Requests\DungeonRoute\APISimulateFormRequest;
use App\Http\Requests\PublishFormRequest;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\AuthorNameColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAffixesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAttributesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\EnemyForcesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\RatingColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\ViewsColumnHandler;
use App\Logic\Datatables\DungeonRoutesDatatablesHandler;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\IO\ExportString;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteFavorite;
use App\Models\DungeonRouteRating;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Models\Tags\TagCategory;
use App\Models\Team;
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
use Teapot\StatusCode\Http;
use Throwable;

class APIDungeonRouteController extends Controller
{
    use PublicKeyDungeonRoute;
    use ListsEnemies;
    use ListsEnemyPacks;
    use ListsEnemyPatrols;
    use ListsPaths;
    use ListsBrushlines;
    use ListsMapIcons;
    use ListsDungeonFloorSwitchMarkers;

    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    function list(Request $request)
    {
        // Check if we're filtering based on team or not
        $teamPublicKey = $request->get('team_public_key', false);
        $userId        = (int)$request->get('user_id', 0);
        // Check if we should load the team's tags or the personal tags
        $tagCategoryName = $teamPublicKey ? TagCategory::DUNGEON_ROUTE_TEAM : TagCategory::DUNGEON_ROUTE_PERSONAL;
        $tagCategoryId   = TagCategory::ALL[$tagCategoryName];

        // Which relationship should be load?
        $tagsRelationshipName = $teamPublicKey ? 'tagsteam' : 'tagspersonal';

        $routes = DungeonRoute::with(['dungeon', 'affixes', 'author', 'routeattributes', 'ratings', 'pageviews', 'metricAggregations', $tagsRelationshipName])
            // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
            // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
            ->selectRaw('dungeon_routes.*, dungeons.enemy_forces_required_teeming, dungeons.enemy_forces_required, MAX(mapping_versions.id) as dungeon_latest_mapping_version_id')
            ->join('dungeons', 'dungeons.id', '=', 'dungeon_routes.dungeon_id')
            ->join('mapping_versions', 'mapping_versions.dungeon_id', 'dungeons.id')
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function ($query) {
                /** @var $query \Illuminate\Database\Query\Builder */
                $query->where('expires_at', 0);
                $query->orWhereNull('expires_at');
            })
            // required for the enemy forces calculation
            ->groupBy(['dungeon_routes.id', 'mapping_versions.dungeon_id']);

        $user = Auth::user();
        $mine = false;

        // If we're viewing a team's route this will be filled
        $team = null;

        $requirements = $request->get('requirements', []);

        // Enough enemy forces
        if (array_search('enough_enemy_forces', $requirements) !== false) {
            // Clear group by
            $routes = $routes
                ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces > dungeons.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces > dungeons.enemy_forces_required)');
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

            // Filter by our own user if logged in
            if ($mine) {
                $routes = $routes->where('author_id', $user->id);
            }

            // Handle favorites
            if (array_search('favorite', $requirements) !== false || $request->get('favorites', false)) {
                $routes = $routes->whereHas('favorites', function ($query) use (&$user) {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', $user->id);
                });
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

        return $dtHandler->setBuilder($routes)->addColumnHandler([
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
    }

    /**
     * @param APIDungeonRouteSearchFormRequest $request
     * @param ExpansionServiceInterface $expansionService
     * @return Response|string
     * @throws Exception
     */
    function htmlsearch(APIDungeonRouteSearchFormRequest $request, ExpansionServiceInterface $expansionService)
    {
        // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
        // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
        // May be modified/adjusted later on
        $selectRaw = 'dungeon_routes.*, dungeons.enemy_forces_required_teeming, dungeons.enemy_forces_required';
        $season    = null;
        $expansion = null;

        if ($request->has('expansion')) {
            $expansion = Expansion::where('shortname', $request->get('expansion'))->first();
        } else if ($request->has('season')) {
            $season = Season::find($request->get('season'));
        } else {
            $expansion = $expansionService->getCurrentExpansion();
        }

        $query = DungeonRoute::with(['author', 'affixes', 'ratings', 'routeattributes', 'dungeon'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->when($expansion !== null, function (Builder $builder) use ($expansion) {
                return $builder->where('dungeons.expansion_id', $expansion->id);
            })
            ->when($season !== null, function (Builder $builder) use ($season) {
                return $builder->join('season_dungeons', 'season_dungeons.dungeon_id', '=', 'dungeon_routes.dungeon_id')
                    ->where('season_dungeons.season_id', $season->id);
            })
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function ($query) {
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
            $split = explode(';', $request->get('level'));
            if (count($split) === 2) {
                $query->where(function (Builder $query) use ($split) {
                    $query->where('level_min', '>=', (int)$split[0])
                        ->where('level_min', '<=', (int)$split[1]);
                });

                $query->where(function (Builder $query) use ($split) {
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
            $query->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces > dungeons.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces > dungeons.enemy_forces_required)');
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
        $query->when(config('app.env') !== 'local', function (Builder $builder) {
            $builder->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
                ->where('demo', 0)
                ->where('dungeons.active', 1);
        })->offset((int)$request->get('offset', 0))
            ->limit((int)$request->get('limit', 20))
            ->selectRaw($selectRaw);

        $result = $query->get();

        if ($result->isEmpty()) {
            return response()->noContent();
        } else {
            $userRegion = GameServerRegion::getUserOrDefaultRegion();

            return view('common.dungeonroute.cardlist', [
                'currentAffixGroup' => optional($season)->getCurrentAffixGroupInRegion($userRegion) ?? $expansionService->getCurrentAffixGroup($expansion, $userRegion),
                'dungeonroutes'     => $result,
                'showAffixes'       => true,
                'showDungeonImage'  => true,
            ])->render();
        }
    }

    /**
     * @param Request $request
     * @param string $category
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Response|string
     */
    function htmlsearchcategory(Request $request, string $category, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
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
            $expansion = $expansionService->getCurrentExpansion();
        }

        // Apply an offset and a limit by default for all subsequent queries
        $closure = function (Builder $builder) use ($offset, $limit) {
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
                if ($dungeon instanceof Dungeon) {
                    $result = $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup = $currentAffixGroup);
                } else {
                    $result = $discoverService->popularByAffixGroup($affixGroup = $currentAffixGroup);
                }
                break;
            case 'nextweek':
                if ($dungeon instanceof Dungeon) {
                    $result = $discoverService->popularByDungeonAndAffixGroup($dungeon, $affixGroup = $currentAffixGroup);
                } else {
                    $result = $discoverService->popularByAffixGroup($affixGroup = $expansionService->getNextAffixGroup($expansion, $region));
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
            return view('common.dungeonroute.cardlist', [
                'currentAffixGroup' => $currentAffixGroup,
                'dungeonroutes'     => $result,
                'affixgroup'        => $affixGroup,
                'showAffixes'       => true,
                'showDungeonImage'  => $dungeon === null,
                'cols'              => 2,
            ])->render();
        }
    }

    /**
     * @param APIDungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param ExpansionServiceInterface $expansionService
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute|null $dungeonRoute
     * @return DungeonRoute
     * @throws AuthorizationException
     */
    function store(
        APIDungeonRouteFormRequest $request,
        SeasonService              $seasonService,
        ExpansionServiceInterface  $expansionService,
        ThumbnailServiceInterface  $thumbnailService,
        DungeonRoute               $dungeonRoute = null
    )
    {
        $this->authorize('edit', $dungeonRoute);

        if ($dungeonRoute === null) {
            $dungeonRoute = new DungeonRoute();
        }

        // Update or insert it
        if (!$dungeonRoute->saveFromRequest($request, $seasonService, $expansionService, $thumbnailService)) {
            abort(500, 'Unable to save dungeonroute');
        }

        return $dungeonRoute;
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @param DungeonRoute $dungeonRoute
     *
     * @return Response
     * @throws AuthorizationException
     */
    function storePullGradient(Request $request, SeasonService $seasonService, DungeonRoute $dungeonRoute)
    {
        $this->authorize('edit', $dungeonRoute);

        $dungeonRoute->pull_gradient              = $request->get('pull_gradient', '');
        $dungeonRoute->pull_gradient_apply_always = $request->get('pull_gradient_apply_always', false);

        // Update or insert it
        if (!$dungeonRoute->save()) {
            abort(500, 'Unable to save dungeonroute');
        }

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return Response
     * @throws Exception
     */
    function delete(Request $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('delete', $dungeonRoute);

        if (!$dungeonRoute->delete()) {
            abort(500, 'Unable to delete dungeonroute');
        }

        return response()->noContent();
    }

    /**
     * @param PublishFormRequest $request
     * @param DungeonRoute $dungeonRoute
     *
     * @return Response
     * @throws Exception
     */
    function publishedState(PublishFormRequest $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('publish', $dungeonRoute);

        $publishedState = $request->get('published_state', PublishedState::UNPUBLISHED);

        if (!PublishedState::getAvailablePublishedStates($dungeonRoute, Auth::user())->contains($publishedState)) {
            abort(422, 'This sharing state is not available for this route');
        }

        $dungeonRoute->published_state_id = PublishedState::ALL[$publishedState];
        if ($dungeonRoute->published_state_id === PublishedState::ALL[PublishedState::WORLD]) {
            $dungeonRoute->published_at = date('Y-m-d H:i:s', time());
        }
        $dungeonRoute->save();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute $dungeonRoute
     * @param Team $team
     * @return Response
     * @throws AuthorizationException
     */
    function cloneToTeam(Request $request, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute, Team $team)
    {
        $this->authorize('clone', $dungeonRoute);

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
     * @param ExpansionServiceInterface $expansionService
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @param string $seasonalType
     * @return Application|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function migrateToSeasonalType(
        ExpansionServiceInterface $expansionService,
        Request                   $request,
        DungeonRoute              $dungeonRoute,
        string                    $seasonalType
    ) {
        $this->authorize('migrate', $dungeonRoute);

        $dungeonRoute->migrateToSeasonalType($expansionService, $seasonalType);

        return response('', Http::NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return array
     * @throws Exception
     */
    function rate(Request $request, DungeonRoute $dungeonRoute)
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

        $dungeonRoute->unsetRelation('ratings');
        DungeonRoute::dropCaches($dungeonRoute->id);
        return ['new_avg_rating' => $dungeonRoute->getAvgRatingAttribute()];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return array
     * @throws Exception
     */
    function rateDelete(Request $request, DungeonRoute $dungeonRoute)
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
        return ['new_avg_rating' => $dungeonRoute->getAvgRatingAttribute()];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return Response
     * @throws Exception
     */
    function favorite(Request $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('favorite', $dungeonRoute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrNew(['dungeon_route_id' => $dungeonRoute->id, 'user_id' => $user->id]);
        $dungeonRouteFavorite->save();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @return Response
     * @throws Exception
     */
    function favoriteDelete(Request $request, DungeonRoute $dungeonRoute)
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
     * @param Request $request
     * @param string $publickey
     * @return array
     * @throws Exception
     */
    function data(Request $request, string $publickey)
    {
        // Init the fields we should get for this request
        $fields = $request->get('fields', ['enemy,enemypack,enemypatrol,mapicon,dungeonfloorswitchmarker']);
        $fields = explode(',', $fields);

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
            if (Auth::check() && !Auth::user()->hasRole('admin')) {
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
     * @param Request $request
     * @param MDTExportStringServiceInterface $mdtExportStringService
     * @param DungeonRoute $dungeonRoute
     * @return array|void
     * @throws AuthorizationException
     * @throws Throwable
     */
    function mdtExport(Request                         $request,
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
     * @param APISimulateFormRequest $request
     * @param RaidEventsServiceInterface $raidEventsService
     * @param DungeonRoute $dungeonRoute
     * @return array
     * @throws AuthorizationException
     */
    function simulate(APISimulateFormRequest $request, RaidEventsServiceInterface $raidEventsService, DungeonRoute $dungeonRoute): array
    {
        $this->authorize('view', $dungeonRoute);

        $raidEventsCollection = $raidEventsService->getRaidEvents(
            SimulationCraftRaidEventsOptions::fromRequest($request, $dungeonRoute)
        );

        return [
            'string' => $raidEventsCollection->toString(),
        ];
    }

    /**
     * @param Request $request
     * @param ThumbnailServiceInterface $thumbnailService
     * @param DungeonRoute $dungeonroute
     * @return Response
     */
    function refreshThumbnail(Request $request, ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonroute): Response
    {
        $thumbnailService->queueThumbnailRefresh($dungeonroute);

        return response()->noContent();
    }
}
