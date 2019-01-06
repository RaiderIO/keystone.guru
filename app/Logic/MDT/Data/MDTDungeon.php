<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 06/01/2019
 * Time: 18:10
 */

namespace App\Logic\MDT\Data;


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
    private static $dungeonNameMapping = [
        'Atal\'Dazar' => 'AtalDazar',
        'Freehold' => 'Freehold',
        'Kings\' Rest' => 'KingsRest',
        'Shrine of the Storm' => 'ShrineoftheStorm',
        'Siege of Boralus' => 'SiegeofBoralus',
        'Temple of Sethraliss' => 'TempleofSethraliss',
        'The MOTHERLODE!!' => 'TheMotherlode',
        'The Underrot' => 'TheUnderrot',
        'Tol Dagor' => 'TolDagor',
        'Waycrest Manor' => 'WaycrestManor'
    ];

    /** @var string The Dungeon's name (Keystone.guru style). Can be converted using self::$dungeonMapping */
    private $_dungeonName;


    function __construct($dungeonName)
    {
        assert(array_key_exists($dungeonName, self::$dungeonNameMapping));
        $this->_dungeonName = $dungeonName;
    }

    /**
     * @return mixed Gets the MDT version of a dungeon name.
     */
    private function _getMDTDungeonName()
    {
        return self::$dungeonNameMapping[$this->_dungeonName];
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
                base_path('vendor/nnogga/MethodDungeonTools/BattleForAzeroth/' . $this->_getMDTDungeonName() . '.lua')
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
     * @param $floor Floor The floor you're looking for.
     * @return array
     */
    public function getClonesAsEnemies($floor)
    {
        $mdtNpcs = $this->_getMDTNPCs();

        // Find the enemy in a list of enemies
        $clones = [];
        foreach ($mdtNpcs as $mdtNpc) {
            foreach ($mdtNpc['clones'] as $mdtId => $clone) {
                //Only clones that are on the same floor
                if ((int)$clone['sublevel'] === $floor->index) {
                    // Set some additional props that come in handy when converting to an enemy
                    $clone['npcId'] = (int)$mdtNpc['id'];
                    $clone['mdtId'] = (int)$mdtId;
                    $clones[] = $clone;
                }
            }
        }

        // We now know a list of clones that we want to display, convert those clones to TEMP enemies
        $enemies = [];
        /** @var Collection $npcs */
        $npcs = Npc::where('dungeon_id', $floor->dungeon->id)->get();
        foreach ($clones as $npcId => $clone) {
            $enemy = new Enemy();
            // Dummy so we can ID them later on
            $enemy->is_mdt = true;
            $enemy->floor_id = $floor->id;
            $enemy->enemy_pack_id = -1;
            $enemy->npc_id = (int)$clone['npcId'];
            $enemy->mdt_id = (int)$clone['mdtId'];
            $enemy->is_infested = false;
            $enemy->teeming = isset($clone['teeming']) && $clone['teeming'] ? 'visible' : null;
            $enemy->faction = isset($clone['faction']) ? ($clone['faction'] === 1 ? 'alliance' : 'horde') : 'any';
            $enemy->enemy_forces_override = -1;
            // This seems to match my coordinate system for about 99%. Sometimes it's a bit off but I can work around that.
            $enemy->lat = ($clone['y'] / 2.2);
            $enemy->lng = ($clone['x'] / 2.2);
            $enemy->npc = $npcs->firstWhere('id', $enemy->npc_id);

            // Some properties which are dynamic on a normal enemy but static here
            $enemy->raid_marker_name = null;
            $enemy->infested_yes_votes = 0;
            $enemy->infested_no_votes = 0;
            $enemy->infested_user_vote = null;

            $enemies[] = $enemy;
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