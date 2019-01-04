<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIKillZoneController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            $result = KillZone::where('floor_id', '=', $floorId)->where('dungeon_route_id', '=', $dungeonRoute->id)->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
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
                        'enemy_id' => $enemyId
                    ];
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);

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
     */
    function delete(Request $request, DungeonRoute $dungeonroute, KillZone $killzone)
    {
        try {
            // @TODO handle this in a policy?
            if ($dungeonroute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $killzone->delete();

            // Refresh the killzones relation
            $dungeonroute->load('killzones');

            // Touch the route so that the thumbnail gets updated
            $dungeonroute->touch();

            $result = ['result' => 'success', 'enemy_forces' => $dungeonroute->getEnemyForcesAttribute()];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
