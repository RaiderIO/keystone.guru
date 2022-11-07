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
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcClass;
use App\Models\NpcType;
use Error;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

trait ListsEnemies
{

    /**
     * Lists all enemies for a specific floor.
     *
     * @param MappingVersion $mappingVersion
     * @param bool $showMdtEnemies
     * @return array|bool
     * @throws InvalidArgumentException
     */
    function listEnemies(MappingVersion $mappingVersion, bool $showMdtEnemies = false)
    {
        /** @var Collection|Enemy[] $enemies */
        $enemies = Enemy::selectRaw('enemies.*')
            ->join('floors', 'enemies.floor_id', '=', 'floors.id')
            ->where('floors.dungeon_id', $mappingVersion->dungeon_id)
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->get();

        // After this $result will contain $npc_id but not the $npc object. Put that in manually here.
        /** @var Npc[]|Collection $npcs */
        $npcs = Npc::whereIn('id', $enemies->pluck('npc_id')->unique()->toArray())->get();

        /** @var Collection $npcTypes */
        $npcTypes = NpcType::all();
        /** @var Collection $npcClasses */
        $npcClasses = NpcClass::all();

        // Only if we should show MDT enemies
        $mdtEnemies = collect();
        if ($showMdtEnemies) {
            try {
                $dungeon    = Dungeon::findOrFail($mappingVersion->dungeon_id);
                $mdtEnemies = (new MDTDungeon($dungeon->key))->getClonesAsEnemies($dungeon->floors);

                $mdtEnemies = $mdtEnemies->filter(function (Enemy $mdtEnemy) {
                    return !in_array($mdtEnemy->npc_id, [155432, 155433, 155434]);
                });

            } // Thrown when Lua hasn't been configured
            catch (Error $ex) {
                return false;
            }
        }

        // Post process enemies
        foreach ($enemies as $enemy) {
            $enemy->npc = $npcs->first(function ($item) use ($enemy) {
                return $enemy->npc_id === $item->id;
            });

            if ($enemy->npc !== null) {
                $enemy->npc->type  = $npcTypes->get($enemy->npc->npc_type_id - 1);// $npcTypes->get(rand(0, 9));//
                $enemy->npc->class = $npcClasses->get($enemy->npc->npc_class_id - 1);
            }

            // Match an enemy with an MDT enemy so that the MDT enemy knows which enemy it's coupled with (vice versa is already known)
            foreach ($mdtEnemies as $mdtEnemy) {
                // Match them
                if ($mdtEnemy->floor_id === $enemy->floor_id &&
                    $mdtEnemy->mdt_id === $enemy->mdt_id &&
                    $mdtEnemy->npc_id === $enemy->getMdtNpcId()) {
                    // Match found, assign and quit
                    $mdtEnemy->mapping_version_id = $enemy->mapping_version_id;
                    $mdtEnemy->enemy_id           = $enemy->id;
                    break;
                }
            }

            // Can be found in the npc object
            unset($enemy->npc_id);
        }

        return ['enemies' => $enemies->toArray(), 'enemiesMdt' => $mdtEnemies->toArray()];
    }
}
