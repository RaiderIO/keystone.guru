<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ListsBrushlines;
use App\Http\Controllers\Traits\ListsDungeonFloorSwitchMarkers;
use App\Http\Controllers\Traits\ListsDungeonStartMarkers;
use App\Http\Controllers\Traits\ListsEnemies;
use App\Http\Controllers\Traits\ListsEnemyPacks;
use App\Http\Controllers\Traits\ListsEnemyPatrols;
use App\Http\Controllers\Traits\ListsKillzones;
use App\Http\Controllers\Traits\ListsMapComments;
use App\Http\Controllers\Traits\ListsPaths;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Http\Requests\APIDungeonRouteFormRequest;
use App\Logic\Datatables\AuthorNameColumnHandler;
use App\Logic\Datatables\DatatablesHandler;
use App\Logic\Datatables\DungeonRouteAffixesColumnHandler;
use App\Logic\Datatables\DungeonRouteAttributesColumnHandler;
use App\Logic\Datatables\RatingColumnHandler;
use App\Logic\Datatables\ViewsColumnHandler;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteFavorite;
use App\Models\DungeonRouteRating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class APIDungeonRouteController extends Controller
{

    use PublicKeyDungeonRoute;
    use ListsEnemies;
    use ListsEnemyPacks;
    use ListsEnemyPatrols;
    use ListsPaths;
    use ListsKillzones;
    use ListsBrushlines;
    use ListsMapComments;
    use ListsDungeonStartMarkers;
    use ListsDungeonFloorSwitchMarkers;

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    function list(Request $request)
    {
        $routes = DungeonRoute::with(['dungeon', 'affixes', 'author', 'routeattributes'])
            // ->setAppends(['dungeon', 'affixes', 'author'])
            ->selectRaw('dungeon_routes.*');

        $user = Auth::user();
        $mine = false;

        // If logged in
        if ($user !== null) {
            $mine = $request->get('mine', false);

            // Filter by our own user if logged in
            if ($mine) {
                $routes = $routes->where('author_id', '=', $user->id);
            }

            // Never show demo routes here
            if (!$user->hasRole('admin')) {
                $routes = $routes->where('demo', '=', '0');
            }

            // Handle favorites
            if ($request->get('favorites', false)) {
                $routes = $routes->whereHas('favorites', function ($query) use (&$user) {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', '=', $user->id);
                });
            }
        }

        if (!$mine) {
            $routes = $routes->where('published', true);
        }

        // Visible here to allow proper usage of indexes
        $routes->visible();

        $dtHandler = new DatatablesHandler($request);

        return $dtHandler->setBuilder($routes)->addColumnHandler([
            // Handles any searching/filtering based on DR Affixes
            new DungeonRouteAffixesColumnHandler($dtHandler),
            // Sort by the amount of attributes
            new DungeonRouteAttributesColumnHandler($dtHandler),
            // Allow sorting by author name
            new AuthorNameColumnHandler($dtHandler),
            // Allow sorting by views
            new ViewsColumnHandler($dtHandler),
            // Allow sorting by rating
            new RatingColumnHandler($dtHandler)
        ])->applyRequestToBuilder()->getResult();
    }

    /**
     * @param APIDungeonRouteFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(APIDungeonRouteFormRequest $request, DungeonRoute $dungeonroute = null)
    {
        if ($dungeonroute === null) {
            $dungeonroute = new DungeonRoute();
        }

        // Update or insert it
        if (!$dungeonroute->saveFromRequest($request)) {
            abort(500, 'Unable to save dungeonroute');
        }

        return ['key' => $dungeonroute->public_key];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function delete(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->delete()) {
            abort(500, 'Unable to delete dungeonroute');
        }

        return ['result' => 'success'];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function publish(Request $request, DungeonRoute $dungeonroute)
    {
        $dungeonroute->published = intval($request->get('published', 0)) === 1;
        $dungeonroute->save();

        return ['result' => 'success'];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     */
    function rate(Request $request, DungeonRoute $dungeonroute)
    {
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
     * @throws \Exception
     */
    function rateDelete(Request $request, DungeonRoute $dungeonroute)
    {
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
     */
    function favorite(Request $request, DungeonRoute $dungeonroute)
    {
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
     * @throws \Exception
     */
    function favoriteDelete(Request $request, DungeonRoute $dungeonroute)
    {
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
     * @throws \Exception
     */
    function data(Request $request, $publickey)
    {
        // Init the fields we should get for this request
        $fields = $request->get('fields', ['enemy,enemypack,enemypatrol,mapcomment,dungeonstartmarker,dungeonfloorswitchmarker']);
        $fields = explode(',', $fields);

        // Show enemies or raw data when fetching enemy packs
        $enemies = (int)$request->get('enemies', true) === 1;
        $teeming = (int)$request->get('teeming', false) === 1;

        // Start parsing
        $result = [];
        if ($publickey === 'try' || $publickey === 'admin') {
            // Delete it so we don't fetch stuff we shouldn't!
            $publickey = null;
        } else {
            // Fetch dungeon route specific properties
            // Paths
            if (in_array('path', $fields)) {
                $result['path'] = $this->listPaths($request->get('floor'), $publickey);
            }

            // Killzone
            if (in_array('killzone', $fields)) {
                $result['killzone'] = $this->listKillzones($request->get('floor'), $publickey);
            }

            // Brushline
            if (in_array('brushline', $fields)) {
                $result['brushline'] = $this->listBrushlines($request->get('floor'), $publickey);
            }
        }

        // Enemies
        if (in_array('enemy', $fields)) {
            $showMdtEnemies = false;
            // Only admins are allowed to see this
            if (Auth::check() && Auth::user()->hasRole('admin')) {
                // Only fetch it now
                $showMdtEnemies = (int)$request->get('show_mdt_enemies', 0) === 1;
            }

            $result['enemy'] = $this->listEnemies($request->get('floor'), $showMdtEnemies, $publickey);
        }

        // Enemy packs
        if (in_array('enemypack', $fields)) {
            // If logged in, and we're NOT an admin
            if (Auth::check() && !Auth::user()->hasRole('admin')) {
                // Don't expose vertices
                $enemies = true;
            }
            $result['enemypack'] = $this->listEnemyPacks($request->get('floor'), $enemies, $teeming);
        }

        // Enemy patrols
        if (in_array('enemypatrol', $fields)) {
            $result['enemypatrol'] = $this->listEnemyPatrols($request->get('floor'));
        }

        // Map comments
        if (in_array('mapcomment', $fields)) {
            $result['mapcomment'] = $this->listMapComments($request->get('floor'), $publickey);
        }

        // Enemy patrols
        if (in_array('dungeonstartmarker', $fields)) {
            $result['dungeonstartmarker'] = $this->listDungeonStartMarkers($request->get('floor'));
        }

        // Enemy patrols
        if (in_array('dungeonfloorswitchmarker', $fields)) {
            $result['dungeonfloorswitchmarker'] = $this->listDungeonFloorSwitchMarkers($request->get('floor'));
        }


        return $result;
    }
}
