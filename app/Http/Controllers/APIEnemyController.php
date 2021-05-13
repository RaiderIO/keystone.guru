<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyActiveAura;
use App\Models\RaidMarker;
use App\Models\Spell;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyController extends Controller
{
    use ChangesMapping;
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    /**
     * @param Request $request
     * @return Enemy
     * @throws Exception
     */
    function store(Request $request)
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::findOrNew($request->get('id'));

        $beforeEnemy = clone $enemy;

        $enemy->enemy_pack_id = (int)$request->get('enemy_pack_id');
        $npcId = $request->get('npc_id', -1);
        $enemy->npc_id = $npcId === null ? -1 : (int)$npcId;
        // Only when set, otherwise default of -1
        $mdtId = $request->get('mdt_id', -1);
        $enemy->mdt_id = $mdtId === null ? -1 : (int)$mdtId;
        $seasonalIndex = $request->get('seasonal_index');
        // don't use is_empty since 0 is valid
        $enemy->seasonal_index = $seasonalIndex === null || $seasonalIndex === '' || $seasonalIndex < 0 ? null : (int)$seasonalIndex;
        $seasonalType = $request->get('seasonal_type', null);
        $enemy->seasonal_type = $seasonalType === null || $seasonalType === '' || $seasonalType < 0 ? null : $seasonalType;
        $enemy->floor_id = (int)$request->get('floor_id');
        $enemy->teeming = $request->get('teeming');
        $enemy->faction = $request->get('faction', 'any');
        $enemy->unskippable = (int)$request->get('unskippable', false);
        $enemy->enemy_forces_override = (int)$request->get('enemy_forces_override', -1);
        $enemy->enemy_forces_override_teeming = (int)$request->get('enemy_forces_override_teeming', -1);
        $enemy->lat = (float)$request->get('lat');
        $enemy->lng = (float)$request->get('lng');

        if ($enemy->save()) {

            // Bolstering whitelist, if set
            $activeAuras = $request->get('active_auras', []);
            // Clear current active auras
            $enemy->enemyactiveauras()->delete();
            foreach ($activeAuras as $activeAura) {
                if (!empty($activeAura)) {
                    $spell = Spell::findOrFail($activeAura);
                    // Only when the passed spell is actually an aura
                    if ($spell->aura) {
                        EnemyActiveAura::insert([
                            'enemy_id' => $enemy->id,
                            'spell_id' => $activeAura
                        ]);
                    }
                }
            }


            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($beforeEnemy, $enemy);
        } else {
            throw new Exception('Unable to save enemy!');
        }

        if ($enemy->npc_id > 0) {
            $enemy->load(['npc']);
        }

        if (Auth::check()) {
            broadcast(new ModelChangedEvent($enemy->floor->dungeon, Auth::getUser(), $enemy));
        }

        return $enemy;
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Enemy $enemy
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    function setRaidMarker(Request $request, DungeonRoute $dungeonroute, Enemy $enemy)
    {
        $this->authorize('edit', $dungeonroute);

        try {
            $raidMarkerName = $request->get('raid_marker_name', '');

            // Delete existing enemy raid marker
            DungeonRouteEnemyRaidMarker::where('enemy_id', $enemy->id)->where('dungeon_route_id', $dungeonroute->id)->delete();

            // Create a new one, if the user didn't just want to clear it
            if ($raidMarkerName !== null && !empty($raidMarkerName)) {
                $raidMarker = new DungeonRouteEnemyRaidMarker();
                $raidMarker->dungeon_route_id = $dungeonroute->id;
                $raidMarker->raid_marker_id = RaidMarker::where('name', $raidMarkerName)->first()->id;
                $raidMarker->enemy_id = $enemy->id;
                $raidMarker->save();

                $result = ['name' => $raidMarkerName];
            } else {
                $result = ['name' => ''];
            }

        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Enemy $enemy
     * @return array|ResponseFactory|Response
     */
    function delete(Request $request, Enemy $enemy)
    {
        try {
            if ($enemy->delete()) {
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemy->floor->dungeon, Auth::getUser(), $enemy));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemy, null);
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
