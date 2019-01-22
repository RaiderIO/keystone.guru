<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 06/01/2019
 * Time: 18:10
 */

namespace App\Logic\MDT\Data;


use App\Logic\MDT\Conversion;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\Npc;
use Illuminate\Support\Collection;

/**
 * Class ImportString. This file was created as a sort of copy of https://github.com/nnogga/MethodDungeonTools/blob/master/Transmission.lua
 * All rights belong to their respective owners, I did write this but I did not make this up.  I merely translated the LUA
 * to PHP to allow for importing of the exported strings.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class MDTDungeon
{

    /** @var string The Dungeon's name (Keystone.guru style). Can be converted using self::$dungeonMapping */
    private $_dungeonName;


    function __construct($dungeonName)
    {
        $this->_dungeonName = $dungeonName;
    }

    /**
     * Get a list of NPCs
     */
    private function _getMDTNPCs()
    {
        $lua = new \Lua();
        $lua->eval(
            'local MethodDungeonTools = {}
            MethodDungeonTools.dungeonTotalCount = {}
            MethodDungeonTools.mapPOIs = {}
            MethodDungeonTools.dungeonEnemies = {}
            MethodDungeonTools.scaleMultiplier = {}
            ' .
            // Some files require LibStub
            file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')) .
            file_get_contents(
                base_path('vendor/nnogga/MethodDungeonTools/BattleForAzeroth/' . Conversion::getMDTDungeonName($this->_dungeonName) . '.lua')
            ) .
            // Insert dummy function to get what we need
            '
            function GetDungeonEnemies() 
                return MethodDungeonTools.dungeonEnemies[dungeonIndex]
            end
        ');
        return $lua->call('GetDungeonEnemies');
    }

    /**
     * Get all clones of a specific NPC.
     * @param $npcId int WoW's NPC id.
     * @return array The enemy as an array.
     */
    private function _getMDTEnemy($npcId)
    {
        $enemies = $this->_getMDTNPCs();

        $result = null;
        // Find the enemy in a list of enemies
        foreach ($enemies as $enemy) {
            // Id is classed as a double, some lua -> php conversion issue/choice.
            if ((int)$enemy->id === $npcId) {
                $result = $enemy;
                break;
            }
        }

        return $result;
    }


    /**
     * Get all clones of this dungeon in the format of enemies (Keystone.guru style).
     * @param $floors Floor|Collection The floors that you want to get the clones for.
     * @return Enemy[]
     */
    public function getClonesAsEnemies($floors)
    {
        // Ensure floors is a collection
        if (!($floors instanceof Collection)) {
            $floors = [$floors];
        }

        $mdtNpcs = $this->_getMDTNPCs();

        // NPC_ID => list of clones
        $npcClones = [];
        // Find the enemy in a list of enemies
        foreach ($mdtNpcs as $mdtNpcIndex => $mdtNpc) {
            $cloneCount = 0;
            foreach ($mdtNpc['clones'] as $mdtCloneIndex => $clone) {
                //Only clones that are on the same floor
                foreach ($floors as $floor) {
                    if ((int)$clone['sublevel'] === $floor->index) {
                        // Set some additional props that come in handy when converting to an enemy
                        $clone['mdtNpcIndex'] = (int)$mdtNpcIndex;
                        // Group ID
                        $clone['g'] = isset($clone['g']) ? $clone['g'] : -1;

                        $npcId = (int)$mdtNpc['id'];
                        // Make sure array is set
                        if (!isset($npcClones[$npcId])) {
                            $npcClones[$npcId] = [];
                        }
                        // Gets funky here. There's instances where MDT has defined an NPC with the same NPC_ID twice
                        // This fucks with the assignment below this if, because it'll overwrite the NPCs there.
                        // We don't want this; instead append it at the end of the current array at the proper index
                        // We calculate that at the hand of the current index in the second array ($cloneCount).
                        if (isset($npcClones[$npcId][$mdtCloneIndex])) {
                            $mdtCloneIndex += (count($npcClones[$npcId]) - $cloneCount);
                        }
                        // Append this clone to the array
                        $npcClones[$npcId][$mdtCloneIndex] = $clone;
                    }
                }

                $cloneCount++;
            }
        }

        // We now know a list of clones that we want to display, convert those clones to TEMP enemies
        $enemies = [];
        foreach ($floors as $floor) {
            /** @var Collection $npcs */
            $npcs = Npc::where('dungeon_id', $floor->dungeon->id)->get();
            foreach ($npcClones as $npcId => $clones) {
                foreach ($clones as $mdtCloneIndex => $clone) {
                    $enemy = new Enemy();
                    // Dummy so we can ID them later on
                    $enemy->is_mdt = true;
                    $enemy->floor_id = $floor->id;
                    $enemy->enemy_pack_id = (int)$clone['g'];
                    $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                    $enemy->npc_id = $npcId;
                    // All MDT_IDs are 1-indexed, because LUA
                    $enemy->mdt_id = $mdtCloneIndex;
                    $enemy->enemy_id = -1;
                    $enemy->is_infested = false;
                    $enemy->teeming = isset($clone['teeming']) && $clone['teeming'] ? 'visible' : null;
                    $enemy->faction = isset($clone['faction']) ? ((int)$clone['faction'] === 1 ? 'horde' : 'alliance') : 'any';
                    $enemy->enemy_forces_override = -1;

                    $latLng = Conversion::convertMDTCoordinateToLatLng($clone);
                    $enemy->lat = $latLng['lat'];
                    $enemy->lng = $latLng['lng'];

                    $enemy->npc = $npcs->firstWhere('id', $enemy->npc_id);

                    // Some properties which are dynamic on a normal enemy but static here
                    $enemy->raid_marker_name = null;
                    $enemy->infested_yes_votes = 0;
                    $enemy->infested_no_votes = 0;
                    $enemy->infested_user_vote = null;

                    $enemies[] = $enemy;
                }
            }
        }

        return $enemies;
    }

    /**
     * Get all enemies of this dungeon (Keystone.guru style).
     */
    public function getEnemies()
    {

    }
}