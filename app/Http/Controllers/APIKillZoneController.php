<?php

namespace App\Http\Controllers;

use App\Events\DungeonRoute\KillZoneChangedEvent;
use App\Events\DungeonRoute\KillZoneDeletedEvent;
use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class APIKillZoneController extends Controller
{
    use ChecksForDuplicates;

    /**
     * @param DungeonRoute $dungeonroute
     * @param array $data
     * @return KillZone
     * @throws \Exception
     */
    private function _saveKillZone(DungeonRoute $dungeonroute, array $data)
    {
        /** @var KillZone $killZone */
        $killZone = KillZone::findOrNew($data['id']);

        $killZone->dungeon_route_id = $dungeonroute->id;
        $killZone->floor_id = (int) $data['floor_id'] ?? $killZone->floor_id;
        $killZone->color = $data['color'] ?? $killZone->color;
        $killZone->lat = $data['lat'] ?? $killZone->lat;
        $killZone->lng = $data['lng'] ?? $killZone->lng;
        $killZone->index = (int) $data['index'] ?? $killZone->index;


        if (!$killZone->exists) {
            // Find out of there is a duplicate
            $this->checkForDuplicate($killZone);
        }

        if ($killZone->save()) {
            $killZone->deleteEnemies();

            // Only when the enemies are actually set
            if (isset($data['enemies'])) {

                // Get the new enemies, only unique values in case there's some bug allowing selection of the same enemy multiple times
                $enemyIds = array_unique($data['enemies'] ?? []);

                // Store them, but only if the enemies are part of the same dungeon as the dungeonroute
                $killZoneEnemies = [];
                $enemyModels = Enemy::with('floor')->whereIn('id', $enemyIds)->get();
                foreach ($enemyIds as $enemyId) {
                    /** @var Enemy $enemy */
                    $enemy = $enemyModels->where('id', $enemyId)->first();
                    if ($dungeonroute->dungeon_id === $enemy->floor->dungeon_id) {
                        // Assign kill zone to each passed enemy
                        $killZoneEnemies[] = [
                            'kill_zone_id' => $killZone->id,
                            'enemy_id'     => $enemyId
                        ];
                    }
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);
            }

            // Refresh the enemies that may or may not have been set
            $killZone->load(['killzoneenemies']);

            if (Auth::check()) {
                // Something's updated; broadcast it
                broadcast(new ModelChangedEvent($dungeonroute, Auth::user(), $killZone));
            }
        } else {
            throw new \Exception('Unable to save kill zone!');
        }

        return $killZone;
    }


    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return KillZone
     * @throws \Exception
     */
    function store(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        try {
            $killZone = $this->_saveKillZone($dungeonroute, $request->all());

            // Touch the route so that the thumbnail gets updated
            $dungeonroute->touch();

            $result = $killZone;
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return array|ResponseFactory|Response|null
     * @throws AuthorizationException
     * @throws \Exception
     */
    function storeall(Request $request, DungeonRoute $dungeonroute)
    {
        if (!$dungeonroute->isTry()) {
            $this->authorize('edit', $dungeonroute);
        }

        // We're deliberately overwriting the $result constantly, we're only interested in the last result
        $result = null;
        foreach ($request->get('killzones', []) as $killZoneData) {
            try {
                $this->_saveKillZone($dungeonroute, $killZoneData);
            } catch (Exception $ex) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // Touch the route so that the thumbnail gets updated
        $dungeonroute->touch();

        return ['enemy_forces' => $dungeonroute->getEnemyForces()];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param KillZone $killzone
     * @return array|ResponseFactory|Response
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
                    broadcast(new ModelDeletedEvent($dungeonroute, Auth::user(), $killzone));
                }

                // Refresh the killzones relation
                $dungeonroute->load('killzones');

                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

                $result = ['enemy_forces' => $dungeonroute->getEnemyForces()];
            } else {
                $result = response('Unable to delete pull', Http::INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
