<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\KillZoneEnemy;
use Illuminate\Http\Request;
use App\Models\KillZone;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIKillZoneController extends Controller
{
    use PublicKeyDungeonRoute;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey);
            $result = KillZone::where('floor_id', '=', $floorId)->where('dungeon_route_id', '=', $dungeonRoute->id)->get();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    function store(Request $request)
    {
        /** @var KillZone $killZone */
        $killZone = KillZone::findOrNew($request->get('id'));

        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($request->get('dungeonroute'));

            $killZone->dungeon_route_id = $dungeonRoute->id;
            $killZone->floor_id = $request->get('floor_id');
            $killZone->lat = $request->get('lat');
            $killZone->lng = $request->get('lng');

            if (!$killZone->save()) {
                throw new \Exception("Unable to save enemy!");
            } else {
                $killZone->deleteEnemies();

                // Get the new enemies
                $enemies = $request->get('enemies', []);

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
            }

            $result = ['id' => $killZone->id, 'enemy_forces' => $dungeonRoute->getEnemyForcesAttribute()];
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    function delete(Request $request)
    {
        try {
            /** @var KillZone $killZone */
            $killZone = KillZone::findOrFail($request->get('id'));

            // @TODO WTF why does $killZone->dungeonroute not work?? It will NOT load the relation despite everything being OK?
            $dungeonroute = DungeonRoute::findOrFail($killZone->dungeon_route_id);
            // If we're not the author, don't delete anything
            // @TODO handle this in a policy?
            if ($dungeonroute->author_id !== Auth::user()->id && !Auth::user()->hasRole('admin')) {
                throw new Exception('Unauthorized');
            }

            $killZone->delete();
            $killZone->deleteEnemies();

            // Refresh the killzones relation
            $dungeonroute->load('killzones');

            $result = ['result' => 'success', 'enemy_forces' => $dungeonroute->getEnemyForcesAttribute()];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
