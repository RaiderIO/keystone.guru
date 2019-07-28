<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 15:46
 */

namespace App\Http\Controllers\Traits;

use App\Models\Floor;
use App\Models\NpcClass;
use App\Models\NpcType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;

trait ListsEnemies
{

    /**
     * Lists all enemies for a specific floor.
     *
     * @param $floorId
     * @param bool $showMdtEnemies
     * @param string|null $publicKey
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    function listEnemies($floorId, $showMdtEnemies = false, $publicKey = null)
    {
        $dungeonRoute = null;
        // If dungeon route was set, fetch the markers as well
        if ($publicKey !== null) {
            try {
                $dungeonRoute = $this->_getDungeonRouteFromPublicKey($publicKey, false);
            } catch (\Exception $ex) {
                return response('Not found', Http::NOT_FOUND);
            }
        }

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

        /** @var Collection $npcTypes */
        $npcTypes = NpcType::all();
        /** @var Collection $npcClasses */
        $npcClasses = NpcClass::all();

        // Only if we should show MDT enemies
        $mdtEnemies = [];
        if ($showMdtEnemies) {
            /** @var Floor $floor */
            $floor = Floor::find($floorId);

            try {
                $mdtEnemies = (new \App\Logic\MDT\Data\MDTDungeon($floor->dungeon->name))->getClonesAsEnemies($floor);
            } // Thrown when Lua hasn't been configured
            catch (\Error $ex) {

            }
        }

        // Post process enemies
        foreach ($result as $enemy) {
            $enemy->npc = $npcs->filter(function ($item) use ($enemy) {
                return $enemy->npc_id === $item->id;
            })->first();

            $enemy->npc->type = $npcTypes->get($enemy->npc->npc_type_id - 1);// $npcTypes->get(rand(0, 9));//
            $enemy->npc->class = $npcClasses->random();

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
}