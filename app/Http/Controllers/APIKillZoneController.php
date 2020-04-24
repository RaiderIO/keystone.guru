<?php

namespace App\Http\Controllers;

use App\Events\KillZoneChangedEvent;
use App\Events\KillZoneDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\DungeonRoute;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIKillZoneController extends Controller
{
    use ChecksForDuplicates;

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        /** @var KillZone $killZone */
        $killZone = KillZone::findOrNew($request->get('id'));

        try {
            $killZone->dungeon_route_id = $dungeonroute->id;
            $killZone->floor_id = $request->get('floor_id');
            $killZone->color = $request->get('color');
            $killZone->lat = $request->get('lat');
            $killZone->lng = $request->get('lng');

            if (!$killZone->exists) {
                // Find out of there is a duplicate
                $this->checkForDuplicate($killZone);
            }

            if (!$killZone->save()) {
                throw new \Exception("Unable to save kill zone!");
            } else {
                $killZone->deleteEnemies();

                // Get the new enemies, only unique values in case there's some bug allowing selection of the same enemy multiple times
                $enemies = array_unique($request->get('enemies', []));

                // Store them
                $killZoneEnemies = [];
                foreach ($enemies as $enemyId) {
                    // Assign kill zone to each passed enemy
                    $killZoneEnemies[] = [
                        'kill_zone_id' => $killZone->id,
                        'enemy_id'     => $enemyId
                    ];
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);

                if (Auth::check()) {
                    // Something's updated; broadcast it
                    broadcast(new KillZoneChangedEvent($dungeonroute, $killZone, Auth::user()));
                }

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();
            }

            $result = ['id' => $killZone->id, 'enemy_forces' => $dungeonroute->getEnemyForcesAttribute()];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param KillZone $killzone
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function delete(Request $request, DungeonRoute $dungeonroute, KillZone $killzone)
    {
        if (!$dungeonroute->isTry()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonroute);
        }

        try {

            if ($killzone->delete()) {
                if (Auth::check()) {
                    broadcast(new KillZoneDeletedEvent($dungeonroute, $killzone, Auth::user()));
                }

                // Refresh the killzones relation
                $dungeonroute->load('killzones');

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

                $result = ['result' => 'success', 'enemy_forces' => $dungeonroute->getEnemyForcesAttribute()];
            } else {
                $result = ['result' => 'error'];
            }

        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
