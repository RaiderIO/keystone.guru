<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 18-2-2019
 * Time: 15:46
 */

namespace App\Http\Controllers\Traits;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\NpcClass;
use App\Models\NpcType;
use Error;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ListsEnemies
{

    /**
     * Lists all enemies for a specific floor.
     *
     * @param $dungeonId
     * @param bool $showMdtEnemies
     * @param string|null $publicKey
     * @return array|bool
     */
    function listEnemies($dungeonId, $showMdtEnemies = false, $publicKey = null)
    {
        $dungeonRoute = null;
        // If dungeon route was set, fetch the markers as well
        if ($publicKey !== null) {
            try {
                $dungeonRoute = $this->_getDungeonRouteFromPublicKey($publicKey, false);
            } catch (Exception $ex) {
                return false;
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
                       left join `floors` on enemies.floor_id = floors.id
                where `floors`.dungeon_id = :dungeonId
                group by `enemies`.`id`;
                ', $params = [
            'routeId'   => isset($dungeonRoute) ? $dungeonRoute->id : -1,
            'dungeonId' => $dungeonId
        ]);

        // After this $result will contain $npc_id but not the $npc object. Put that in manually here.
        $npcs = DB::table('npcs')->whereIn('id', array_unique(array_column($result, 'npc_id')))->get();

        /** @var Collection $npcTypes */
        $npcTypes = NpcType::all();
        /** @var Collection $npcClasses */
        $npcClasses = NpcClass::all();

        // Only if we should show MDT enemies
        $filteredEnemies = [];
        if ($showMdtEnemies) {
            try {
                $dungeon = Dungeon::findOrFail($dungeonId);
                $mdtEnemies = (new MDTDungeon($dungeon->name))->getClonesAsEnemies($dungeon->floors);

                foreach ($mdtEnemies as $mdtEnemy) {
                    // Skip Emissaries (Season 3), season is over
                    if (!in_array($mdtEnemy->npc_id, [155432, 155433, 155434])) {
                        $filteredEnemies[] = $mdtEnemy;
                    }
                }

            } // Thrown when Lua hasn't been configured
            catch (Error $ex) {
                return false;
            }
        }

        // Post process enemies
        foreach ($result as $enemy) {
            $enemy->npc = $npcs->filter(function ($item) use ($enemy)
            {
                return $enemy->npc_id === $item->id;
            })->first();

            if ($enemy->npc !== null) {
                $enemy->npc->type = $npcTypes->get($enemy->npc->npc_type_id - 1);// $npcTypes->get(rand(0, 9));//
                $enemy->npc->class = $npcClasses->get($enemy->npc->npc_class_id - 1);
            }

            // Match an enemy with an MDT enemy so that the MDT enemy knows which enemy it's coupled with (vice versa is already known)
            foreach ($filteredEnemies as $mdtEnemy) {
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

        return ['enemies' => collect($result), 'mdt_enemies' => collect($filteredEnemies)];
    }
}