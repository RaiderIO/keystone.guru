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
use App\Http\Requests\APIDungeonRouteFormRequest;
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
use App\Models\Team;
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
        $routes = DungeonRoute::with(['dungeon', 'affixes', 'author', 'routeattributes'])
            // Specific selection of dungeon columns; if we don't do it somehow the Affixes and Attributes of the result is cleared.
            // Probably selecting similar named columns leading Laravel to believe the relation is already satisfied.
            ->selectRaw('dungeon_routes.*, dungeons.enemy_forces_required_teeming, dungeons.enemy_forces_required,
             CAST(IFNULL(
                 IF(dungeon_routes.teeming = 1,
                      SUM(
                          IF(
                              enemies.enemy_forces_override_teeming >= 0,
                              enemies.enemy_forces_override_teeming,
                              IF(npcs.enemy_forces_teeming >= 0, npcs.enemy_forces_teeming, npcs.enemy_forces)
                          )
                      ),
                      SUM(
                          IF(
                              enemies.enemy_forces_override >= 0,
                              enemies.enemy_forces_override,
                              npcs.enemy_forces
                          )
                      )
                ),  0
            ) AS SIGNED ) as enemy_forces')
            // Select enemy forces
            ->leftJoin('kill_zones', 'kill_zones.dungeon_route_id', '=', 'dungeon_routes.id')
            ->leftJoin('kill_zone_enemies', 'kill_zone_enemies.kill_zone_id', '=', 'kill_zones.id')
            ->leftJoin('enemies', 'enemies.id', '=', 'kill_zone_enemies.enemy_id')
            ->leftJoin('npcs', 'npcs.id', '=', 'enemies.npc_id')
            ->leftJoin('dungeons', 'dungeons.id', '=', 'dungeon_routes.dungeon_id')
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

        // If we're with a team and if we want to get all routes that may be assigned to the team
        $available = false;
        // If we're viewing a team's route this will be filled
        $team = null;

        $requirements = $request->get('requirements', []);

        // Enough enemy forces
        if (array_search('enough_enemy_forces', $requirements) !== false) {
            // Clear group by
            $routes = $routes
                // Having because we're using the result of SELECT
                ->havingRaw('IF(dungeon_routes.teeming, enemy_forces > dungeons.enemy_forces_required_teeming, enemy_forces > dungeons.enemy_forces_required)')
                // Add more group by clauses, required for the above having query
                ->groupBy(['dungeon_routes.teeming', 'dungeons.enemy_forces_required', 'dungeons.enemy_forces_required_teeming']);
        }

        // Check if we're filtering based on team or not
        $teamName = $teamName = $request->get('team_name', false);

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
            if ($teamName) {
                // @TODO Policy?
                // You must be a member of this team to retrieve their routes
                $team = Team::where('name', $teamName)->firstOrFail();
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
        if (!$mine && !$teamName) {
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
            $newRoute = $dungeonroute->clone(true);
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
            return abort(400, sprintf(__('Invalid MDT string: %s'), $ex->getMessage()));
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
            }

            throw $error;
        }
    }
}
