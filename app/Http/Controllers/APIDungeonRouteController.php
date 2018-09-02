<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\DungeonRouteRating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $builder = $builder->where('unlisted', '<>', true);

        $builder->whereHas('dungeon', function ($query) {
            /** @var $query Builder This uses the ActiveScope from the Dungeon; dungeon must be active for the route to show up */
            $query->active();
        });

        $mine = $request->get('mine', false);
        $user = Auth::user();

        // Filter by our own user if logged in
        if ($mine) {
            $builder = $builder->where('author_id', '=', $user->id);
        }

        if (!$user->hasRole('admin')) {
            // Never show demo routes here
            $builder = $builder->where('demo', '=', '0');
        }

        // Handle searching on affixes
        if ($request->has('columns')) {
            $columns = $request->get('columns');

            $affixes = $columns[2]['search']['value'];
            if (!empty($affixes)) {
                $affixIds = explode(',', $affixes);

                $builder->whereHas('affixes', function ($query) use (&$affixIds) {
                    /** @var $query Builder */
                    $query->whereIn('affix_groups.id', $affixIds);
                });
            }

            // Unset the search value, we already filtered it and I don't know how to convince DT to do the above for me
            $columns[2]['search']['value'] = '';
            // Apply to request parameters
            $request->merge(['columns' => $columns]);
        }

        return DataTables::eloquent($builder)
            ->make(true);
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

        return ['id' => $dungeonroute->id];
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
}
