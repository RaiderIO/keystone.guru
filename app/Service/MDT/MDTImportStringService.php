<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Models\Affix;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\NpcClassification;
use App\Models\Path;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Models\PublishedState;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Class MDTImportStringService
 * @package App\Service\MDT
 * @author Wouter
 * @since 09/11/2022
 */
class MDTImportStringService extends MDTBaseService implements MDTImportStringServiceInterface
{
    /** @var string $encodedString The MDT encoded string that's currently staged for conversion to a DungeonRoute. */
    private string $encodedString;

    /** @var SeasonService Used for grabbing info about the current M+ season. */
    private SeasonService $seasonService;


    function __construct(SeasonService $seasonService)
    {
        $this->seasonService = $seasonService;
    }

    /**
     * @param Collection $warnings
     * @param array $decoded
     * @param DungeonRoute $dungeonRoute
     * @param boolean $save
     * @throws ImportWarning
     */
    private function parseRiftOffsets(Collection $warnings, array $decoded, DungeonRoute $dungeonRoute, bool $save)
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

                try {
                    $seasonalIndexWhere = function (Builder $query) use ($dungeonRoute) {
                        $query->whereNull('seasonal_index')
                            ->orWhere('seasonal_index', $dungeonRoute->seasonal_index ?? 1);
                    };

                    $npcIdToMapIconMapping = [
                        161124 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL])
                            ->whereIn('floor_id', $floorIds) // Urg'roth, Brutal spire
                            ->where($seasonalIndexWhere)->firstOrFail(),
                        161241 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED])
                            ->whereIn('floor_id', $floorIds) // Cursed spire
                            ->where($seasonalIndexWhere)->firstOrFail(),
                        161244 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED])
                            ->whereIn('floor_id', $floorIds) // Blood of the Corruptor, Defiled spire
                            ->where($seasonalIndexWhere)->firstOrFail(),
                        161243 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC])
                            ->whereIn('floor_id', $floorIds) // Samh'rek, Entropic spire
                            ->where($seasonalIndexWhere)->firstOrFail(),
                    ];

                } catch (Exception $exception) {
                    throw new ImportWarning('Awakened Obelisks',
                        sprintf(
                            'Cannot find Awakened Obelisks for your dungeon/week combination. Your Awakened Obelisk skips will not be imported.'
                        )
                    );
                }

                // From the built array, construct our map icons / paths
                foreach ($rifts as $npcId => $mdtXy) {
                    try {
                        // Find out the floor where the NPC is standing on
                        /** @var Enemy $enemy */
                        $enemy = Enemy::where('npc_id', $npcId)->whereNotNull('enemy_pack_id')->whereIn('floor_id', $floorIds)->firstOrFail();
                        /** @var MapIcon $obeliskMapIcon */
                        $obeliskMapIcon = $npcIdToMapIconMapping[$npcId];

                        if (isset($mdtXy['sublevel'])) {
                            throw new ImportWarning('Awakened Obelisks',
                                sprintf(
                                    'Unable to import Awakened Obelisk %s, it is on a different floor than the Obelisk itself. Keystone.guru does not support this at this time.',
                                    $obeliskMapIcon->mapicontype->name
                                )
                            );
                        }

                        $mapIconEnd = new MapIcon(array_merge([
                            'mapping_version_id' => null,
                            'floor_id'           => $enemy->floor_id,
                            'dungeon_route_id'   => $dungeonRoute->id,
                            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY],
                            'comment'            => $obeliskMapIcon->mapicontype->name,
                            // MDT has the x and y inverted here
                        ], Conversion::convertMDTCoordinateToLatLng(['x' => $mdtXy['x'], 'y' => $mdtXy['y']])));

                        $hasAnimatedLines = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES);

                        $polyLine = new Polyline([
                            'model_id'       => -1,
                            'model_class'    => Path::class,
                            'color'          => '#80FF1A',
                            'color_animated' => $hasAnimatedLines ? '#244812' : null,
                            'weight'         => 3,
                            'vertices_json'  => json_encode([
                                [
                                    'lat' => $obeliskMapIcon->lat,
                                    'lng' => $obeliskMapIcon->lng,
                                ],
                                [
                                    'lat' => $mapIconEnd->lat,
                                    'lng' => $mapIconEnd->lng,
                                ],
                            ]),
                        ]);

                        if ($save) {
                            $polyLine->save();
                        }

                        $path = new Path([
                            'floor_id'         => $enemy->floor_id,
                            'dungeon_route_id' => $dungeonRoute->id,
                            'polyline_id'      => $polyLine->id,
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
     * @param Collection $warnings A Collection of Warnings that this parsing may produce.
     * @param array $decoded
     * @param DungeonRoute $dungeonRoute
     * @param boolean $save
     * @throws Exception
     */
    private function parseValuePulls(Collection $warnings, array $decoded, DungeonRoute $dungeonRoute, bool $save): void
    {
        $floors = $dungeonRoute->dungeon->floors;
        /** @var Collection|Enemy[] $enemies */
        $enemies = $dungeonRoute->mappingVersion->enemies->each(function (Enemy $enemy) {
            $enemy->npc_id = $enemy->mdt_npc_id ?? $enemy->npc_id;
        });

        // We only need to take the prideful enemies into account if the route is prideful
        $isRoutePrideful = $dungeonRoute->hasUniqueAffix(Affix::AFFIX_PRIDEFUL);
        // Keep a list of prideful enemies to assign
        $pridefulEnemies    = $enemies->where('npc_id', config('keystoneguru.prideful.npc_id'));
        $pridefulEnemyCount = config('keystoneguru.prideful.count');
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $enemiesByNpcId = $enemies->groupBy('npc_id');

        // Fetch all enemies of this dungeon
        $mdtEnemies = (new MDTDungeon($dungeonRoute->dungeon))->getClonesAsEnemies($floors);
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $mdtEnemiesByMdtNpcIndex = $mdtEnemies->groupBy('mdt_npc_index');

        // Required for calculating when to add prideful enemies
        $enemyForcesRequired = $dungeonRoute->teeming ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required;

        // For each pull the user created
        $newPullIndex = 1;
        foreach ($decoded['value']['pulls'] as $pullIndex => $pull) {
            // Create a killzone
            $killZone        = new KillZone();
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
                        $npcIndex  = (int)$pullKey;
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
                            } else if ($dungeonRoute->dungeon->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE) {
                                if ($npcIndex === 23) {
                                    $cloneIndex += 5;
                                }
                            }

                            // Find the matching enemy of the clones
                            /** @var Enemy $mdtEnemy */
                            $mdtEnemy = null;
                            if ($mdtEnemiesByMdtNpcIndex->has($npcIndex)) {
                                foreach ($mdtEnemiesByMdtNpcIndex->get($npcIndex) as $mdtEnemyCandidate) {
                                    /** @var $mdtEnemyCandidate Enemy */
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
                            }

                            if ($mdtEnemy === null) {
                                $warnings->push(new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                                    sprintf(__('logic.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index'), $cloneIndex, $npcIndex),
                                    ['details' => __('logic.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index_details')]
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
                                    /** @var $enemyCandidate Enemy */
                                    if ($enemyCandidate->mdt_id === $mdtEnemy->mdt_id) {
                                        $enemy = $enemyCandidate;
                                        break;
                                    }
                                }
                            }

                            if ($enemy === null) {
                                // Teeming is gone, and its enemies have not always been mapped on purpose. So if we cannot find a Teeming enemy
                                // we can skip this warning as to not alert people to something that shouldn't be there in the first place
                                // Secondly, MDT does something weird with shrouded enemies. It has both the normal enemy and a shrouded infiltrator
                                // mapped. The shrouded infiltrator is what you kill in MDT, but the normal enemy is somehow put in other pulls.
                                // Since an enemy on my side can only be mapped to one MDT enemy I now choose the Infiltrator and we can discard the other one.
                                // The other enemy is marked as shrouded, so if we cannot find a shrouded normal mob we skip it and don't alert
                                if (!$mdtEnemy->teeming && $mdtEnemy->seasonal_type !== Enemy::SEASONAL_TYPE_SHROUDED) {
                                    $warnings->push(new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                                        sprintf(__('logic.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy'), $mdtEnemy->mdt_id, $mdtEnemy->npc->name, $mdtEnemy->npc_id),
                                        ['details' => __('logic.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy_details')]
                                    ));
                                }
                                continue;
                            }

                            // Don't add any teeming enemies
                            if (!$dungeonRoute->teeming && $enemy->teeming === 'visible') {
                                continue;
                            }

                            // Skip mdt placeholders - we found it, great, but we don't show this on our mapping so get rid of it
                            if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER) {
                                continue;
                            }

                            // Skip enemies that don't belong to our current seasonal index
                            if ($enemy->seasonal_index === null || $enemy->seasonal_index === $dungeonRoute->seasonal_index) {
                                $kzEnemy = new KillZoneEnemy([
                                    'kill_zone_id' => $killZone->id,
                                    'npc_id'       => $enemy->npc_id,
                                    'mdt_id'       => $enemy->mdt_id,
                                ]);

                                // Couple the KillZoneEnemy to its KillZone
                                if ($save) {
                                    $kzEnemy->save();
                                }

                                // Cache for the hasFinalBoss check below - it's slow otherwise, don't set it above here since
                                // save will trip over it
                                $kzEnemy->enemy = $enemy;

                                // Keep track of our enemy forces
                                if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED) {
                                    $dungeonRoute->enemy_forces += $dungeonRoute->dungeon->enemy_forces_shrouded;
                                } else if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX) {
                                    $dungeonRoute->enemy_forces += $dungeonRoute->dungeon->enemy_forces_shrouded_zul_gamux;
                                } else {
                                    $dungeonRoute->enemy_forces += $dungeonRoute->teeming ? $enemy->npc->enemy_forces_teeming : $enemy->npc->enemy_forces;
                                }

                                // No point doing this if we're not saving
                                if ($save && $isRoutePrideful) {
                                    // Do not add more than 5 regardless of circumstance
                                    if ($dungeonRoute->pridefulEnemies->count() + $totalPridefulEnemiesToAdd < $pridefulEnemyCount) {
                                        // If we should add a prideful enemy in this pull ..
                                        $currentPercentage = ($dungeonRoute->enemy_forces / $enemyForcesRequired) * 100;
                                        // Add one so that we start adding at 20%
                                        if ($currentPercentage >= ($dungeonRoute->pridefulEnemies->count() + $totalPridefulEnemiesToAdd + 1) * (100 / $pridefulEnemyCount)) {
                                            $totalPridefulEnemiesToAdd++;
                                        }
                                    }
                                }


                                // Save enemies to the killzones regardless
                                $killZone->killzoneEnemies->push($kzEnemy);
                                $killZone->enemies->push($enemy->id);
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
                        /** @var Enemy $pridefulEnemyEnemy */
                        $pridefulEnemyEnemy = $pridefulEnemies->slice($dungeonRoute->pridefulEnemies->count(), 1)->first();
                        $pridefulEnemy      = PridefulEnemy::create([
                            'dungeon_route_id' => $dungeonRoute->id,
                            'enemy_id'         => $pridefulEnemyEnemy->id,
                            'floor_id'         => $killZone->enemies->first()->floor_id,
                            'lat'              => $killZone->enemies->avg('lat'),
                            'lng'              => $killZone->enemies->avg('lng'),
                        ]);

                        $dungeonRoute->pridefulEnemies->push($pridefulEnemy);

                        // Couple the prideful enemy to this pull
                        KillZoneEnemy::create([
                            'kill_zone_id' => $killZone->id,
                            'npc_id'       => $pridefulEnemyEnemy->npc_id,
                            'mdt_id'       => $pridefulEnemyEnemy->mdt_id,
                        ]);
                    }
                }

                if ($totalEnemiesMatched > 0) {
                    // In order to import Awakened Bosses that are killed at the final boss, we need to identify if this
                    // pull contains the final boss, and if so, convert all its Awakened enemies to the correct enemies
                    // that are around the boss instead
                    $hasFinalBoss = false;
                    foreach ($killZone->killzoneEnemies as $kzEnemy) {
                        if ($kzEnemy->npc !== null && $kzEnemy->npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS]) {
                            $hasFinalBoss = true;
                            break;
                        }
                    }

                    if ($hasFinalBoss) {
                        foreach ($killZone->killzoneEnemies as $kzEnemy) {
                            if ($kzEnemy->npc !== null && $kzEnemy->npc->isAwakened()) {
                                // Find the equivalent Awakened Enemy that's next to the boss.
                                /** @var Enemy $bossAwakenedEnemy */
                                $bossAwakenedEnemy = Enemy::where('npc_id', $kzEnemy->npc_id)
                                    ->where('mdt_id', $kzEnemy->mdt_id)
                                    ->where('seasonal_index', $kzEnemy->enemy->seasonal_index)
                                    ->whereNotNull('enemy_pack_id')
                                    ->first();

                                if ($bossAwakenedEnemy !== null) {
                                    $kzEnemy->enemy_id = $bossAwakenedEnemy->id;
                                    // Just to be sure
                                    $kzEnemy->unsetRelation('enemy');

                                    if ($save) {
                                        $kzEnemy->save();
                                    }
                                } else {
                                    throw new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                                        sprintf(__('unable_to_find_awakened_enemy_for_final_boss'), $kzEnemy->enemy->npc_id, $kzEnemy->enemy->seasonal_index ?? -1, __($dungeonRoute->dungeon->name)),
                                        ['details' => __('logic.mdt.io.import_string.unable_to_find_awakened_enemy_for_final_boss_details')]
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
                    throw new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                        __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped'),
                        ['details' => __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped_details')]
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
     * @param $save bool
     */
    private function parseObjects(Collection $warnings, array $decoded, DungeonRoute $dungeonRoute, bool $save)
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
                    // Fix a strange issue where 6 would sometimes not be set - and then the array may look like this:
                    /** d: {
                     * 1: 3,
                     * 2: 1.1,
                     * 3: 1,
                     * 4: false,
                     * 5: "fafff9",
                     * 7: true
                     * } */
                    if (!isset($object['d'][0])) {
                        if (!isset($object['d'][6])) {
                            $object['d'][6] = 0;
                        }
                        $details = array_values($object['d']);
                    } else {
                        $details = $object['d'];
                    }

                    // Get the proper index of the floor, validated for length
                    $mdtSubLevel = ((int)$details[2]);

                    /** @var Floor $floor */
                    $floor = $floors->first(function (Floor $floor) use ($mdtSubLevel) {
                        return ($floor->mdt_sub_level ?? $floor->index) === $mdtSubLevel;
                    });

                    if ($floor === null) {
                        throw new ImportWarning(
                            sprintf(__('logic.mdt.io.import_string.category.object'), $objectIndex),
                            sprintf(__('logic.mdt.io.import_string.unable_to_find_floor_for_object'), $mdtSubLevel),
                            ['details' => __('logic.mdt.io.import_string.unable_to_find_floor_for_object_details') . json_encode($details)]
                        );
                    }

                    // Only if shown/visible
                    if ($details[3]) {
                        // If it's a line
                        // MethodDungeonTools.lua:2529
                        if (isset($object['l'])) {
                            $line = $object['l'];

                            $isFreeDrawn = isset($details[6]) && $details[6];
                            /** @var Brushline|Path $lineOrPath */
                            $lineOrPath = $isFreeDrawn ? new Brushline() : new Path();
                            // Assign the proper ID
                            $lineOrPath->floor_id    = $floor->id;
                            $lineOrPath->polyline_id = -1;

                            $polyline = new Polyline();

                            // Make sure there is a pound sign in front of the value at all times, but never double up should
                            // MDT decide to suddenly place it here
                            $polyline->color  = (substr($details[4], 0, 1) !== '#' ? '#' : '') . $details[4];
                            $polyline->weight = (int)$details[0];

                            $vertices = [];
                            for ($i = 0; $i < count($line); $i += 2) {
                                $vertices[] = Conversion::convertMDTCoordinateToLatLng(['x' => doubleval($line[$i]), 'y' => doubleval($line[$i + 1])]);
                            }

                            $polyline->vertices_json = json_encode($vertices);

                            if ($save) {
                                // Only assign when saving
                                $lineOrPath->dungeon_route_id = $dungeonRoute->id;
                                $lineOrPath->save();

                                $polyline->model_id    = $lineOrPath->id;
                                $polyline->model_class = get_class($lineOrPath);
                                $polyline->save();

                                $lineOrPath->polyline_id = $polyline->id;
                                $lineOrPath->save();
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
                            $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $details[0], 'y' => $details[1]]);

                            $mapIconTypeId = MapIconType::MAP_ICON_TYPE_COMMENT;
                            $commentLower  = strtolower($details[4]);
                            if ($commentLower === 'heroism') {
                                $mapIconTypeId = MapIconType::MAP_ICON_TYPE_SPELL_HEROISM;
                            } else if ($commentLower === 'bloodlust') {
                                $mapIconTypeId = MapIconType::MAP_ICON_TYPE_SPELL_BLOODLUST;
                            }

                            $mapComment = new MapIcon([
                                'mapping_version_id' => null,
                                'floor_id'           => $floor->id,
                                'map_icon_type_id'   => MapIconType::where('key', $mapIconTypeId)->firstOrFail()->id,
                                'comment'            => $details[4],
                                'lat'                => $latLng['lat'],
                                'lng'                => $latLng['lng'],
                            ]);

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
                        // else if (isset($object['t']) && $object['t']) {

                        // }
                    }
                } catch (ImportWarning $warning) {
                    $warnings->push($warning);
                }
            }
        }
    }

    /**
     * Gets an array that represents the currently set MDT string.
     * @return array|null
     */
    public function getDecoded(): ?array
    {
//        $lua = $this->_getLua();
        // Import it to a table
//        return $lua->call("StringToTable", [$this->_encodedString, true]);
        return $this->decode($this->encodedString);
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     * @param  $warnings Collection Collection that is passed by reference in which any warnings are stored.
     * @param  $sandbox boolean True to mark the dungeon as a sandbox route which will be automatically deleted at a later stage.
     * @param  $save bool True to save the route and all associated models, false to not save & couple.
     * @param  $importAsThisWeek bool True to replace the imported affixes with this week's affixes instead
     * @return DungeonRoute DungeonRoute if the route could be constructed
     * @throws InvalidMDTString
     * @throws Exception
     */
    public function getDungeonRoute(Collection $warnings, bool $sandbox = false, bool $save = false, bool $importAsThisWeek = false): DungeonRoute
    {
        $lua = $this->getLua();
        // Import it to a table
//        $decoded = $lua->call("StringToTable", [$this->_encodedString, true]);
        $decoded = $this->decode($this->encodedString);
        // Check if it's valid
        $isValid = $lua->call("ValidateImportPreset", [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTString('Unable to validate MDT import string in Lua');
        }

        // Create a dungeon route
        $dungeonRoute             = new DungeonRoute();
        $dungeonRoute->author_id  = $sandbox ? -1 : Auth::id();
        $dungeonRoute->dungeon_id = Conversion::convertMDTDungeonID($decoded['value']['currentDungeonIdx']);
        // Set some relations here so we can reference them later
        $dungeonRoute->dungeon            = Dungeon::findOrFail($dungeonRoute->dungeon_id);
        $dungeonRoute->mappingVersion     = $dungeonRoute->dungeon->getCurrentMappingVersion();
        $dungeonRoute->mapping_version_id = $dungeonRoute->mappingVersion->id;

        // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
        $dungeonRoute->faction_id         = isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1;
        $dungeonRoute->published_state_id = PublishedState::ALL[PublishedState::UNPUBLISHED]; // Needs to be explicit otherwise redirect to edit will not have this value
        $dungeonRoute->public_key         = DungeonRoute::generateRandomPublicKey();
        $dungeonRoute->teeming            = boolval($decoded['value']['teeming']);
        $dungeonRoute->title              = empty($decoded['text']) ? 'No title' : $decoded['text'];
        $dungeonRoute->difficulty         = 'Casual';
        $dungeonRoute->level_min          = $decoded['difficulty'];
        $dungeonRoute->level_max          = $decoded['difficulty'];

        // Must expire if we're trying
        if ($sandbox) {
            $dungeonRoute->expires_at = Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString();
        }

        if ($save) {
            // Can't save these relations to database
            unset($dungeonRoute->dungeon);
            unset($dungeonRoute->mappingVersion);

            // Pre-emptively save the route
            $dungeonRoute->save();
        } else {
            $dungeonRoute->killzones  = new Collection();
            $dungeonRoute->brushlines = new Collection();
            $dungeonRoute->mapicons   = new Collection();
            $dungeonRoute->paths      = new Collection();
            $dungeonRoute->affixes    = new Collection();
        }

        // Set the affix for this route
        $affixGroup = Conversion::convertWeekToAffixGroup($this->seasonService, $dungeonRoute->dungeon, $decoded['week']);

        // If affix group not found or
        if ($importAsThisWeek || $affixGroup === null) {
            $activeSeason = $dungeonRoute->dungeon->getActiveSeason($this->seasonService);
            if ($activeSeason !== null) {
                $affixGroup = $activeSeason->getCurrentAffixGroup();
            }
        }

        if ($affixGroup !== null) {
            if ($save) {
                // Something we can save to the database
                DungeonRouteAffixGroup::create([
                    'affix_group_id'   => $affixGroup->id,
                    'dungeon_route_id' => $dungeonRoute->id,
                ]);
            } else {
                // Something we can just return and have the user read
                $dungeonRoute->affixes->push($affixGroup);
            }

            // Apply the seasonal index to the route
            $dungeonRoute->seasonal_index = $affixGroup->seasonal_index;
        }

        // Update seasonal index
        if ($save) {
            $dungeonRoute->save();
        }

        // Create a path and map icons for MDT rift offsets
        $this->parseRiftOffsets($warnings, $decoded, $dungeonRoute, $save);

        // Create killzones and attach enemies
        $this->parseValuePulls($warnings, $decoded, $dungeonRoute, $save);

        // For each object the user created
        $this->parseObjects($warnings, $decoded, $dungeonRoute, $save);

        // Re-calculate the enemy forces
        if ($save) {
            $dungeonRoute->update(['enemy_forces' => $dungeonRoute->getEnemyForces()]);
        }
        // else {
        // Do not do this - the enemy_forces are incremented while creating the route
        // $dungeonRoute->enemy_forces = $dungeonRoute->getEnemyForces();
        // }

        return $dungeonRoute;
    }

    /**
     * Sets the encoded string to be staged for translation to a DungeonRoute.
     *
     * @param $encodedString string The MDT encoded string.
     * @return $this
     */
    public function setEncodedString(string $encodedString): self
    {
        $this->encodedString = $encodedString;

        return $this;
    }
}
