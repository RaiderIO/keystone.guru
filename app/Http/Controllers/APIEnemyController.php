<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\Enemy;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Npc;
use App\Models\RaidMarker;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;

class APIEnemyController extends Controller
{
    use PublicKeyDungeonRoute;

    function list(Request $request)
    {
        $floorId = $request->get('floor_id');
        $dungeonRoutePublicKey = $request->get('dungeonroute', null);

        // If dungeon route was set, fetch the markers as well
        if ($dungeonRoutePublicKey !== null) {
            try {
                $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
                $result = DB::table('enemies')
                    ->leftJoin('dungeon_route_enemy_raid_markers', function ($join) use ($dungeonRoute) {
                        /** @var $join JoinClause */
                        $join->on('dungeon_route_enemy_raid_markers.enemy_id', '=', 'enemies.id')
                            ->where('dungeon_route_enemy_raid_markers.dungeon_route_id', $dungeonRoute->id);
                    })
                    ->leftJoin('raid_markers', 'dungeon_route_enemy_raid_markers.raid_marker_id', '=', 'raid_markers.id')
                    ->where('enemies.floor_id', $floorId)
                    ->select('enemies.*', 'raid_markers.name as raid_marker_name')
                    ->get();

                // After this $result will contain $npc_id but not the $npc object. Put that in manually here.
                $npcs = DB::table('npcs')->whereIn('id', $result->pluck(['npc_id'])->unique())->get();

                foreach ($result as $enemy) {
                    $enemy->npc = $npcs->filter(function ($item) use ($enemy) {
                        return $enemy->npc_id === $item->id;
                    })->first();
                    unset($enemy->npc_id);
                }
            } catch (\Exception $ex) {
                $result = response('Not found', Http::NOT_FOUND);
            }
        } else {
//        DB::enableQueryLog();
            $result = Enemy::where('floor_id', '=', $floorId)->get();
//        dd(DB::getQueryLog());
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
        /** @var Enemy $enemy */
        $enemy = Enemy::findOrNew($request->get('id'));

        $enemy->enemy_pack_id = $request->get('enemy_pack_id');
        $npcId = $request->get('npc_id', -1);
        $enemy->npc_id = $npcId === null ? -1 : $npcId;
        $enemy->floor_id = $request->get('floor_id');
        $enemy->teeming = $request->get('teeming');
        $enemy->faction = $request->get('faction', 'any');
        $enemy->enemy_forces_override = $request->get('enemy_forces_override', -1);
        $enemy->lat = $request->get('lat');
        $enemy->lng = $request->get('lng');

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
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function setRaidMarker(Request $request)
    {
        $dungeonRoutePublicKey = $request->get('dungeonroute');
        try {
            $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey);
            $raidMarkerName = $request->get('raid_marker_name', '');
            $enemyId = $request->get('enemy_id', 0);

            // Delete existing enemy raid marker
            DungeonRouteEnemyRaidMarker::where('enemy_id', $enemyId)->where('dungeon_route_id', $dungeonRoute->id)->delete();

            // Create a new one, if the user didn't just want to clear it
            if ($raidMarkerName !== null && !empty($raidMarkerName)) {
                $raidMarker = new DungeonRouteEnemyRaidMarker();
                $raidMarker->dungeon_route_id = $dungeonRoute->id;
                $raidMarker->raid_marker_id = RaidMarker::where('name', $raidMarkerName)->first()->id;
                $raidMarker->enemy_id = $enemyId;
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
