<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Requests\KillZone\APIDeleteAllFormRequest;
use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Http\Requests\KillZone\APIKillZoneMassFormRequest;
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
    /**
     * @param DungeonRoute $dungeonroute
     * @param array $data
     * @param bool $recalculateEnemyForces
     * @return KillZone
     * @throws \Exception
     */
    private function saveKillZone(DungeonRoute $dungeonroute, array $data, bool $recalculateEnemyForces = true): KillZone
    {
        $enemyIds = $data['enemies'] ?? null;
        unset($data['enemies']);
        $data['dungeon_route_id'] = $dungeonroute->id;

        /** @var KillZone $killZone */
        $killZone = KillZone::with('dungeonRoute')->findOrNew($data['id']);

        $dungeonroute = $killZone->dungeonRoute ?? $dungeonroute;
        // Prevent someone from updating different killzones than they are allowed to
        $this->authorize('edit', $killZone->dungeonRoute);

        if (!$killZone->exists) {
            $killZone = KillZone::create($data);
            $success  = $killZone instanceof KillZone;
        } else {
            $success = $killZone->update($data);
        }

        if ($success) {
            // Only when the enemies are actually set
            if ($enemyIds !== null) {
                $killZone->killzoneEnemies()->delete();

                // Store them, but only if the enemies are part of the same dungeon as the dungeonroute
                $validEnemyIds   = [];
                $killZoneEnemies = [];
                $enemyModels     = $dungeonroute->mappingVersion->enemies()->whereIn('id', $enemyIds)->get();
                foreach ($enemyIds as $enemyId) {
                    /** @var Enemy $enemy */
                    $enemy = $enemyModels->where('id', $enemyId)->first();
                    // Could be if someone decides to send an enemy ID that is not part of the current mapping version
                    if ($enemy === null) {
                        continue;
                    }

                    // Assign kill zone to each passed enemy
                    $killZoneEnemies[] = [
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                    ];
                    $validEnemyIds[]   = $enemyId;
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);
                $killZone->enemies = collect($validEnemyIds);
            }

            if ($recalculateEnemyForces) {
                // Update the enemy forces
                $dungeonroute->update(['enemy_forces' => $dungeonroute->getEnemyForces()]);
                // Touch the route so that the thumbnail gets updated
                $dungeonroute->touch();
            }

            if (Auth::check()) {
                // Something's updated; broadcast it
                broadcast(new ModelChangedEvent($dungeonroute, Auth::user(), $killZone));
            }
        } else {
            throw new Exception('Unable to save kill zone!');
        }

        return $killZone;
    }


    /**
     * @param APIKillZoneFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @param KillZone|null $killZone
     * @return KillZone
     * @throws AuthorizationException
     */
    function store(APIKillZoneFormRequest $request, DungeonRoute $dungeonRoute, KillZone $killZone = null): KillZone
    {
        $dungeonRoute = optional($killZone)->dungeonRoute ?? $dungeonRoute;

        try {
            $data = $request->validated();
            // Make sure that if we're unsetting all enemies from the killzone, it's handled differently
            // than mass-updating and not wanting to update the enemies at all
            if (!isset($data['enemies'])) {
                $data['enemies'] = [];
            }
            $data['id'] = optional($killZone)->id ?? null;
            $killZone   = $this->saveKillZone($dungeonRoute, $data);

            // Touch the route so that the thumbnail gets updated
            $dungeonRoute->touch();

            $result = $killZone;
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param APIKillZoneMassFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @return array|ResponseFactory|Response|null
     * @throws AuthorizationException
     */
    public function storeAll(APIKillZoneMassFormRequest $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        // Update killzones
        $killZones = new Collection();
        foreach ($validated['killzones'] as $killZoneData) {
            try {
                // Unset the enemies since we're quicker to update that in bulk here
                $kzDataWithoutEnemies = $killZoneData;
                unset($kzDataWithoutEnemies['enemies']);
                // Do not save the enemy forces - we save it one time down below
                $killZones->push($this->saveKillZone($dungeonRoute, $kzDataWithoutEnemies, false));
            } catch (Exception $ex) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // Save enemy data at once and not one by one - it's slow
        $killZoneEnemies = [];
        $enemies         = $dungeonRoute->mappingVersion->enemies->keyBy('id');
        $validEnemyIds   = $enemies->pluck('id')->toArray();

        // Insert new enemies based on what was sent
        foreach ($validated['killzones'] as $killZoneData) {
            try {
                if (isset($killZoneData['enemies'])) {
                    // Filter enemies - only allow those who are actually on the allowed floors (don't couple to enemies in other dungeons)
                    $killZoneDataEnemies = array_filter($killZoneData['enemies'], function ($item) use ($validEnemyIds) {
                        return in_array($item, $validEnemyIds);
                    });

                    // Assign kill zone to each passed enemy
                    foreach ($killZoneDataEnemies as $killZoneDataEnemyId) {
                        /** @var Enemy $enemy */
                        $enemy             = $enemies->get($killZoneDataEnemyId);
                        $killZoneEnemies[] = [
                            'kill_zone_id' => $killZoneData['id'],
                            'npc_id'       => $enemy->npc_id,
                            'mdt_id'       => $enemy->mdt_id,
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
        $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
        // Touch the route so that the thumbnail gets updated
        $dungeonRoute->touch();

        return ['enemy_forces' => $dungeonRoute->enemy_forces];
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonRoute
     * @param KillZone $killZone
     * @return array|ResponseFactory|Response
     * @throws \Exception
     */
    function delete(Request $request, DungeonRoute $dungeonRoute, KillZone $killZone)
    {
        $dungeonRoute = $killZone->dungeonRoute;

        if (!$dungeonRoute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonRoute);
        }

        try {

            if ($killZone->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($dungeonRoute, Auth::user(), $killZone));
                }

                $dungeonRoute->load('killzones');
                $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = ['enemy_forces' => $dungeonRoute->enemy_forces];
            } else {
                $result = response('Unable to delete pull', Http::INTERNAL_SERVER_ERROR);
            }

        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param APIDeleteAllFormRequest $request
     * @param DungeonRoute $dungeonRoute
     * @return array|Application|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function deleteAll(APIDeleteAllFormRequest $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        if ($validated['confirm'] === 'yes') {
            try {
                $killZones       = $dungeonRoute->killzones;
                $pridefulEnemies = $dungeonRoute->pridefulenemies;

                $dungeonRoute->killzones()->delete();
                $dungeonRoute->pridefulenemies()->delete();

                if (Auth::check()) {
                    foreach ($killZones as $killZone) {
                        broadcast(new ModelDeletedEvent($dungeonRoute, Auth::user(), $killZone));
                    }

                    foreach ($pridefulEnemies as $pridefulEnemy) {
                        broadcast(new ModelDeletedEvent($dungeonRoute, Auth::user(), $pridefulEnemy));
                    }
                }

                $dungeonRoute->load('killzones');
                $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = ['enemy_forces' => $dungeonRoute->enemy_forces];
            } catch (\Exception $ex) {
                $result = response('Not found', Http::NOT_FOUND);
            }
        } else {
            $result = response('You must confirm before deleting all pulls', Http::BAD_REQUEST);
        }

        return $result;
    }
}
