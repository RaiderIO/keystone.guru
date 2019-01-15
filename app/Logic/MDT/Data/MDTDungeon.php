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
     * @param $floors Collection The floors that you want to get the clones for.
     * @return Enemy[]
     */
    public function getClonesAsEnemies($floors)
    {
        // Ensure floors is a collection
        if (!($floors instanceof Collection)) {
            $floors = new Collection($floors);
        }

        $mdtNpcs = $this->_getMDTNPCs();

        // Find the enemy in a list of enemies
        $clones = [];
        foreach ($mdtNpcs as $mdtNpcIndex => $mdtNpc) {
            foreach ($mdtNpc['clones'] as $mdtId => $clone) {
                //Only clones that are on the same floor
                foreach ($floors as $floor) {
                    if ((int)$clone['sublevel'] === $floor->index) {
                        // Set some additional props that come in handy when converting to an enemy
                        $clone['mdtNpcIndex'] = (int)$mdtNpcIndex;
                        $clone['npcId'] = (int)$mdtNpc['id'];
                        $clone['mdtId'] = (int)$mdtId;
                        $clones[] = $clone;
                    }
                }
            }
        }

        // We now know a list of clones that we want to display, convert those clones to TEMP enemies
        $enemies = [];
        foreach ($floors as $floor) {
            /** @var Collection $npcs */
            $npcs = Npc::where('dungeon_id', $floor->dungeon->id)->get();
            foreach ($clones as $npcId => $clone) {
                $enemy = new Enemy();
                // Dummy so we can ID them later on
                $enemy->is_mdt = true;
                $enemy->floor_id = $floor->id;
                $enemy->enemy_pack_id = -1;
                $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                $enemy->npc_id = (int)$clone['npcId'];
                $enemy->mdt_id = (int)$clone['mdtId'];
                $enemy->enemy_id = -1;
                $enemy->is_infested = false;
                $enemy->teeming = isset($clone['teeming']) && $clone['teeming'] ? 'visible' : null;
                $enemy->faction = isset($clone['faction']) ? ($clone['faction'] === 1 ? 'alliance' : 'horde') : 'any';
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

        return $enemies;
    }

    /**
     * Get all enemies of this dungeon (Keystone.guru style).
     */
    public function getEnemies()
    {

    }
}