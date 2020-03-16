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
use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\Polyline;
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
     * Parse the $decoded['value']['pulls'] value and put it in the $dungeonRoute object, optionally $save'ing the
     * values to the database.
     * @param $warnings Collection A Collection of Warnings that this parsing may produce.
     * @param $decoded array
     * @param $dungeonRoute DungeonRoute
     * @param $save boolean
     * @throws \Exception
     */
    private function _parseValuePulls($warnings, $decoded, $dungeonRoute, $save)
    {
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

            try {
                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $pullKey => $pullValue) {
                    // Numeric means it's an index of the dungeon's NPCs
                    if (is_numeric($pullKey)) {
                        $npcIndex = (int)$pullKey;
                        $mdtClones = $pullValue;
                        // Only if filled
                        $enemyCount = count($mdtClones);
                        foreach ($mdtClones as $index => $cloneIndex) {
                            // This comes in through as a double, cast to int
                            $cloneIndex = (int)$cloneIndex;

                            // Find the matching enemy of the clones
                            /** @var Enemy $mdtEnemy */
                            $mdtEnemy = null;
                            foreach ($mdtEnemies as $mdtEnemyCandidate) {
                                // Fix for Siege of Boralus NPC id = 141565, this is an error on MDT's side. It defines multiple
                                // NPCs for one npc_id, 15 because of 15 clones @ SiegeofBoralus.lua:3539
                                $cloneIndexAddition = $mdtEnemyCandidate->npc_id === 141565 ? 15 : 0;
                                // NPC and clone index make for unique ID
                                if ($mdtEnemyCandidate->mdt_npc_index === $npcIndex && ($mdtEnemyCandidate->mdt_id === $cloneIndex || $mdtEnemyCandidate->mdt_id === ($cloneIndex + $cloneIndexAddition))) {
                                    // Found it
                                    $mdtEnemy = $mdtEnemyCandidate;
                                    break;
                                }
                            }

                            if ($mdtEnemy === null) {
                                throw new ImportWarning(sprintf(__('Pull %s'), $pullIndex),
                                    sprintf(__('Unable to find MDT enemy for clone index %s and npc index %s.'), $cloneIndex, $npcIndex),
                                    ['details' => __('This may indicate MDT recently had an update that is not integrated in Keystone.guru yet.')]
                                );
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
                                throw new ImportWarning(sprintf(__('Pull %s'), $pullIndex),
                                    sprintf(__('Unable to find Keystone.guru equivalent for MDT enemy %s with NPC id %s.'), $mdtEnemy->mdt_id, $mdtEnemy->npc_id),
                                    ['details' => __('This may indicate MDT recently had an update that is not integrated in Keystone.guru yet.')]
                                );
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
                    } // Color is randomly put in here
                    else if ($pullKey === 'color') {
                        // Make sure there is a pound sign in front of the value at all times, but never double up should
                        // MDT decide to suddenly place it here
                        $killZone->color = (strpos($pullValue, 0) !== '#' ? '#' : '') . $pullValue;
                    }
                }

                if ($totalEnemiesKilled > 0) {
                    // KillZones at the average position of all killed enemies
                    $killZone->floor_id = $floorId;
                    $killZone->lat = $kzLat / $totalEnemiesKilled;
                    $killZone->lng = $kzLng / $totalEnemiesKilled;

                    // Do not place them right on top of each other
                    if ($totalEnemiesKilled === 1) {
                        $killZone->lat += 1;
                    }


                    if ($save) {
                        $killZone->save();
                    } else {
                        $dungeonRoute->killzones->push($killZone);
                    }
                } else {
                    throw new ImportWarning(sprintf(__('Pull %s'), $pullIndex),
                        __('Failure to find enemies resulted in a pull being skipped.'),
                        ['details' => __('This may indicate MDT recently had an update that is not integrated in Keystone.guru yet.')]
                    );
                }
            } catch (ImportWarning $warning) {
                $warnings->push($warning);
            }
        }
    }

    /**
     * Parse any saved objects from the MDT string to a $dungeonRoute, optionally $save'ing the objects to the database.
     * @param $warnings Collection A Collection of Warnings that this parsing may produce.
     * @param $decoded array
     * @param $dungeonRoute DungeonRoute
     * @param $save boolean
     */
    private function _parseObjects($warnings, $decoded, $dungeonRoute, $save)
    {
        $floors = $dungeonRoute->dungeon->floors;

        if (isset($decoded['objects'])) {
            // Pre-init
            $dungeonRoute->brushlines = new Collection();
            $dungeonRoute->mapicons = new Collection();

            foreach ($decoded['objects'] as $objectIndex => $object) {
                try {
                    /*
                     * Note
                     * 1 = x (size in case of line)
                     * 2 = y (smooth in case of line)
                     * 3 = sublevel
                     * 4 = enabled/visible?
                     * 5 = text (color in case of line)
                     *
                     * Line
                     *
                     * 1 = size
                     * 2 = linefactor (weight?)
                     * 3 = sublevel
                     * 4 = enabled/visible?
                     * 5 = color
                     * 6 = layer sublevel
                     * 7 = smooth
                     */
                    $details = $object['d'];

                    // Get the proper index of the floor, validated for length
                    $floorIndex = ((int)$details['3']) - 1;
                    $floorIndex = ($floorIndex < $floors->count() ? $floorIndex : 0);
                    /** @var Floor $floor */
                    $floor = ($floors->all())[$floorIndex];

                    // If it's a line
                    // MethodDungeonTools.lua:2529
                    if (isset($object['l'])) {
                        $line = $object['l'];

                        $brushline = new Brushline();
                        // Assign the proper ID
                        $brushline->floor_id = $floor->id;
                        $brushline->polyline_id = -1;

                        $polyline = new Polyline();
                        $polyline->color = '#' . $details['5'];
                        $polyline->weight = (int)$details['1'];

                        $vertices = [];
                        for ($i = 1; $i < count($line); $i += 2) {
                            $vertices[] = Conversion::convertMDTCoordinateToLatLng(['x' => doubleval($line[$i + 1]), 'y' => doubleval($line[$i])]);
                        }

                        $polyline->vertices_json = json_encode($vertices);

                        if ($save) {
                            // Only assign when saving
                            $brushline->dungeon_route_id = $dungeonRoute->id;
                            $brushline->save();

                            $polyline->model_id = $brushline->id;
                            $polyline->model_class = get_class($brushline);
                            $polyline->save();
                        } else {
                            // Otherwise inject
                            $brushline->polyline = $polyline;
                            $dungeonRoute->brushlines->push($brushline);
                        }
                    }
                    // Map comment (n = note)
                    // MethodDungeonTools.lua:2523
                    else if (isset($object['n']) && $object['n']) {
                        $mapComment = new MapIcon();
                        $mapComment->floor_id = $floor->id;
                        $mapComment->icon_type = MapIcon::MAP_COMMENT;
                        $mapComment->comment = $details['5'];

                        $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $details['1'], 'y' => $details['2']]);
                        $mapComment->lat = $latLng['lat'];
                        $mapComment->lng = $latLng['lng'];

                        if ($save) {
                            // Save
                            $mapComment->dungeon_route_id = $dungeonRoute->id;
                            $mapComment->save();
                        } else {
                            // Inject
                            $dungeonRoute->mapicons->push($mapComment);
                        }
                    }
                    // Triangles (t = triangle)
                    // MethodDungeonTools.lua:2554
                    else if (isset($object['t']) && $object['t']) {

                    }
                } catch (ImportWarning $warning) {
                    $warnings->push($warning);
                }
            }
        }
    }

    /**
     * Gets the MDT encoded string based on the currently set DungeonRoute.
     * @return string
     */
    public function getEncodedString()
    {
        $lua = $this->_getLua();
        $encoded = $lua->call("TableToString", [$this->_dungeonRoute, true]);
        return $encoded;
    }

    /**
     * Gets an array that represents the currently set MDT string.
     * @return mixed
     */
    public function getDecoded()
    {
        $lua = $this->_getLua();
        // Import it to a table
        return $lua->call("StringToTable", [$this->_encodedString, true]);
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     * @param $warnings Collection Collection that is passed by reference in which any warnings are stored.
     * @param $save boolean True to save the route and all associated models, false to not save & couple.
     * @return DungeonRoute|bool DungeonRoute if the route could be constructed, false if the string was invalid.
     * @throws \Exception
     */
    public function getDungeonRoute($warnings, $save = false)
    {
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
            // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
            $dungeonRoute->faction_id = isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1;
            $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonRoute->teeming = boolval($decoded['value']['teeming']);
            $dungeonRoute->title = $decoded['text'];
            $dungeonRoute->difficulty = 'Casual';
            $dungeonRoute->published = 0; // Needs to be explicit otherwise redirect to edit will not have this value

            if ($save) {
                // Pre-emptively save the route
                $dungeonRoute->save();
            } else {
                $dungeonRoute->killzones = new Collection();
                $dungeonRoute->brushlines = new Collection();
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
            $this->_parseValuePulls($warnings, $decoded, $dungeonRoute, $save);

            // For each object the user created
            $this->_parseObjects($warnings, $decoded, $dungeonRoute, $save);
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