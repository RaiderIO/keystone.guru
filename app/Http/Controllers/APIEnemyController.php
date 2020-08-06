<?php

namespace App\Http\Controllers;

use App\Events\ModelChangedEvent;
use App\Events\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\ListsEnemies;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\Npc;
use App\Models\RaidMarker;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APIEnemyController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;
    use ListsEnemies;

    function list(Request $request)
    {
        $showMdtEnemies = false;

        // Only admins are allowed to see this
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            // Only fetch it now
            $showMdtEnemies = intval($request->get('show_mdt_enemies', 0)) === 1;
        }

        $enemies = $this->listEnemies(
            $request->get('floorId'),
            $showMdtEnemies,
            $request->get('dungeonroute', null)
        );

        if ($enemies === false) {
            return response('Not found', Http::NOT_FOUND);
        } else {
            return $enemies;
        }
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    function store(Request $request)
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::findOrNew($request->get('id'));

        $enemy->enemy_pack_id = $request->get('enemy_pack_id');
        $npcId = $request->get('npc_id', -1);
        $enemy->npc_id = $npcId === null ? -1 : $npcId;
        // Only when set, otherwise default of -1
        $mdtId = $request->get('mdt_id', -1);
        $enemy->mdt_id = $mdtId === null ? -1 : $mdtId;
        $seasonalIndex = $request->get('seasonal_index');
        // don't use is_empty since 0 is valid
        $enemy->seasonal_index = $seasonalIndex === null || $seasonalIndex === '' ? null : $seasonalIndex;
        $enemy->floor_id = $request->get('floor_id');
        $enemy->teeming = $request->get('teeming');
        $enemy->faction = $request->get('faction', 'any');
        $enemy->enemy_forces_override = $request->get('enemy_forces_override', -1);
        $enemy->enemy_forces_override_teeming = $request->get('enemy_forces_override_teeming', -1);
        $enemy->lat = $request->get('lat');
        $enemy->lng = $request->get('lng');

        if (!$enemy->save()) {
            throw new Exception('Unable to save enemy!');
        }

        $result = ['id' => $enemy->id];

        if ($enemy->npc_id > 0) {
            $result['npc'] = Npc::findOrFail($enemy->npc_id);
        }

        if (Auth::check()) {
            broadcast(new ModelChangedEvent($enemy->floor->dungeon, Auth::getUser(), $enemy));
        }

        return $result;
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
            if( $enemy->delete() ){
                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemy->floor->dungeon, Auth::getUser(), $enemy));
                }
            }
            $result = response()->noContent();
        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
