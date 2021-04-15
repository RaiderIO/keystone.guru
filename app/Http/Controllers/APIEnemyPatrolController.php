<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemyPatrols;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Models\EnemyPatrol;
use App\Models\Polyline;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyPatrolController extends Controller
{
    use ChangesMapping;
    use ChecksForDuplicates;
    use SavesPolylines;

    /**
     * @param Request $request
     * @return EnemyPatrol
     * @throws Exception
     */
    function store(Request $request)
    {
        /** @var EnemyPatrol $enemyPatrol */
        $enemyPatrol = EnemyPatrol::findOrNew($request->get('id'));

        $enemyPatrolBefore = clone $enemyPatrol;

        $enemyPatrol->floor_id = $request->get('floor_id');
        $enemyPatrol->teeming = $request->get('teeming');
        $enemyPatrol->faction = $request->get('faction', 'any');

        // Init to a default value if new
        if (!$enemyPatrol->exists) {
            $enemyPatrol->polyline_id = -1;
        }

        if ($enemyPatrol->save()) {
            // Create a new polyline and save it
            $polyline = $this->_savePolyline(Polyline::findOrNew($enemyPatrol->polyline_id), $enemyPatrol, $request->get('polyline'));

            // Couple the patrol to the polyline
            $enemyPatrol->polyline_id = $polyline->id;
            // Load the polyline so it can be echoed back to the user
            $enemyPatrol->load(['polyline']);

            if ($enemyPatrol->save()) {
                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($enemyPatrol->floor->dungeon, Auth::getUser(), $enemyPatrol));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPatrolBefore, $enemyPatrol);
            }
        } else {
            throw new Exception('Unable to save enemy patrol!');
        }

        return $enemyPatrol;
    }

    /**
     * @param Request $request
     * @param EnemyPatrol $enemypatrol
     * @return array|ResponseFactory|Response
     */
    function delete(Request $request, EnemyPatrol $enemypatrol)
    {
        try {
            if ($enemypatrol->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemypatrol->floor->dungeon, Auth::getUser(), $enemypatrol));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemypatrol, null);
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
