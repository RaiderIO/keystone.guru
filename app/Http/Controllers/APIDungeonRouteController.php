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

        // Filter by our own user if logged in
        if ($request->has('author_id')) {
            $builder = $builder->where('author_id', '=', $request->has('author_id'));
        }

        // Handle searching on affixes
        if ($request->has('columns')) {
            $columns = $request->get('columns');

            $affixes = $columns[2]['search']['value'];
            if (!empty($affixes)) {
                $affixIds = explode(',', $affixes);

                $builder->whereHas('affixes', function($query) use(&$affixIds){
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
