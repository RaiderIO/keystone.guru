<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 05/01/2019
 * Time: 20:49
 */

namespace App\Logic\MDT\IO;


use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\AffixGroup;
use App\Models\Brushline;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Path;
use App\Models\Polyline;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Lua;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ImportString
{
    /** @var $_encodedString string The MDT encoded string that's currently staged for conversion to a DungeonRoute. */
    private $_encodedString;

    /** @var DungeonRoute The route that's currently staged for conversion to an encoded string. */
    private $_dungeonRoute;

    /** @var SeasonService Used for grabbing info about the current M+ season. */
    private $_seasonService;


    function __construct(SeasonService $seasonService)
    {
        $this->_seasonService = $seasonService;
    }

    /**
     * Gets a Lua instance and load all the required files in it.
     * @return Lua
     */
    private function _getLua()
    {
        $lua = new Lua();

        // Load libraries (yeah can do this with ->library function as well)
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibCompress.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibDeflate.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/AceSerializer.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/MDTTransmission.lua')));

        return $lua;
    }

    /**
     * @param Collection $warnings
     * @param array $decoded
     * @param DungeonRoute $dungeonRoute
     * @param boolean $save
     */
    private function _parseRiftOffsets($warnings, $decoded, $dungeonRoute, $save)
    {
        // Build an array with a structure that makes more sense
        $rifts = [];

        if (isset($decoded['value']['riftOffsets'])) {
            foreach ($decoded['value']['riftOffsets'] as $weekIndex => $riftOffsets) {
                if ($weekIndex === (int)$decoded['week']) {
                    $rifts = $riftOffsets;
                    break;
                }
            }

            if (!empty($rifts)) {
                // Loaded for the comment import
                $floorIds = $dungeonRoute->dungeon->floors->pluck('id');
                $seasonalIndexWhere = function (Builder $query) use ($dungeonRoute)
                {
                    $query->whereNull('seasonal_index')
                        ->orWhere('seasonal_index', $dungeonRoute->seasonal_index);
                };

                $npcIdToMapIconMapping = [
                    161124 => MapIcon::where('map_icon_type_id', 17)
                        ->whereIn('floor_id', $floorIds) // Urg'roth, Brutal spire
                        ->where($seasonalIndexWhere)->firstOrFail(),
                    161241 => MapIcon::where('map_icon_type_id', 18)
                        ->whereIn('floor_id', $floorIds) // Cursed spire
                        ->where($seasonalIndexWhere)->firstOrFail(),
                    161244 => MapIcon::where('map_icon_type_id', 19)
                        ->whereIn('floor_id', $floorIds) // Blood of the Corruptor, Defiled spire
                        ->where($seasonalIndexWhere)->firstOrFail(),
                    161243 => MapIcon::where('map_icon_type_id', 20)
                        ->whereIn('floor_id', $floorIds) // Samh'rek, Entropic spire
                        ->where($seasonalIndexWhere)->firstOrFail(),
                ];

                $gatewayIconType = MapIconType::where('key', 'gateway')->firstOrFail();

                // From the built array, construct our map icons / paths
                foreach ($rifts as $npcId => $mdtXY) {
                    // Find out the floor where the NPC is standing on
                    /** @var Enemy $enemy */
                    $enemy = Enemy::where('npc_id', $npcId)->whereIn('floor_id', $floorIds)->firstOrFail();
                    /** @var MapIcon $obeliskMapIcon */
                    $obeliskMapIcon = $npcIdToMapIconMapping[$npcId];

                    $mapIconEnd = new MapIcon(array_merge([
                        'floor_id'         => $enemy->floor_id,
                        'dungeon_route_id' => $dungeonRoute->id,
                        'map_icon_type_id' => $gatewayIconType->id,
                        'comment'          => $obeliskMapIcon->mapicontype->name
                        // MDT has the x and y inverted here
                    ], Conversion::convertMDTCoordinateToLatLng(['x' => $mdtXY['x'], 'y' => $mdtXY['y']])));

                    $polyLine = new Polyline([
                        'model_id'      => -1,
                        'model_class'   => Path::class,
                        'color'         => '#80FF1A',
                        'weight'        => 3,
                        'vertices_json' => json_encode([
                            [
                                'lat' => $obeliskMapIcon->lat,
                                'lng' => $obeliskMapIcon->lng
                            ],
                            [
                                'lat' => $mapIconEnd->lat,
                                'lng' => $mapIconEnd->lng
                            ]
                        ])
                    ]);

                    if ($save) {
                        $polyLine->save();
                    }

                    $mdtXY = new Path([
                        'floor_id'         => $enemy->floor_id,
                        'dungeon_route_id' => $dungeonRoute->id,
                        'polyline_id'      => $polyLine->id
                    ]);

                    if ($save) {
                        $mapIconEnd->save();

                        $mdtXY->save();
                        $polyLine->model_id = $mdtXY->id;
                        $polyLine->save();
                    } else {
                        $dungeonRoute->mapicons->push($mapIconEnd);
                        $dungeonRoute->paths->push($mdtXY);
                    }
                }
            }
        }
    }

    /**
     * Parse the $decoded['value']['pulls'] value and put it in the $dungeonRoute object, optionally $save'ing the
     * values to the database.
     * @param $warnings Collection A Collection of Warnings that this parsing may produce.
     * @param $decoded array
     * @param $dungeonRoute DungeonRoute
     * @param $save boolean
     * @throws Exception
     */
    private function _parseValuePulls($warnings, $decoded, $dungeonRoute, $save)
    {
        $floors = $dungeonRoute->dungeon->floors;
        $enemies = Enemy::whereIn('floor_id', $floors->pluck(['id']))->get();

        // Fetch all enemies of this
        $mdtEnemies = (new MDTDungeon($dungeonRoute->dungeon->name))->getClonesAsEnemies($floors);

        // For each pull the user created
        foreach ($decoded['value']['pulls'] as $pullIndex => $pull) {
            // Create a killzone
            $killZone = new KillZone();
            $killZone->index = $pullIndex;
            if ($save) {
                $killZone->dungeon_route_id = $dungeonRoute->id;
                // Save it so we have an ID that we can use later on
                $killZone->save();
            } else {
                $killZone->enemies = new Collection();
            }

            // Init some variables
            $totalEnemiesKilled = 0;

            try {
                $seasonalIndexSkip = false;

                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $pullKey => $pullValue) {
                    // Numeric means it's an index of the dungeon's NPCs
                    if (is_numeric($pullKey)) {
                        $npcIndex = (int)$pullKey;
                        $mdtClones = $pullValue;
                        // Only if filled
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

                                    // Skip Emissaries (Season 3), season is over
                                    if (in_array($mdtEnemy->npc_id, [155432, 155433, 155434])) {
                                        break 2;
                                    }

                                    break;
                                }
                            }

                            if ($mdtEnemy === null) {
                                throw new ImportWarning(sprintf(__('Pull %s'), $pullIndex),
                                    sprintf(__('Unable to find MDT enemy for clone index %s and npc index %s.'), $cloneIndex, $npcIndex),
                                    ['details' => __('This indicates MDT has mapped an enemy that is not known in Keystone.guru yet.')]
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
                                    sprintf(__('Unable to find Keystone.guru equivalent for MDT enemy %s with NPC %s (id: %s).'), $mdtEnemy->mdt_id, $mdtEnemy->npc->name, $mdtEnemy->npc_id),
                                    ['details' => __('This indicates that your route kills an enemy of which its NPC is known to Keystone.guru, but Keystone.guru doesn\'t have that enemy mapped yet.')]
                                );
                            }

                            // Don't add any teeming enemies
                            if (!$dungeonRoute->teeming && $enemy->teeming === 'visible') {
                                continue;
                            }

                            // Skip enemies that don't belong to our current seasonal index
                            if ($enemy->seasonal_index === null || $enemy->seasonal_index === $dungeonRoute->seasonal_index) {
                                // Couple the KillZoneEnemy to its KillZone
                                if ($save) {
                                    $kzEnemy = new KillZoneEnemy();
                                    $kzEnemy->enemy_id = $enemy->id;
                                    $kzEnemy->kill_zone_id = $killZone->id;
                                    $kzEnemy->save();
                                }

                                // Save enemies to the killzones regardless
                                $killZone->enemies->push($enemy);
                                $totalEnemiesKilled++;
                            } else {
                                $seasonalIndexSkip = true;
                            }
                        }

                    } // Color is randomly put in here
                    else if ($pullKey === 'color') {
                        // Make sure there is a pound sign in front of the value at all times, but never double up should
                        // MDT decide to suddenly place it here
                        $killZone->color = (substr($pullValue, 0, 1) !== '#' ? '#' : '') . $pullValue;
                    }
                }

                if ($totalEnemiesKilled > 0) {
                    if ($save) {
                        $killZone->save();
                    } else {
                        $dungeonRoute->killzones->push($killZone);
                    }
                } // Don't throw this warning if we skipped things because they were not part of the seasonal index we're importing
                else if (!$seasonalIndexSkip) {
                    if ($save) {
                        $killZone->delete();
                    }
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
                            $vertices[] = Conversion::convertMDTCoordinateToLatLng(['x' => doubleval($line[$i]), 'y' => doubleval($line[$i + 1])]);
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
                        // Bit hacky? But should work
                        $mapComment->map_icon_type_id = MapIconType::where('key', 'comment')->firstOrFail()->id;
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
     * @param $try boolean True to mark the dungeon as a try route which will be automatically deleted at a later stage.
     * @param $save boolean True to save the route and all associated models, false to not save & couple.
     * @return DungeonRoute|bool DungeonRoute if the route could be constructed, false if the string was invalid.
     * @throws Exception
     */
    public function getDungeonRoute($warnings, $try = false, $save = false)
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
            $dungeonRoute->author_id = $try ? -1 : Auth::id();
            $dungeonRoute->dungeon_id = Conversion::convertMDTDungeonID($decoded['value']['currentDungeonIdx']);
            // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
            $dungeonRoute->faction_id = isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1;
            $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
            $dungeonRoute->teeming = boolval($decoded['value']['teeming']);
            $dungeonRoute->title = $decoded['text'];
            $dungeonRoute->difficulty = 'Casual';
            $dungeonRoute->published = 0; // Needs to be explicit otherwise redirect to edit will not have this value
            // Must expire if we're trying
            if ($try) {
                $dungeonRoute->expires_at = Carbon::now()->addHour(config('keystoneguru.try_dungeon_route_expires_hours'))->toDateTimeString();
            }

            if ($save) {
                // Pre-emptively save the route
                $dungeonRoute->save();
            } else {
                $dungeonRoute->killzones = new Collection();
                $dungeonRoute->brushlines = new Collection();
                $dungeonRoute->mapicons = new Collection();
                $dungeonRoute->paths = new Collection();
                $dungeonRoute->affixes = new Collection();
            }

            // Set the affix for this route
            $affixGroup = Conversion::convertWeekToAffixGroup($this->_seasonService, $decoded['week']);
            if ($affixGroup instanceof AffixGroup) {
                if ($save) {
                    // Something we can save to the database
                    $dungeonAffixGroup = new DungeonRouteAffixGroup();
                    $dungeonAffixGroup->affix_group_id = $affixGroup->id;
                    $dungeonAffixGroup->dungeon_route_id = $dungeonRoute->id;
                    $dungeonAffixGroup->save();
                } else {
                    // Something we can just return and have the user read
                    $dungeonRoute->affixes->push($affixGroup);
                }
                // Apply the seasonal index to the route
                $dungeonRoute->seasonal_index = $affixGroup->seasonal_index;
            } else {
                $dungeonRoute->seasonal_index = 0;
            }

            // Update seasonal index
            if ($save) {
                $dungeonRoute->save();
            }

            // Create a path and map icons for MDT rift offsets
            $this->_parseRiftOffsets($warnings, $decoded, $dungeonRoute, $save);

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