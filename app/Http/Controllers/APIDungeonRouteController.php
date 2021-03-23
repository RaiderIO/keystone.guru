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
use App\Http\Requests\DungeonRoute\APIDungeonRouteSearchFormRequest;
use App\Http\Requests\PublishFormRequest;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\AuthorNameColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAffixesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\DungeonRouteAttributesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\EnemyForcesColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\RatingColumnHandler;
use App\Logic\Datatables\ColumnHandler\DungeonRoutes\ViewsColumnHandler;
use App\Logic\Datatables\DungeonRoutesDatatablesHandler;
use App\Logic\MDT\IO\ExportString;
use App\Logic\MDT\IO\ImportWarning;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteFavorite;
use App\Models\DungeonRouteRating;
use App\Models\PublishedState;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        // Check if we should load the team's tags or the personal tags
        $tagCategoryName = $teamPublicKey ? TagCategory::DUNGEON_ROUTE_TEAM : TagCategory::DUNGEON_ROUTE_PERSONAL;
        $tagCategory = TagCategory::fromName($tagCategoryName);

        // Which relationship should be load?
        $tagsRelationshipName = $teamPublicKey ? 'tagsteam' : 'tagspersonal';

        $routes = DungeonRoute::with(['dungeon', 'affixes', 'author', 'routeattributes', $tagsRelationshipName])
            // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
            // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
            ->selectRaw('dungeon_routes.*, dungeons.enemy_forces_required_teeming, dungeons.enemy_forces_required')
            ->join('dungeons', 'dungeons.id', '=', 'dungeon_routes.dungeon_id')
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function ($query)
            {
                /** @var $query \Illuminate\Database\Query\Builder */
                $query->where('expires_at', 0);
                $query->orWhereNull('expires_at');
            })
            // required for the enemy forces calculation
            ->groupBy('dungeon_routes.id');

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
                ->where('tags.tag_category_id', $tagCategory->id)
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
            if (array_search('favorite', $requirements) !== false) {
                $routes = $routes->whereHas('favorites', function ($query) use (&$user)
                {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', $user->id);
                });
            }

            // Handle team if set
            if ($teamPublicKey) {
                // @TODO Policy?
                // You must be a member of this team to retrieve their routes
                $team = Team::where('public_key', $teamPublicKey)->firstOrFail();
                if (!$team->members->contains($user->id)) {
                    abort(403, 'Unauthorized');
                }

                // If available, we need all routes which MAY be assigned to this team, so all routes where
                // team_id = -1 and the author is one of the team members
                $available = intval($request->get('available', 0));
                if ($available === 1) {
                    $routes = $routes->where('team_id', -1);
                    $routes = $routes->whereIn('author_id', $team->members->pluck(['id'])->toArray());
                } else {
                    // Where the route is part of the requested team
                    $routes = $routes->where('team_id', $team->id);
                }

                $routes = $routes->whereIn('published_state_id',
                    PublishedState::whereIn('name', [PublishedState::TEAM, PublishedState::WORLD])->get()->pluck('id')
                );
//                $routes = $routes->whereHas('teams', function ($query) use (&$user, $teamId) {
//                    /** @var $query Builder */
//                    $query->where('team_dungeon_routes.team_id', $teamId);
//                });
            }
        }

        // Only show routes that are visible to the world, unless we're viewing our own routes
        if (!$mine && !$teamPublicKey) {
            $routes = $routes->where('published_state_id', PublishedState::where('name', PublishedState::WORLD)->firstOrFail()->id);
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
            new RatingColumnHandler($dtHandler)
        ])->applyRequestToBuilder()->getResult();
    }

    /**
     * @param APIDungeonRouteSearchFormRequest $request
     * @return string
     */
    function htmlsearch(APIDungeonRouteSearchFormRequest $request)
    {
        // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
        // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
        // May be modified/adjusted later on
        $selectRaw = 'dungeon_routes.*, dungeons.enemy_forces_required_teeming, dungeons.enemy_forces_required';

        $query = DungeonRoute::with(['dungeon', 'affixes', 'author', 'routeattributes'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            // Only non-try routes, combine both where() and whereNull(), there are inconsistencies where one or the
            // other may work, this covers all bases for both dev and live
            ->where(function ($query)
            {
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
//                dd($split);
                $query->where(function (Builder $query) use ($split)
                {
                    $query->where('level_min', '>=', (int)$split[0])
                        ->where('level_min', '<=', (int)$split[1]);
                });

                $query->where(function (Builder $query) use ($split)
                {
                    $query->where('level_max', '>=', (int)$split[0])
                        ->where('level_max', '<=', (int)$split[1]);
                });
            }
        }

        // Affixes
        $hasAffixGroups = $request->has('affixgroups');
        $hasAffixes = $request->has('affixes');
        if ($hasAffixGroups || $hasAffixes) {
            $query->join('dungeon_route_affix_groups', 'dungeon_route_affix_groups.dungeon_route_id', '=', 'dungeon_routes.id');

            if ($hasAffixGroups) {
                $query->whereIn('dungeon_route_affix_groups.affix_group_id', $request->get('affixgroups'));
            }

            if ($hasAffixes) {
                $selectRaw .= ', COUNT(affix_group_couplings.affix_id) as affixMatches';
                $query->join('affix_groups', 'affix_groups.id', '=', 'dungeon_route_affix_groups.affix_group_id')
                    ->join('affix_group_couplings', 'affix_group_couplings.affix_group_id', '=', 'affix_groups.id')
                    ->whereIn('affix_group_couplings.affix_id', $request->get('affixes'))
                    ->groupBy('affix_group_couplings.affix_group_id')
                    ->having('affixMatches', '>=', count($request->get('affixes')));
            }
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
        $query->when(env('APP_ENV') !== 'local', function (Builder $builder)
        {
            $builder->where('published_state_id', PublishedState::where('name', PublishedState::WORLD)->firstOrFail()->id)
                ->where('demo', 0)
                ->where('dungeons.active', 1);
        })->offset((int)$request->get('offset', 0))
            ->limit(10)
            ->selectRaw($selectRaw);


        return view('common.dungeonroute.cardlist', [
            'dungeonroutes'    => $query->get(),
            'showAffixes'      => true,
            'showDungeonImage' => true,
        ])->render();
    }

    /**
     * @param Request $request
     * @param string $category
     * @param DiscoverServiceInterface $discoverService
     * @return string
     */
    function htmlsearchcategory(Request $request, string $category, DiscoverServiceInterface $discoverService)
    {
        $result = collect();

        switch ($category) {
            case 'popular':
                $result = $discoverService->popular();
                break;
        }

        return view('common.dungeonroute.cardlist', [
            'dungeonroutes'    => $result,
            'showAffixes'      => true,
            'showDungeonImage' => true,
            'cols'             => 2,
        ])->render();
    }

    /**
     * @param APIDungeonRouteFormRequest $request
     * @param SeasonService $seasonService
     * @param DungeonRoute|null $dungeonroute
     * @return DungeonRoute
     * @throws Exception
     */
    function store(APIDungeonRouteFormRequest $request, SeasonService $seasonService, DungeonRoute $dungeonroute = null)
    {
        $this->authorize('edit', $dungeonroute);

        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // Update or insert it
        if (!$dungeonroute->saveFromRequest($request, $seasonService)) {
            abort(500, 'Unable to save dungeonroute');
        }

        return $dungeonroute;
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @param DungeonRoute $dungeonroute
     *
     * @return Response
     */
    function storePullGradient(Request $request, SeasonService $seasonService, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        $dungeonroute->pull_gradient = $request->get('pull_gradient', '');
        $dungeonroute->pull_gradient_apply_always = $request->get('pull_gradient_apply_always', false);

        // Update or insert it
        if (!$dungeonroute->save()) {
            abort(500, 'Unable to save dungeonroute');
        }

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws Exception
     */
    function delete(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('delete', $dungeonroute);

        if (!$dungeonroute->delete()) {
            abort(500, 'Unable to delete dungeonroute');
        }

        return ['result' => 'success'];
    }

    /**
     * @param PublishFormRequest $request
     * @param DungeonRoute $dungeonroute
     *
     * @return Response
     * @throws Exception
     */
    function publishedState(PublishFormRequest $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('publish', $dungeonroute);

        $publishedState = $request->get('published_state', PublishedState::UNPUBLISHED);

        if (!PublishedState::getAvailablePublishedStates($dungeonroute, Auth::user())->contains($publishedState)) {
            abort(422, 'This sharing state is not available for this route');
        }

        $dungeonroute->published_state_id = PublishedState::where('name', $publishedState)->first()->id;
        if ($dungeonroute->published_state_id === PublishedState::where('name', PublishedState::WORLD)->firstOrFail()->id) {
            $dungeonroute->published_at = date('Y-m-d H:i:s', time());
        }
        $dungeonroute->save();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Team $team
     * @return Response
     * @throws AuthorizationException
     */
    function cloneToTeam(Request $request, DungeonRoute $dungeonroute, Team $team)
    {
        $this->authorize('clone', $dungeonroute);

        $user = Auth::user();

        if ($user->canCreateDungeonRoute() && $team->canAddRemoveRoute($user)) {
            $newRoute = $dungeonroute->cloneRoute(false);
            $team->addRoute($newRoute);

            return response('', Http::NO_CONTENT);
        } else {
            return response(['result' => 'error']);
        }
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws Exception
     */
    function rate(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('rate', $dungeonroute);

        $value = $request->get('rating', -1);
        if ($value > 0) {
            $user = Auth::user();

            /** @var DungeonRouteRating $dungeonRouteRating */
            $dungeonRouteRating = DungeonRouteRating::firstOrNew(['dungeon_route_id' => $dungeonroute->id, 'user_id' => $user->id]);
            $dungeonRouteRating->rating = max(1, min(10, $value));
            $dungeonRouteRating->save();
        }

        $dungeonroute->unsetRelation('ratings');
        return ['new_avg_rating' => $dungeonroute->getAvgRatingAttribute()];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws Exception
     */
    function rateDelete(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('rate', $dungeonroute);

        $user = Auth::user();

        /** @var DungeonRouteRating $dungeonRouteRating */
        $dungeonRouteRating = DungeonRouteRating::firstOrFail()
            ->where('dungeon_route_id', $dungeonroute->id)
            ->where('user_id', $user->id);
        $dungeonRouteRating->delete();

        $dungeonroute->unsetRelation('ratings');
        return ['new_avg_rating' => $dungeonroute->getAvgRatingAttribute()];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws Exception
     */
    function favorite(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('favorite', $dungeonroute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrNew(['dungeon_route_id' => $dungeonroute->id, 'user_id' => $user->id]);
        $dungeonRouteFavorite->save();

        return ['result' => 'success'];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws Exception
     */
    function favoriteDelete(Request $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('favorite', $dungeonroute);

        $user = Auth::user();

        /** @var DungeonRouteFavorite $dungeonRouteFavorite */
        $dungeonRouteFavorite = DungeonRouteFavorite::firstOrFail()
            ->where('dungeon_route_id', $dungeonroute->id)
            ->where('user_id', $user->id);
        $dungeonRouteFavorite->delete();

        return ['result' => 'success'];
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
        $teeming = (int)$request->get('teeming', false) === 1;

        // Start parsing
        $result = [];
        if ($publickey === 'admin') {
            // Delete it so we don't fetch stuff we shouldn't!
            $publickey = null;
        } else {
            // Fetch dungeon route specific properties
            // Paths
            if (in_array('path', $fields)) {
                $result['path'] = $this->listPaths($request->get('floor'), $publickey);
            }

            // Brushline
            if (in_array('brushline', $fields)) {
                $result['brushline'] = $this->listBrushlines($request->get('floor'), $publickey);
            }
        }

        // Enemy packs
        if (in_array('enemypack', $fields)) {
            // If logged in, and we're NOT an admin
            if (Auth::check() && !Auth::user()->hasRole('admin')) {
                // Don't expose vertices
                $enemyPackEnemies = true;
            }
            $result['enemypack'] = $this->listEnemyPacks($request->get('floor'), $enemyPackEnemies, $teeming);
        }

        // Enemy patrols
        if (in_array('enemypatrol', $fields)) {
            $result['enemypatrol'] = $this->listEnemyPatrols($request->get('floor'));
        }

        // Map icons
        if (in_array('mapicon', $fields)) {
            $result['mapicon'] = $this->listMapIcons($request->get('floor'), $publickey);
        }

        // Dungeon floor switch markers
        if (in_array('dungeonfloorswitchmarker', $fields)) {
            $result['dungeonfloorswitchmarker'] = $this->listDungeonFloorSwitchMarkers($request->get('floor'));
        }


        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param SeasonService $seasonService
     * @return array|void
     * @throws Throwable
     */
    function mdtExport(Request $request, DungeonRoute $dungeonroute, SeasonService $seasonService)
    {
        $this->authorize('view', $dungeonroute);

        $exportString = new ExportString($seasonService);

        try {
            // @TODO improve exception handling
            $warnings = new Collection();
            $dungeonRoute = $exportString->setDungeonRoute($dungeonroute)
                ->getEncodedString($warnings);

            $warningResult = [];
            foreach ($warnings as $warning) {
                /** @var $warning ImportWarning */
                $warningResult[] = $warning->toArray();
            }

            return ['mdt_string' => $dungeonRoute, 'warnings' => $warningResult];
        } catch (Exception $ex) {
            return abort(400, sprintf(__('An error occurred generating your MDT string: %s'), $ex->getMessage()));
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
            }

            throw $error;
        }
    }
}
