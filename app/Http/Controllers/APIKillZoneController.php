<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Requests\KillZone\DeleteAllFormRequest;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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
        $killZone->floor_id = (int)((isset($data['floor_id']) && $data['floor_id'] !== null) ? $data['floor_id'] : $killZone->floor_id);
        $killZone->color = (isset($data['color']) && $data['color'] !== null) ? $data['color'] : $killZone->color;
        $killZone->lat = (isset($data['lat']) && $data['lat'] !== null) ? $data['lat'] : $killZone->lat;
        $killZone->lng = (isset($data['lng']) && $data['lng'] !== null) ? $data['lng'] : $killZone->lng;
        $killZone->index = (int)((isset($data['index']) && $data['index'] !== null) ? $data['index'] : $killZone->index);

        if (!$killZone->exists && $killZone->lat !== null && $killZone->lng !== null) {
            // Find out of there is a duplicate
            $this->checkForDuplicate($killZone);
        }

        if ($killZone->save()) {
            // Only when the enemies are actually set
            if (isset($data['enemies'])) {
                $killZone->deleteEnemies();

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

            // Update the enemy forces
            $dungeonroute->update(['enemy_forces' => $dungeonroute->getEnemyForces()]);
            // Touch the route so that the thumbnail gets updated
            $dungeonroute->touch();

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
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        try {
            $data = $request->all();
            // Make sure that if we're unsetting all enemies from the killzone, it's handled differently
            // than mass-updating and not wanting to update the enemies at all
            if (!isset($data['enemies'])) {
                $data['enemies'] = [];
            }
            $killZone = $this->_saveKillZone($dungeonroute, $data);

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
        if (!$dungeonroute->isSandbox()) {
            $this->authorize('edit', $dungeonroute);
        }

        // We're deliberately overwriting the $result constantly, we're only interested in the last result
        $result = null;

        // Update killzones
        $killZones = new Collection();
        foreach ($request->get('killzones', []) as $killZoneData) {
            try {
                // Unset the enemies since we're quicker to update that in bulk here
                $kzDataWithoutEnemies = $killZoneData;
                unset($kzDataWithoutEnemies['enemies']);
                $killZones->push($this->_saveKillZone($dungeonroute, $kzDataWithoutEnemies));
            } catch (Exception $ex) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // Save enemy data at once and not one by one - it's slow
        $killZoneEnemies = [];
        $enemyIds = Enemy::select('id')->whereIn('floor_id', $dungeonroute->dungeon->floors->pluck('id')->toArray())->get()->pluck('id')->toArray();

        // Insert new enemies based on what was sent
        foreach ($request->get('killzones', []) as $killZoneData) {
            try {
                if (isset($killZoneData['enemies'])) {
                    // Filter enemies - only allow those who are actually on the allowed floors (don't couple to enemies in other dungeons)
                    $killZoneDataEnemies = array_filter($killZoneData['enemies'], function ($item) use ($enemyIds)
                    {
                        return in_array($item, $enemyIds);
                    });

                    // Assign kill zone to each passed enemy
                    foreach ($killZoneDataEnemies as $killZoneDataEnemy) {
                        $killZoneEnemies[] = [
                            'kill_zone_id' => $killZoneData['id'],
                            'enemy_id'     => $killZoneDataEnemy
                        ];
                    }
                }
            } catch (Exception $ex) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // May be empty if the user did not send any enemies
        if (count($killZoneEnemies) > 0) {
            // Delete existing enemies
            KillZoneEnemy::whereIn('kill_zone_id', $killZones->pluck('id')->toArray())->delete();
            // Save all new enemies at once
            KillZoneEnemy::insert($killZoneEnemies);
        }


        // Update the enemy forces
        $dungeonroute->update(['enemy_forces' => $dungeonroute->getEnemyForces()]);
        // Touch the route so that the thumbnail gets updated
        $dungeonroute->touch();

        return ['enemy_forces' => $dungeonroute->enemy_forces];
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
        if (!$dungeonroute->isSandbox()) {
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

                // Update the enemy forces
                $dungeonroute->update(['enemy_forces' => $dungeonroute->getEnemyForces()]);
                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();

                $result = ['enemy_forces' => $dungeonroute->enemy_forces];
            } else {
                $result = response('Unable to delete pull', Http::INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param DeleteAllFormRequest $request
     * @param DungeonRoute $dungeonroute
     * @return array|Application|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function deleteAll(DeleteAllFormRequest $request, DungeonRoute $dungeonroute)
    {
        $this->authorize('edit', $dungeonroute);

        if ($request->get('confirm') === 'yes') {
            try {
                $killZones = $dungeonroute->killzones;
                if ($dungeonroute->killzones()->delete()) {
                    if (Auth::check()) {
                        foreach ($killZones as $killZone) {
                            broadcast(new ModelDeletedEvent($dungeonroute, Auth::user(), $killZone));
                        }
                    }

                    // Refresh the killzones relation
                    $dungeonroute->load('killzones');

                    // Update the enemy forces
                    $dungeonroute->update(['enemy_forces' => $dungeonroute->getEnemyForces()]);
                    // Touch the route so that the thumbnail gets updated
                    $dungeonroute->touch();

                    $result = ['enemy_forces' => $dungeonroute->enemy_forces];
                } else {
                    $result = response('Unable to delete all pulls', Http::INTERNAL_SERVER_ERROR);
                }

            } catch (\Exception $ex) {
                $result = response('Not found', Http::NOT_FOUND);
            }
        } else {
            $result = response('You must confirm before deleting all pulls', Http::BAD_REQUEST);
        }

        return $result;
    }
}
