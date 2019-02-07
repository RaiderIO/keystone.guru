<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Logic\MDT\IO\MDTDungeon;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\GameServerRegion;
use App\Models\Npc;
use App\Models\RaidMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;

class APIEnemyController extends Controller
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute', null);
        $showMdtEnemies = false;

        // Only admins are allowed to see this
        if (Auth::check()) {
            if (Auth::user()->hasRole('admin')) {
                // Only fetch it now
                $showMdtEnemies = intval($request->get('show_mdt_enemies', 0)) === 1;
            }
        }

        $dungeonRoute = null;
        // If dungeon route was set, fetch the markers as well
        if ($dungeonRoutePublicKey !== null) {
            try {
                $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
            } catch (\Exception $ex) {
                return response('Not found', Http::NOT_FOUND);
            }
        }

        $region = GameServerRegion::getUserOrDefaultRegion();

        // Eloquent wasn't working with me, raw SQL it is
        /** @var array $result */
        $result = DB::select($query = '
                select `enemies`.*, `raid_markers`.`name`                                     as `raid_marker_name`
                from `enemies`
                       left join `dungeon_route_enemy_raid_markers`
                         on `dungeon_route_enemy_raid_markers`.`enemy_id` = `enemies`.`id` and
                            `dungeon_route_enemy_raid_markers`.`dungeon_route_id` = :routeId
                       left join `raid_markers` on `dungeon_route_enemy_raid_markers`.`raid_marker_id` = `raid_markers`.`id`
                where `enemies`.`floor_id` = :floorId
                group by `enemies`.`id`;
                ', $params = [
            'routeId' => isset($dungeonRoute) ? $dungeonRoute->id : -1,
            'floorId' => $floorId
        ]);

        // After this $result will contain $npc_id but not the $npc object. Put that in manually here.
        $npcs = DB::table('npcs')->whereIn('id', array_unique(array_column($result, 'npc_id')))->get();

        // Only if we should show MDT enemies
        $mdtEnemies = [];
        if ($showMdtEnemies) {
            /** @var Floor $floor */
            $floor = Floor::find($floorId);

            $mdtEnemies = (new \App\Logic\MDT\Data\MDTDungeon($floor->dungeon->name))->getClonesAsEnemies($floor);
        }

        // Post process enemies
        foreach ($result as $enemy) {
            $enemy->npc = $npcs->filter(function ($item) use ($enemy) {
                return $enemy->npc_id === $item->id;
            })->first();

            // Match an enemy with an MDT enemy so that the MDT enemy knows which enemy it's coupled with (vice versa is already known)
            foreach ($mdtEnemies as $mdtEnemy) {
                // Match them
                if ($mdtEnemy->mdt_id === $enemy->mdt_id && $mdtEnemy->npc_id === $enemy->npc_id) {
                    // Match found, assign and quit
                    $mdtEnemy->enemy_id = $enemy->id;
                    break;
                }
            }

            // Can be found in the npc object
            unset($enemy->npc_id);
        }

        return ['enemies' => $result, 'mdt_enemies' => $mdtEnemies];
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
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
        $enemy->floor_id = $request->get('floor_id');
        $enemy->teeming = $request->get('teeming');
        $enemy->faction = $request->get('faction', 'any');
        $enemy->enemy_forces_override = $request->get('enemy_forces_override', -1);
        $enemy->lat = $request->get('lat');
        $enemy->lng = $request->get('lng');

        // Find out of there is a duplicate
        if (!$enemy->exists) {
            $this->checkForDuplicate($enemy);
        }

        if (!$enemy->save()) {
            throw new \Exception("Unable to save enemy!");
        }

        $result = ['id' => $enemy->id];

        if ($enemy->npc_id > 0) {
            $result['npc'] = Npc::findOrFail($enemy->npc_id);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Enemy $enemy
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function setRaidMarker(Request $request, Enemy $enemy)
    {
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey);
            $raidMarkerName = $request->get('raid_marker_name', '');

            // Delete existing enemy raid marker
            DungeonRouteEnemyRaidMarker::where('enemy_id', $enemy->id)->where('dungeon_route_id', $dungeonRoute->id)->delete();

            // Create a new one, if the user didn't just want to clear it
            if ($raidMarkerName !== null && !empty($raidMarkerName)) {
                $raidMarker = new DungeonRouteEnemyRaidMarker();
                $raidMarker->dungeon_route_id = $dungeonRoute->id;
                $raidMarker->raid_marker_id = RaidMarker::where('name', $raidMarkerName)->first()->id;
                $raidMarker->enemy_id = $enemy->id;
                $raidMarker->save();

                $result = ['name' => $raidMarkerName];
            } else {
                $result = ['name' => ''];
            }

        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function delete(Request $request)
    {
        try {
            /** @var Enemy $enemy */
            $enemy = Enemy::findOrFail($request->get('id'));

            $enemy->delete();
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }
}
