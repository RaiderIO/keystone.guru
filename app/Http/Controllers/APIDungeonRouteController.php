<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\DungeonRouteFavorite;
use App\Models\DungeonRouteRating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Yajra\DataTables\Facades\DataTables;

class APIDungeonRouteController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function list(Request $request)
    {
        $builder = DungeonRoute::query()->with(['dungeon', 'affixes', 'author']);
        // No unlisted routes!
        $builder = $builder->where('unlisted', false);

        $builder->whereHas('dungeon', function ($query) {
            /** @var $query Builder This uses the ActiveScope from the Dungeon; dungeon must be active for the route to show up */
            $query->active();
        });

        $user = Auth::user();
        $mine = false;

        // If logged in
        if ($user !== null) {
            $mine = $request->get('mine', false);

            // Filter by our own user if logged in
            if ($mine) {
                $builder = $builder->where('author_id', '=', $user->id);
            }

            // Never show demo routes here
            if (!$user->hasRole('admin')) {
                $builder = $builder->where('demo', '=', '0');
            }

            // Handle favorites
            if ($request->get('favorites', false)) {
                $builder->whereHas('favorites', function ($query) use (&$user) {
                    /** @var $query Builder */
                    $query->where('dungeon_route_favorites.user_id', '=', $user->id);
                });
            }
        }

        // If we're not viewing our own routes, only select published routes
        if (!$mine) {
            $builder = $builder->where('published', true);
        }


        // Handle searching
        if ($request->has('columns')) {
            $columns = $request->get('columns');

            $affixes = $columns[3]['search']['value'];
            if (!empty($affixes)) {
                $affixIds = explode(',', $affixes);

                $builder->whereHas('affixes', function ($query) use (&$affixIds) {
                    /** @var $query Builder */
                    $query->whereIn('affix_groups.id', $affixIds);
                });
            }

            // Unset the search value, we already filtered it and I don't know how to convince DT to do the above for me
            $columns[3]['search']['value'] = '';
            // Apply to request parameters
            $request->merge(['columns' => $columns]);
        }


//        $result = DataTables::eloquent($builder)->toArray();
//
//        if ($request->has('order')) {
//            $order = $request->get('order');
//
//            if (intval($order[0]['column']) === 5) {
//                array_multisort(array_column($result['data'], 'avg_rating'), SORT_ASC,  $result['data']);
//            }
//        }
//
//        return $result;

        return DataTables::eloquent($builder)->make(true);
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute = null)
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
        $user = Auth::user();

        // @TODO This should be in a policy?
        $result = false;
        if ($dungeonroute->author_id === $user->id || $user->hasRole('admin')) {
            $result = $dungeonroute->delete();
        }

        if (!$result) {
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
        $user = Auth::user();

        // @TODO This should be in a policy?
        if ($dungeonroute->author_id === $user->id || $user->hasRole('admin')) {
            $dungeonroute->published = intval($request->get('published', 0)) === 1;
            $dungeonroute->save();
        }

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

        if ($dungeonroute->isOwnedByCurrentUser()) {
            return response()->setStatusCode(403);
        }

        if ($value > 0) {
            $user = Auth::user();

            if ($user->id !== $dungeonroute->author_id) {
                /** @var DungeonRouteRating $dungeonRouteRating */
                $dungeonRouteRating = DungeonRouteRating::firstOrNew(['dungeon_route_id' => $dungeonroute->id, 'user_id' => $user->id]);
                $dungeonRouteRating->rating = max(1, min(10, $value));
                $dungeonRouteRating->save();
            } else {
                abort(Http::FORBIDDEN, 'You cannot rate your own routes.');
            }
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
