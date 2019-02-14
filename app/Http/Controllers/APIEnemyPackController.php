<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\EnemyPack;
use App\Models\EnemyPackVertex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyPackController extends Controller
{
    use ChecksForDuplicates;

    //
    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $teeming = $request->get('teeming', false);
        $vertices = $request->get('vertices', false);

        // If logged in, and we're NOT an admin
        if (Auth::check() && !Auth::user()->hasRole('admin')) {
            // Don't expose vertices
            $vertices = false;
        }

        /** @var Builder $result */
        $result = null;
        $fields = ['id', 'label', 'faction'];
        if ($vertices) {
            $fields[] = 'vertices_json';
        } else {
            $result = EnemyPack::with(['enemies' => function ($query) use ($teeming) {
                /** @var $query \Illuminate\Database\Query\Builder */
                // Only include teeming enemies when requested
                if (!$teeming) {
                    $query->where('teeming', null);
                }
                $query->select(['enemy_pack_id', 'lat', 'lng']); // must select enemy_pack_id, else it won't return results /sadface
            }]);
        }

        $enemyPacks = $result->where('floor_id', '=', $floorId)->get($fields);
        $enemyPacks->makeHidden('enemy_pack_id');

        return $enemyPacks;
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::findOrNew($request->get('id'));

        $enemyPack->faction = $request->get('faction', 'any');
        $enemyPack->label = $request->get('label');
        $enemyPack->floor_id = $request->get('floor_id');
        $enemyPack->vertices_json = json_encode($request->get('vertices'));

        if (!$enemyPack->save()) {
            throw new \Exception("Unable to save pack!");
        }

        return ['id' => $enemyPack->id];
    }

    function delete(Request $request)
    {
        try {
            /** @var EnemyPack $enemyPack */
            $enemyPack = EnemyPack::findOrFail($request->get('id'));

            $enemyPack->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
