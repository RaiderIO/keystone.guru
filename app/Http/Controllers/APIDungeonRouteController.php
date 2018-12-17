<?php

namespace App\Http\Controllers;

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
}
