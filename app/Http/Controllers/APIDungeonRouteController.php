<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

        $authorId = intval($request->get('author_id', -1));
        // Filter by our own user if logged in
        if ($authorId > -1) {
            $builder = $builder->where('author_id', '=', $authorId);
        }

        // This is safe enough, even with the links people will get denied access
        // @TODO hardcoded admin ID?
        if ($authorId !== 1) {
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
}
