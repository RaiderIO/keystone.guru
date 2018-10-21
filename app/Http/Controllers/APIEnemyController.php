<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyInfestedVote;
use App\Models\Npc;
use App\Models\RaidMarker;
use Illuminate\Http\Request;
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
        DB::enableQueryLog();

        // If dungeon route was set, fetch the markers as well
        if ($dungeonRoutePublicKey !== null) {
            try {
                $dungeonRoute = $this->_getDungeonRouteFromPublicKey($dungeonRoutePublicKey, false);
                // Eloquent wasn't working with me, raw SQL it is
                /** @var array $result */
                $result = DB::select('
                    select `enemies`.*, `raid_markers`.`name` as `raid_marker_name`,
                            CAST(SUM(if(`enemy_infested_votes`.`vote` = 1, 1, 0)) as SIGNED) as infested_yes_votes,
                            CAST(SUM(if(`enemy_infested_votes`.`vote` = 0, 1, 0)) as SIGNED) as infested_no_votes,
                            if(`enemy_infested_votes`.`user_id` = :userId, `enemy_infested_votes`.`vote`, null) as infested_user_vote
                    from `enemies`
                           left join `dungeon_route_enemy_raid_markers`
                             on `dungeon_route_enemy_raid_markers`.`enemy_id` = `enemies`.`id` and
                                `dungeon_route_enemy_raid_markers`.`dungeon_route_id` = :routeId
                           left join `raid_markers` on `dungeon_route_enemy_raid_markers`.`raid_marker_id` = `raid_markers`.`id`
                           left join `enemy_infested_votes` on `enemies`.`id` = `enemy_infested_votes`.`enemy_id`
                    where `enemies`.`floor_id` = :floorId
                    group by `enemies`.`id`;
                ', [
                    'userId' => Auth::check() ? Auth::user()->id : -1,
                    'routeId' => $dungeonRoute->id,
                    'floorId' => $floorId
                ]);

                // After this $result will contain $npc_id but not the $npc object. Put that in manually here.
                $npcs = DB::table('npcs')->whereIn('id', array_unique(array_column($result, 'npc_id')))->get();

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
            $result = Enemy::where('floor_id', $floorId)->get();
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


    /**
     * @param Request $request
     * @param Enemy $enemy
     * @return array
     * @throws \Exception
     */
    function setInfested(Request $request, Enemy $enemy)
    {
        $vote = $request->get('vote', -1);

        $user = Auth::user();
        /** @var EnemyInfestedVote $infestedEnemyVote */
        $infestedEnemyVote = EnemyInfestedVote::firstOrNew(['enemy_id' => $enemy->id, 'user_id' => $user->id]);
        // If user wants to vote yes/no
        if ($vote === 0 || $vote === 1) {
            // If it's not 0, it's true (yes), otherwise false (no)
            $infestedEnemyVote->vote = intval($vote) !== 0;
            $infestedEnemyVote->save();
        } else if ($infestedEnemyVote->exists) {
            $infestedEnemyVote->delete();
        }

        // Re-load infested relations
        $enemy->unsetRelation('infestedvotes');

        // Return up-to-date state
        return ['is_infested' => $enemy->is_infested];
    }

//    /**
//     * @param Request $request
//     * @param Enemy $enemy
//     * @return array
//     * @throws \Exception
//     */
//    function rateDelete(Request $request, Enemy $enemy)
//    {
//        $user = Auth::user();
//
//        /** @var DungeonRouteRating $dungeonRouteRating */
//        $dungeonRouteRating = DungeonRouteRating::firstOrFail()
//            ->where('dungeon_route_id', $dungeonroute->id)
//            ->where('user_id', $user->id);
//        $dungeonRouteRating->delete();
//
//        $dungeonroute->unsetRelation('ratings');
//        return ['new_avg_rating' => $dungeonroute->getAvgRatingAttribute()];
//    }
}
