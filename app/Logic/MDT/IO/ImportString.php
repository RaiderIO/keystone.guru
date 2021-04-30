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
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
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
use App\Models\PaidTier;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\PridefulEnemy;
use App\Models\PublishedState;
use App\Service\Season\SeasonService;
use App\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * This file handles any and all conversion from DungeonRoutes to MDT Export strings and vice versa.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class ImportString extends MDTBase
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

                /** @var MapIconType $gatewayIconType */
                $gatewayIconType = MapIconType::where('key', 'gateway')->firstOrFail();

                // From the built array, construct our map icons / paths
                foreach ($rifts as $npcId => $mdtXy) {
                    try {
                        // Find out the floor where the NPC is standing on
                        /** @var Enemy $enemy */
                        $enemy = Enemy::where('npc_id', $npcId)->where('enemy_pack_id', -1)->whereIn('floor_id', $floorIds)->firstOrFail();
                        /** @var MapIcon $obeliskMapIcon */
                        $obeliskMapIcon = $npcIdToMapIconMapping[$npcId];

                        // @TODO #378 We do not support rifts on different floors
                        if (isset($mdtXy['sublevel'])) {
                            throw new ImportWarning('Awakened Obelisks',
                                sprintf(
                                    'Unable to import Awakened Obelisk %s, it is on a different floor than the Obelisk itself. Keystone.guru does not support this at this time.',
                                    $obeliskMapIcon->mapicontype->name
                                )
                            );
                        }

                        $mapIconEnd = new MapIcon(array_merge([
                            'floor_id'         => $enemy->floor_id,
                            'dungeon_route_id' => $dungeonRoute->id,
                            'map_icon_type_id' => $gatewayIconType->id,
                            'comment'          => $obeliskMapIcon->mapicontype->name
                            // MDT has the x and y inverted here
                        ], Conversion::convertMDTCoordinateToLatLng(['x' => $mdtXy['x'], 'y' => $mdtXy['y']])));

                        $hasAnimatedLines = Auth::check() ? User::findOrFail(Auth::id())->hasPaidTier(PaidTier::ANIMATED_POLYLINES) : false;

                        $polyLine = new Polyline([
                            'model_id'       => -1,
                            'model_class'    => Path::class,
                            'color'          => '#80FF1A',
                            'color_animated' => $hasAnimatedLines ? '#244812' : null,
                            'weight'         => 3,
                            'vertices_json'  => json_encode([
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

                        $path = new Path([
                            'floor_id'         => $enemy->floor_id,
                            'dungeon_route_id' => $dungeonRoute->id,
                            'polyline_id'      => $polyLine->id
                        ]);

                        if ($save) {
                            $mapIconEnd->save();
                            $path->save();
                            $polyLine->model_id = $path->id;
                            $polyLine->save();

                            // Link it now that the IDs are known
                            $mapIconEnd->setLinkedAwakenedObeliskByMapIconId($obeliskMapIcon->id);
                            $path->setLinkedAwakenedObeliskByMapIconId($obeliskMapIcon->id);
                        } else {
                            $dungeonRoute->mapicons->push($mapIconEnd);
                            $dungeonRoute->paths->push($path);
                        }

                    } catch (ImportWarning $warning) {
                        $warnings->add($warning);
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
    private function _parseValuePulls(Collection $warnings, array $decoded, DungeonRoute $dungeonRoute, bool $save): void
    {
        $floors = $dungeonRoute->dungeon->floors;
        /** @var Collection|Enemy[] $enemies */
        $enemies = Enemy::whereIn('floor_id', $floors->pluck(['id']))->get();
        // Keep a list of prideful enemies to assign
        $pridefulEnemies = $enemies->where('npc_id', config('keystoneguru.prideful.npc_id'));
        $pridefulEnemyCount = config('keystoneguru.prideful.count');
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $enemiesByNpcId = $enemies->groupBy('npc_id');

        // Fetch all enemies of this dungeon
        $mdtEnemies = (new MDTDungeon($dungeonRoute->dungeon->name))->getClonesAsEnemies($floors);
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $mdtEnemiesByMdtNpcIndex = $mdtEnemies->groupBy('mdt_npc_index');

        // Required for calculating when to add prideful enemies
        $enemyForcesRequired = $dungeonRoute->teeming ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required;

        // For each pull the user created
        $newPullIndex = 1;
        foreach ($decoded['value']['pulls'] as $pullIndex => $pull) {
            // Create a killzone
            $killZone = new KillZone();
            $killZone->index = $newPullIndex;
            if ($save) {
                $killZone->dungeon_route_id = $dungeonRoute->id;
                // Save it so we have an ID that we can use later on
                $killZone->save();
            } else {
                $killZone->enemies = new Collection();
            }

            // The amount of enemies selected in MDT pull
            $totalEnemiesSelected = 0;
            // The amount of enemies that we actually matched with
            $totalEnemiesMatched = 0;
            // Keeps track of the amount of prideful enemies to add, a pull can in theory require us to add multiple
            // But mostly since we add them in the center in the pack, we need to know all coordinates of the pack enemies
            // first before we can place the prideful enemies
            $totalPridefulEnemiesToAdd = 0;

            try {
                $seasonalIndexSkip = false;

                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $pullKey => $pullValue) {
                    // Numeric means it's an index of the dungeon's NPCs
                    if (is_numeric($pullKey)) {
                        $npcIndex = (int)$pullKey;
                        $mdtClones = $pullValue;

                        $totalEnemiesSelected += count($mdtClones);
                        // Only if filled
                        foreach ($mdtClones as $index => $cloneIndex) {
                            // This comes in through as a double, cast to int
                            $cloneIndex = (int)$cloneIndex;

                            // Hacky fix for a MDT bug where there's duplicate NPCs with the same npc_id etc.
                            if ($dungeonRoute->dungeon->isSiegeOfBoralus()) {
                                if ($npcIndex === 35) {
                                    $cloneIndex += 15;
                                }
                            } else if ($dungeonRoute->dungeon->isTolDagor()) {
                                if ($npcIndex === 11) {
                                    $cloneIndex += 2;
                                }
                            }

                            // Find the matching enemy of the clones
                            /** @var Enemy $mdtEnemy */
                            $mdtEnemy = null;
                            foreach ($mdtEnemiesByMdtNpcIndex->get($npcIndex) as $mdtEnemyCandidate) {
                                if ($mdtEnemyCandidate->mdt_id === $cloneIndex) {
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
                                $warnings->push(new ImportWarning(sprintf(__('Pull %s'), $newPullIndex),
                                    sprintf(__('Unable to find MDT enemy for clone index %s and npc index %s.'), $cloneIndex, $npcIndex),
                                    ['details' => __('This indicates MDT has mapped an enemy that is not known in Keystone.guru yet.')]
                                ));
                                continue;
                            }

                            // We now know the MDT enemy that the user was trying to import. However, we need to know
                            // our own enemy. Thus, try to find the enemy in our list which has the same npc_id and mdt_id.
                            /** @var Enemy $enemy */
                            $enemy = null;
                            // Only if we have the npc assigned at all
                            if ($enemiesByNpcId->has($mdtEnemy->npc_id)) {
                                foreach ($enemiesByNpcId->get($mdtEnemy->npc_id) as $enemyCandidate) {
                                    if ($enemyCandidate->mdt_id === $mdtEnemy->mdt_id) {
                                        $enemy = $enemyCandidate;
                                        break;
                                    }
                                }
                            }

                            if ($enemy === null) {
                                $warnings->push(new ImportWarning(sprintf(__('Pull %s'), $newPullIndex),
                                    sprintf(__('Unable to find Keystone.guru equivalent for MDT enemy %s with NPC %s (id: %s).'), $mdtEnemy->mdt_id, $mdtEnemy->npc->name, $mdtEnemy->npc_id),
                                    ['details' => __('This indicates that your route kills an enemy of which its NPC is known to Keystone.guru, but Keystone.guru doesn\'t have that enemy mapped yet.')]
                                ));
                                continue;
                            }

                            // Don't add any teeming enemies
                            if (!$dungeonRoute->teeming && $enemy->teeming === 'visible') {
                                continue;
                            }

                            // Skip enemies that don't belong to our current seasonal index
                            if ($enemy->seasonal_index === null || $enemy->seasonal_index === $dungeonRoute->seasonal_index) {
                                $kzEnemy = new KillZoneEnemy();
                                $kzEnemy->enemy_id = $enemy->id;
                                $kzEnemy->kill_zone_id = $killZone->id;

                                // Couple the KillZoneEnemy to its KillZone
                                if ($save) {
                                    $kzEnemy->save();
                                }

                                // Cache for the hasFinalBoss check below - it's slow otherwise, don't set it above here since
                                // save will trip over it
                                $kzEnemy->enemy = $enemy;

                                // Keep track of our enemy forces
                                $dungeonRoute->enemy_forces += $dungeonRoute->teeming ? $enemy->npc->enemy_forces_teeming : $enemy->npc->enemy_forces;

                                // No point doing this if we're not saving
                                if ($save) {
                                    // Do not add more than 5 regardless of circumstance
                                    if ($dungeonRoute->pridefulenemies->count() + $totalPridefulEnemiesToAdd < $pridefulEnemyCount) {
                                        // If we should add a prideful enemy in this pull ..
                                        $currentPercentage = ($dungeonRoute->enemy_forces / $enemyForcesRequired) * 100;
                                        // Add one so that we start adding at 20%
                                        if ($currentPercentage >= ($dungeonRoute->pridefulenemies->count() + $totalPridefulEnemiesToAdd + 1) * (100 / $pridefulEnemyCount)) {
                                            $totalPridefulEnemiesToAdd++;
                                        }
                                    }
                                }


                                // Save enemies to the killzones regardless
                                $killZone->killzoneenemies->push($kzEnemy);
                                $killZone->enemies->push($enemy);
                                $totalEnemiesMatched++;
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

                // No point doing this if we're not saving
                if ($save) {
                    for ($i = 0; $i < $totalPridefulEnemiesToAdd; $i++) {
                        $pridefulEnemy = new PridefulEnemy();
                        // Get the prideful enemy appropriate for this pull
                        $pridefulEnemy->enemy_id = $pridefulEnemies->slice($dungeonRoute->pridefulenemies->count(), 1)->first()->id;
                        // We kind of assume the prideful enemy should be on the same floor as the enemies
                        $pridefulEnemy->floor_id = $killZone->enemies->first()->floor_id;

                        // Location of the enemy
                        $pridefulEnemy->lat = $killZone->enemies->avg('lat');
                        $pridefulEnemy->lng = $killZone->enemies->avg('lng');

                        $pridefulEnemy->dungeon_route_id = $dungeonRoute->id;
                        // Save it so we have an ID that we can use later on
                        $pridefulEnemy->save();

                        $dungeonRoute->pridefulenemies->push($pridefulEnemy);

                        // Couple the prideful enemy to this pull
                        $kzEnemy = new KillZoneEnemy();
                        $kzEnemy->enemy_id = $pridefulEnemy->enemy_id;
                        $kzEnemy->kill_zone_id = $killZone->id;

                        // Couple the KillZoneEnemy to its KillZone
                        $kzEnemy->save();
                    }
                }

                if ($totalEnemiesMatched > 0) {
                    // In order to import Awakened Bosses that are killed at the final boss, we need to identify if this
                    // pull contains the final boss, and if so, convert all its Awakened enemies to the correct enemies
                    // that are around the boss instead
                    $hasFinalBoss = false;
                    foreach ($killZone->killzoneenemies as $kzEnemy) {
                        if ($kzEnemy->enemy->npc !== null && $kzEnemy->enemy->npc->classification_id === 4) {
                            $hasFinalBoss = true;
                            break;
                        }
                    }

                    if ($hasFinalBoss) {
                        foreach ($killZone->killzoneenemies as $kzEnemy) {
                            if ($kzEnemy->enemy->npc !== null && $kzEnemy->enemy->npc->isAwakened()) {
                                // Find the equivalent Awakened Enemy that's next to the boss.
                                /** @var Enemy $bossAwakenedEnemy */
                                $bossAwakenedEnemy = Enemy::where('npc_id', $kzEnemy->enemy->npc_id)
                                    ->where('seasonal_index', $kzEnemy->enemy->seasonal_index)
                                    ->where('enemy_pack_id', '>', 0)
                                    ->first();

                                if ($bossAwakenedEnemy !== null) {
                                    $kzEnemy->enemy_id = $bossAwakenedEnemy->id;
                                    // Just to be sure
                                    $kzEnemy->unsetRelation('enemy');

                                    if ($save) {
                                        $kzEnemy->save();
                                    }
                                } else {
                                    throw new ImportWarning(sprintf(__('Pull %s'), $newPullIndex),
                                        sprintf(__('Unable to find Awakened Enemy %s (%s) at the final boss in %s.'), $kzEnemy->enemy->npc_id, $kzEnemy->enemy->seasonal_index ?? -1, $dungeonRoute->dungeon->name),
                                        ['details' => __('This indicates Keystone.guru has a mapping error that will need to be correct. Send the above warning to me and I\'ll correct it.')]
                                    );
                                }
                            }
                        }
                    }

                    if ($save) {
                        $killZone->save();
                    } else {
                        $dungeonRoute->killzones->push($killZone);
                    }
                    $newPullIndex++;
                }
                // Don't throw this warning if we skipped things because they were not part of the seasonal index we're importing
                // Also don't throw it if the pull is simply empty in MDT, then just import an empty pull for consistency
                else if (!$seasonalIndexSkip && $totalEnemiesSelected > 0) {
                    if ($save) {
                        $killZone->delete();
                    }
                    throw new ImportWarning(sprintf(__('Pull %s'), $newPullIndex),
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
                     * 1 = size (weight?)
                     * 2 = linefactor
                     * 3 = sublevel
                     * 4 = enabled/visible?
                     * 5 = color
                     * 6 = drawlayer
                     * 7 = smooth
                     */
                    $details = $object['d'];

                    // Get the proper index of the floor, validated for length
                    $floorIndex = ((int)$details['3']) - 1;
                    $floorIndex = ($floorIndex < $floors->count() ? $floorIndex : 0);
                    /** @var Floor $floor */
                    $floor = ($floors->all())[$floorIndex];

                    // Only if shown/visible
                    if ($details['4']) {
                        // If it's a line
                        // MethodDungeonTools.lua:2529
                        if (isset($object['l'])) {
                            $line = $object['l'];

                            $isFreeDrawn = isset($details['7']) && $details['7'];
                            /** @var Brushline|Path $lineOrPath */
                            $lineOrPath = $isFreeDrawn ? new Brushline() : new Path();
                            // Assign the proper ID
                            $lineOrPath->floor_id = $floor->id;
                            $lineOrPath->polyline_id = -1;

                            $polyline = new Polyline();

                            // Make sure there is a pound sign in front of the value at all times, but never double up should
                            // MDT decide to suddenly place it here
                            $polyline->color = (substr($details['5'], 0, 1) !== '#' ? '#' : '') . $details['5'];
                            $polyline->weight = (int)$details['1'];

                            $vertices = [];
                            for ($i = 1; $i < count($line); $i += 2) {
                                $vertices[] = Conversion::convertMDTCoordinateToLatLng(['x' => doubleval($line[$i]), 'y' => doubleval($line[$i + 1])]);
                            }

                            $polyline->vertices_json = json_encode($vertices);

                            if ($save) {
                                // Only assign when saving
                                $lineOrPath->dungeon_route_id = $dungeonRoute->id;
                                $lineOrPath->save();

                                $polyline->model_id = $lineOrPath->id;
                                $polyline->model_class = get_class($lineOrPath);
                                $polyline->save();
                            } else {
                                // Otherwise inject
                                $lineOrPath->polyline = $polyline;
                                if ($isFreeDrawn) {
                                    $dungeonRoute->brushlines->push($lineOrPath);
                                } else {
                                    $dungeonRoute->paths->push($lineOrPath);
                                }
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
                    }
                } catch (ImportWarning $warning) {
                    $warnings->push($warning);
                }
            }
        }
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
     * @param $sandbox boolean True to mark the dungeon as a sandbox route which will be automatically deleted at a later stage.
     * @param $save boolean True to save the route and all associated models, false to not save & couple.
     * @return DungeonRoute DungeonRoute if the route could be constructed
     * @throws InvalidMDTString
     * @throws Exception
     */
    public function getDungeonRoute(Collection $warnings, $sandbox = false, $save = false): DungeonRoute
    {
        $lua = $this->_getLua();
        // Import it to a table
        $decoded = $lua->call("StringToTable", [$this->_encodedString, true]);
        // Check if it's valid
        $isValid = $lua->call("ValidateImportPreset", [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTString();
        }

        // Create a dungeon route
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->author_id = $sandbox ? -1 : Auth::id();
        $dungeonRoute->dungeon_id = Conversion::convertMDTDungeonID($decoded['value']['currentDungeonIdx']);
        // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
        $dungeonRoute->faction_id = isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1;
        $dungeonRoute->published_state_id = PublishedState::where('name', PublishedState::UNPUBLISHED)->first()->id; // Needs to be explicit otherwise redirect to edit will not have this value
        $dungeonRoute->public_key = DungeonRoute::generateRandomPublicKey();
        $dungeonRoute->teeming = boolval($decoded['value']['teeming']);
        $dungeonRoute->title = $decoded['text'];
        $dungeonRoute->difficulty = 'Casual';
        // Must expire if we're trying
        if ($sandbox) {
            $dungeonRoute->expires_at = Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString();
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

        // Re-calculate the enemy forces
        if ($save) {
            $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
        } else {
            // Do not do this - the enemy_forces are incremented while creating the route
            // $dungeonRoute->enemy_forces = $dungeonRoute->getEnemyForces();
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
}