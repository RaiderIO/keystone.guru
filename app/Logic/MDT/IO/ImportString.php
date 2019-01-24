<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT\IO;


use App\Logic\MDT\Conversion;
use App\Models\AffixGroup;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ImportString
{
    /**
     * @var string The MDT encoded string that's currently staged for conversion to a DungeonRoute.
     */
    private $_encodedString;

    /**
     * @var DungeonRoute The route that's currently staged for conversion to an encoded string.
     */
    private $_dungeonRoute;


    function __construct()
    {

    }

    /**
     * Gets a Lua instance and load all the required files in it.
     * @return \Lua
     */
    private function _getLua()
    {
        $lua = new \Lua();

        // Load libraries (yeah can do this with ->library function as well)
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibCompress.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/AceSerializer.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/MDTTransmission.lua')));

        return $lua;
    }

    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     * @return string
     */
    public function getEncodedString()
    {
        // @TODO This needs a dungeon route to "table" conversion first
        $lua = $this->_getLua();
        $encoded = $lua->call("TableToString", [$this->_dungeonRoute, true]);
        return $encoded;
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     * @param $save boolean True to save the route and all associated models, false to not save & couple.
     * @return DungeonRoute|bool DungeonRoute if the route could be constructed, false if the string was invalid.
     * @throws \Exception
     */
    public function getDungeonRoute($save = false)
    {
        // @TODO This needs a "table" to dungeon route conversion first
        $lua = $this->_getLua();
        // Import it to a table
        $decoded = $lua->call("StringToTable", [$this->_encodedString, true]);
        // Check if it's valid
        $isValid = $lua->call("ValidateImportPreset", [$decoded]);

        $dungeonRoute = false;
        if ($isValid) {
            // Create a dungeon route
            $dungeonRoute = new DungeonRoute();
            $dungeonRoute->author_id = Auth::id();
            $dungeonRoute->dungeon_id = Conversion::convertMDTDungeonID($decoded['value']['currentDungeonIdx']);
            $dungeonRoute->faction_id = 1; // Default faction
            $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonRoute->teeming = boolval($decoded['value']['teeming']);
            $dungeonRoute->title = $decoded['text'];
            $dungeonRoute->difficulty = 'Casual';

            if ($save) {
                // Preemptively save the route
                $dungeonRoute->save();
            } else {
                $dungeonRoute->killzones = new Collection();
            }

            // Set the affix for this route
            $affixGroup = Conversion::convertWeekToAffixGroup($decoded['week']);
            if ($affixGroup instanceof AffixGroup) {
                if ($save) {
                    // Something we can save to the database
                    $dungeonAffixGroup = new DungeonRouteAffixGroup();
                    $dungeonAffixGroup->affix_group_id = $affixGroup->id;
                    $dungeonAffixGroup->dungeon_route_id = $dungeonRoute->id;
                    $dungeonAffixGroup->save();
                } else {
                    // Something we can just return and have the user read
                    $dungeonRoute->affixes = new Collection();
                    $dungeonRoute->affixes->push($affixGroup);
                }
            }

            // Create killzones and attach enemies
            $floors = $dungeonRoute->dungeon->floors;
            $enemies = Enemy::whereIn('floor_id', $floors->pluck(['id']))->get();

            // Fetch all enemies of this
            $mdtEnemies = (new \App\Logic\MDT\Data\MDTDungeon($dungeonRoute->dungeon->name))->getClonesAsEnemies($floors);

            // For each pull the user created
            foreach ($decoded['value']['pulls'] as $pullIndex => $pull) {
                // Create a killzone
                $killZone = new KillZone();
                if ($save) {
                    $killZone->dungeon_route_id = $dungeonRoute->id;
                    // Save it so we have an ID that we can use later on
                    $killZone->save();
                } else {
                    $killZone->enemies = new Collection();
                }

                // Init some variables
                $totalEnemiesKilled = 0;
                $kzLat = 0;
                $kzLng = 0;
                $floorId = -1;

                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $npcIndex => $mdtClones) {
                    // Only if filled
                    $enemyCount = count($mdtClones);
                    foreach ($mdtClones as $index => $cloneIndex) {
                        // This comes in through as a double, cast to int
                        $cloneIndex = (int)$cloneIndex;

                        // Find the matching enemy of the clones
                        /** @var Enemy $mdtEnemy */
                        $mdtEnemy = null;
                        foreach ($mdtEnemies as $mdtEnemyCandidate) {
                            // NPC and clone index make for unique ID
                            if ($mdtEnemyCandidate->mdt_npc_index === $npcIndex && $mdtEnemyCandidate->mdt_id === $cloneIndex) {
                                // Found it
                                $mdtEnemy = $mdtEnemyCandidate;
                                break;
                            }
                        }

                        if ($mdtEnemy === null) {
                            throw new \Exception("Unable to find MDT enemy for index {$cloneIndex}!");
                        }

                        // We now know the MDT enemy that the user was trying to import. However, we need to know
                        // our own enemy. Thus, try to find the enemy in our list which has the same npc_id and mdt_id.
                        /** @var Enemy $enemy */
                        $enemy = null;
                        foreach ($enemies as $enemyCandidate) {
                            if ($enemyCandidate->mdt_id === $mdtEnemy->mdt_id && $enemyCandidate->npc_id === $mdtEnemy->npc_id) {
                                $enemy = $enemyCandidate;
                                break;
                            }
                        }

                        if ($enemy === null) {
                            throw new \Exception("Unable to find enemy for mdt_id {$mdtEnemy->mdt_id}, npc_id {$mdtEnemy->npc_id}!");
                        }

                        $kzLat += $enemy->lat;
                        $kzLng += $enemy->lng;

                        // Couple the KillZoneEnemy to its KillZone
                        if ($save) {
                            $kzEnemy = new KillZoneEnemy();
                            $kzEnemy->enemy_id = $enemy->id;
                            $kzEnemy->kill_zone_id = $killZone->id;
                            $kzEnemy->save();
                        } else {
                            $killZone->enemies->push($enemy);
                        }

                        // Should be the same floor_id all the time, but we need it anyways
                        $floorId = $enemy->floor_id;
                    }

                    $totalEnemiesKilled += $enemyCount;
                }

                // KillZones at the average position of all killed enemies
                $killZone->floor_id = $floorId;
                $killZone->lat = $kzLat / $totalEnemiesKilled;
                $killZone->lng = $kzLng / $totalEnemiesKilled;


                if ($save) {
                    $killZone->save();
                } else {
                    $dungeonRoute->killzones->push($killZone);
                }
            }
        }

        return $dungeonRoute;
    }

    /**
     * Sets the encoded string to be staged for translation to a DungeonRoute.
     *
     * @param $encodedString string The MDT encoded string.
     * @return $this
     */
    public function setEncodedString($encodedString)
    {
        $this->_encodedString = $encodedString;

        return $this;
    }

    /**
     * Sets a dungeon route to be staged for encoding to an encoded string.
     *
     * @param $dungeonRoute DungeonRoute
     * @return $this Returns self to allow for chaining.
     */
    public function setDungeonRoute($dungeonRoute)
    {
        $this->_dungeonRoute = $dungeonRoute;

        return $this;
    }
}