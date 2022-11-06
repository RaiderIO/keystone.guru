<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Requests\EnemyPatrol\EnemyPatrolFormRequest;
use App\Models\EnemyPatrol;
use App\Models\Polyline;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class APIEnemyPatrolController extends APIMappingModelBaseController
{
    use ChecksForDuplicates;
    use SavesPolylines;

    /**
     * @param EnemyPatrolFormRequest $request
     * @param EnemyPatrol|null $enemyPatrol
     * @return EnemyPatrol|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(EnemyPatrolFormRequest $request, EnemyPatrol $enemyPatrol = null): EnemyPatrol
    {
        $validated = $request->validated();
        return $this->storeModel($validated, EnemyPatrol::class, $enemyPatrol, function (EnemyPatrol $enemyPatrol) use ($validated) {
            // Create a new polyline and save it
            $polyline = $this->savePolyline(Polyline::findOrNew($enemyPatrol->polyline_id), $enemyPatrol, $validated['polyline']);

            // Couple the patrol to the polyline
            $enemyPatrol->polyline_id = $polyline->id;

            $saveResult = $enemyPatrol->save();

            // Load the polyline so it can be echoed back to the user
            $enemyPatrol->load(['polyline']);

            return $saveResult;
        });
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
