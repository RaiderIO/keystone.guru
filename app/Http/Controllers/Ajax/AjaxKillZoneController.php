<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Requests\KillZone\APIDeleteAllFormRequest;
use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Http\Requests\KillZone\APIKillZoneMassFormRequest;
use App\Jobs\RefreshEnemyForces;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Teapot\StatusCode\Http;

class AjaxKillZoneController extends Controller
{
    use ChangesDungeonRoute;

    /**
     * @throws \Exception
     */
    private function saveKillZone(
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonroute,
        array                       $data,
        bool                        $recalculateEnemyForces = true
    ): KillZone {
        $enemyIds = $data['enemies'] ?? null;
        unset($data['enemies']);
        $data['dungeon_route_id'] = $dungeonroute->id;

        $spellIds = $data['spells'] ?? null;

        /** @var KillZone $killZone */
        $killZone = KillZone::with('dungeonRoute')->findOrNew($data['id']);

        $dungeonroute = $killZone->dungeonRoute ?? $dungeonroute;
        // Prevent someone from updating different killzones than they are allowed to
        if ($killZone->dungeonRoute !== null && !$killZone->dungeonRoute->isSandbox()) {
            $this->authorize('edit', $killZone->dungeonRoute);
        }

        $this->authorize('addKillZone', $dungeonroute);

        /** @var KillZone|null $beforeModel */
        $beforeModel = $killZone === null ? null : clone $killZone;

        if (!$killZone->exists) {
            $killZone = KillZone::create($data);
            // We JUST created the KillZone - so it does not have enemies (yet)
            $killZone->setEnemiesAttributeCache(collect());
            $success  = $killZone instanceof KillZone;
        } else {
            // Set the cache on the before model to properly store the changes
            $beforeModel->getEnemiesAttribute(true);
            $success = $killZone->update($data);
        }

        if ($success) {
            // Only when the enemies are actually set
            if ($enemyIds !== null) {
                $killZone->killZoneEnemies()->delete();

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
                    $validEnemyIds[]   = (int)$enemyId;
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);

                $killZone->setEnemiesAttributeCache(collect($validEnemyIds));
            }

            // May be null for mass request
            if ($spellIds !== null) {
                $killZone->killZoneSpells()->delete();

                $spellsAttributes = [];
                foreach ($spellIds as $spellId) {
                    $spellsAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'spell_id'     => $spellId,
                    ];
                }

                KillZoneSpell::insert($spellsAttributes);
                $killZone->load(['spells:id,icon_name']);
            }

            if ($recalculateEnemyForces) {
                RefreshEnemyForces::dispatch($dungeonroute->id);
            }

            $this->dungeonRouteChanged($dungeonroute, $beforeModel, $killZone, function (array &$beforeAttributes, array &$afterAttributes) use ($beforeModel, $killZone) {
                $beforeAttributes['enemies'] = $beforeModel?->getEnemiesAttribute() ?? [];
                $afterAttributes['enemies']  = $killZone->getEnemiesAttribute();
            });

            // If killzone has lat/lng set, convert it to facade location if it's not already
            $useFacade = $dungeonroute->mappingVersion->facade_enabled &&
                User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;

            if ($killZone->hasKillArea()) {
                // Track this latlng so we can re-echo it back to the user if we still want to use facades
                $originalLatLng = $killZone->getLatLng();

                // Only when we actually have a kill location set!
                if ($useFacade && $originalLatLng->getFloor() !== null) {
                    $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                        $dungeonroute->mappingVersion,
                        $originalLatLng
                    );

                    // Save the killzone with converted lat/lngs in the database
                    $killZone->update([
                        'lat'      => $latLng->getLat(),
                        'lng'      => $latLng->getLng(),
                        'floor_id' => $latLng->getFloor()->id,
                    ]);
                }

                // But echo it back as facade!
                $killZone->setAttribute('lat', $originalLatLng->getLat());
                $killZone->setAttribute('lng', $originalLatLng->getLng());
                $killZone->setAttribute('floor_id', $originalLatLng->getFloor()->id);
                $killZone->setRelation('floor', $originalLatLng->getFloor());
            }

            if (Auth::check()) {
                // Something's updated; broadcast it
                /** @var User $user */
                $user = Auth::user();
                broadcast(new ModelChangedEvent($dungeonroute, $user, $killZone));
            }
        } else {
            throw new Exception('Unable to save kill zone!');
        }

        return $killZone;
    }

    /**
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function store(
        CoordinatesServiceInterface $coordinatesService,
        APIKillZoneFormRequest      $request,
        DungeonRoute                $dungeonRoute,
        ?KillZone                   $killZone = null
    ): KillZone {
        $dungeonRoute = $killZone?->dungeonRoute ?? $dungeonRoute;

        try {
            $data = $request->validated();
            // Make sure that if we're unsetting all enemies from the killzone, it's handled differently
            // than mass-updating and not wanting to update the enemies at all
            if (!isset($data['enemies'])) {
                $data['enemies'] = [];
            }

            if (!isset($data['spells'])) {
                $data['spells'] = [];
            }

            $data['id'] = $killZone?->id ?? null;

            $result = $this->saveKillZone($coordinatesService, $dungeonRoute, $data, $killZone !== null);
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return array|ResponseFactory|Response|null
     *
     * @throws AuthorizationException
     */
    public function storeAll(
        CoordinatesServiceInterface $coordinatesService,
        APIKillZoneMassFormRequest  $request,
        DungeonRoute                $dungeonRoute
    ) {
        $this->authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        // Update killzones
        $killZones = new Collection();
        foreach ($validated['killzones'] ?? [] as $killZoneData) {
            try {
                // Unset the enemies since we're quicker to update that in bulk here
                $kzDataWithoutEnemies = $killZoneData;
                unset($kzDataWithoutEnemies['enemies']);
                // Do not save the enemy forces - we save it one time down below
                $killZones->push($this->saveKillZone($coordinatesService, $dungeonRoute, $kzDataWithoutEnemies, false));
            } catch (Exception) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // Save enemy data at once and not one by one - it's slow
        $killZoneEnemies = [];
        $enemies         = $dungeonRoute->mappingVersion->enemies->keyBy('id');
        $validEnemyIds   = $enemies->pluck('id')->toArray();

        // Insert new enemies based on what was sent
        foreach ($validated['killzones'] ?? [] as $killZoneData) {
            try {
                if (isset($killZoneData['enemies'])) {
                    // Filter enemies - only allow those who are actually on the allowed floors (don't couple to enemies in other dungeons)
                    $killZoneDataEnemies = array_filter($killZoneData['enemies'], static fn($item) => in_array($item, $validEnemyIds));

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
            } catch (Exception) {
                return response(sprintf('Unable to find kill zone %s', $killZoneData['id']), Http::NOT_FOUND);
            }
        }

        // May be empty if the user did not send any enemies
        if ($killZoneEnemies !== []) {
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
     * @return array|ResponseFactory|Response
     *
     * @throws \Exception
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, KillZone $killZone)
    {
        $dungeonRoute = $killZone->dungeonRoute;

        if (!$dungeonRoute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            $this->authorize('edit', $dungeonRoute);
        }

        try {

            if ($killZone->delete()) {
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::user();
                    broadcast(new ModelDeletedEvent($dungeonRoute, $user, $killZone));
                }

                $dungeonRoute->load('killZones');
                $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);

                $this->dungeonRouteChanged($dungeonRoute, $killZone, null);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = ['enemy_forces' => $dungeonRoute->enemy_forces];
            } else {
                $result = response('Unable to delete pull', Http::INTERNAL_SERVER_ERROR);
            }

        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function deleteAll(APIDeleteAllFormRequest $request, DungeonRoute $dungeonRoute)
    {
        $this->authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        if ($validated['confirm'] === 'yes') {
            try {
                $killZones       = $dungeonRoute->killZones;
                $pridefulEnemies = $dungeonRoute->pridefulEnemies;

                $dungeonRoute->killZones()->delete();
                $dungeonRoute->pridefulEnemies()->delete();

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::user();
                    foreach ($killZones as $killZone) {
                        $this->dungeonRouteChanged($dungeonRoute, $killZone, null);

                        broadcast(new ModelDeletedEvent($dungeonRoute, $user, $killZone));
                    }

                    foreach ($pridefulEnemies as $pridefulEnemy) {
                        broadcast(new ModelDeletedEvent($dungeonRoute, $user, $pridefulEnemy));
                    }
                }

                $dungeonRoute->load('killZones');
                $dungeonRoute->update(['enemy_forces' => 0]);
                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                $result = ['enemy_forces' => $dungeonRoute->enemy_forces];
            } catch (\Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }
        } else {
            $result = response('You must confirm before deleting all pulls', Http::BAD_REQUEST);
        }

        return $result;
    }
}
