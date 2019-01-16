<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Logic\MDT\IO\MDTDungeon;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyInfestedVote;
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
                select `enemies`.*, `raid_markers`.`name`                                     as `raid_marker_name`,
                       CAST(IFNULL(SUM(if(`vote` = 1, 1, 0) * `vote_weight`), 0) as SIGNED)   as infested_yes_votes,
                       CAST(IFNULL(SUM(if(`vote` = 0, 1, 0) * `vote_weight`), 0) as SIGNED)   as infested_no_votes,
                       if(`enemy_infested_votes`.`user_id` = :userId, `vote`, null)           as infested_user_vote
                from `enemies`
                       left join `dungeon_route_enemy_raid_markers`
                         on `dungeon_route_enemy_raid_markers`.`enemy_id` = `enemies`.`id` and
                            `dungeon_route_enemy_raid_markers`.`dungeon_route_id` = :routeId
                       left join `raid_markers` on `dungeon_route_enemy_raid_markers`.`raid_marker_id` = `raid_markers`.`id`
                       left join `enemy_infested_votes` on `enemies`.`id` = `enemy_infested_votes`.`enemy_id`
                                and `enemy_infested_votes`.affix_group_id = :affixGroupId
                                              and `enemy_infested_votes`.updated_at > :minTime
                where `enemies`.`floor_id` = :floorId
                group by `enemies`.`id`;
                ', $params = [
            'userId' => Auth::check() ? Auth::user()->id : -1,
            'routeId' => isset($dungeonRoute) ? $dungeonRoute->id : -1,
            'affixGroupId' => $region->getCurrentAffixGroup()->id,
            'minTime' => Carbon::now()->subMonth()->format('Y-m-d H:i:s'),
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
            $enemy->is_infested = ($enemy->infested_yes_votes - $enemy->infested_no_votes) >= config('keystoneguru.infested_user_vote_threshold');
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


    /**
     * @param Request $request
     * @param Enemy $enemy
     * @return array
     * @throws \Exception
     */
    function setInfested(Request $request, Enemy $enemy)
    {
        $vote = intval($request->get('vote', -1));

        $user = Auth::user();

        if ($user->game_server_region_id > 0) {
            $currentAffixId = $user->gameserverregion->getCurrentAffixGroup()->id;

            /** @var EnemyInfestedVote $infestedEnemyVote */
            $infestedEnemyVote = EnemyInfestedVote::firstOrNew([
                'enemy_id' => $enemy->id,
                'user_id' => $user->id,
                'affix_group_id' => $currentAffixId
            ]);

            // If user wants to vote yes/no
            if ($vote === 0 || $vote === 1) {
                $infestedEnemyVote->affix_group_id = $currentAffixId;
                // If it's not 0, it's true (yes), otherwise false (no)
                $infestedEnemyVote->vote = $vote;
                // Admins must be able to have some weight to their votes. They're not to abuse the system. However,
                // I don't want an 'admin is king' so an admin CAN be overruled by users
                if ($user->hasRole('admin')) {
                    $infestedEnemyVote->vote_weight = config('keystoneguru.infested_user_vote_threshold');
                }
                $infestedEnemyVote->save();
            } // If vote was an invalid value but a vote existed, get rid of it
            else if ($infestedEnemyVote->exists) {
                $infestedEnemyVote->delete();
            }

            // Re-load infested relations
            $enemy->unsetRelation('infestedvotes');
            $enemy->unsetRelation('thisweeksinfestedvotes');

            return [
                'infested_yes_votes' => $enemy->getInfestedYesVotesCount(),
                'infested_no_votes' => $enemy->getInfestedNoVotesCount(),
                'infested_user_vote' => $enemy->getUserInfestedVoteAttribute(),
                'is_infested' => $enemy->is_infested
            ];
        } else {
            throw new \Exception(__('Region not set. Please visit your profile and set your region before voting on Infested enemies.'));
        }
    }
}
