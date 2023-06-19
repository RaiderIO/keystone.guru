<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Logic\Utils\MathUtils;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Path;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Models\PublishedState;
use App\Service\MDT\Models\ImportStringDetails;
use App\Service\MDT\Models\ImportStringObjects;
use App\Service\MDT\Models\ImportStringPulls;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Class MDTImportStringService
 *
 * @package App\Service\MDT
 * @author Wouter
 * @since 09/11/2022
 */
class MDTImportStringService extends MDTBaseService implements MDTImportStringServiceInterface
{
    /** @var int */
    private const IMPORT_NOTE_AS_KILL_ZONE_DESCRIPTION_DISTANCE = 5;

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
     * @param Dungeon $dungeon
     * @param bool $importAsThisWeek
     *
     * @return AffixGroup|null
     * @throws Exception
     */
    private function parseAffixes(Collection $warnings, array $decoded, Dungeon $dungeon, bool $importAsThisWeek = false): ?AffixGroup
    {
        $affixGroup = Conversion::convertWeekToAffixGroup($this->seasonService, $dungeon, $decoded['week']);

        // If affix group not found or
        if ($importAsThisWeek || $affixGroup === null) {
            $activeSeason = $dungeon->getActiveSeason($this->seasonService);
            if ($activeSeason !== null) {
                $affixGroup = $activeSeason->getCurrentAffixGroup();
            }
        }

        return $affixGroup;
    }

    /**
     * @param Collection $warnings
     * @param array $decoded
     * @param DungeonRoute $dungeonRoute
     * @param boolean $save
     *
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
                        __('logic.mdt.io.import_string.unable_to_find_awakened_obelisks')
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
                                __('logic.mdt.io.import_string.unable_to_find_awakened_obelisk_different_floor',
                                    ['name' => __($obeliskMapIcon->mapicontype->name)])
                            );
                        }

                        $mapIconEnd = new MapIcon(array_merge([
                            'mapping_version_id' => null,
                            'floor_id'           => $enemy->floor_id,
                            'dungeon_route_id'   => $dungeonRoute->id,
                            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY],
                            'comment'            => __($obeliskMapIcon->mapicontype->name),
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
     * Parse the $decoded['value']['pulls'] value and save the result in the ImportStringPulls object.
     *
     * @param ImportStringPulls $importStringPulls
     *
     * @return ImportStringPulls
     * @throws InvalidMDTDungeonException
     * @throws InvalidArgumentException
     */
    private function parseValuePulls(
        ImportStringPulls $importStringPulls
    ): ImportStringPulls
    {
        $floors = $importStringPulls->getDungeon()->floors;
        /** @var Collection|Enemy[] $enemies */
        $enemies = $importStringPulls->getMappingVersion()->enemies->each(function (Enemy $enemy) {
            $enemy->npc_id = $enemy->mdt_npc_id ?? $enemy->npc_id;
        });

        // Keep a list of prideful enemies to assign
        //        $pridefulEnemies    = $enemies->where('npc_id', config('keystoneguru.prideful.npc_id'));
        //        $pridefulEnemyCount = config('keystoneguru.prideful.count');
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $enemiesByNpcId      = $enemies->groupBy('npc_id');
        $enemyForcesByNpcIds = NpcEnemyForces::where('mapping_version_id', $importStringPulls->getMappingVersion()->id)->get()->keyBy('npc_id');

        // Fetch all enemies of this dungeon
        $mdtEnemies = (new MDTDungeon($importStringPulls->getDungeon()))->getClonesAsEnemies($floors);
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $mdtEnemiesByMdtNpcIndex = $mdtEnemies->groupBy('mdt_npc_index');

        // Required for calculating when to add prideful enemies
        //        $enemyForcesRequired = $importStringPulls->isRouteTeeming() ?
        //            $importStringPulls->getMappingVersion()->enemy_forces_required_teeming :
        //            $importStringPulls->getMappingVersion()->enemy_forces_required;

        // For each pull the user created
        $newPullIndex = 1;
        foreach ($importStringPulls->getMdtPulls() as $pullIndex => $pull) {
            // Keep track of KillZone attributes
            $killZoneAttributes = [
                'index'           => $newPullIndex,
                'killZoneEnemies' => [],
            ];

            // The amount of enemies selected in MDT pull
            $totalEnemiesSelected = 0;
            // The amount of enemies that we actually matched with
            $totalEnemiesMatched = 0;
            // Keeps track of the amount of prideful enemies to add, a pull can in theory require us to add multiple
            // But mostly since we add them in the center in the pack, we need to know all coordinates of the pack enemies
            // first before we can place the prideful enemies
            //            $totalPridefulEnemiesToAdd = 0;

            try {
                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $pullKey => $pullValue) {
                    $this->parsePull(
                        $importStringPulls,
                        $mdtEnemiesByMdtNpcIndex,
                        $enemiesByNpcId,
                        $enemyForcesByNpcIds,
                        $totalEnemiesSelected,
                        $totalEnemiesMatched,
                        $killZoneAttributes,
                        $newPullIndex,
                        $pullKey,
                        $pullValue
                    );
                }

                // Save the attributes of this killzone
                $importStringPulls->addKillZoneAttributes($killZoneAttributes);

                $newPullIndex++;
            } catch (ImportWarning $warning) {
                $importStringPulls->getWarnings()->push($warning);
            }
        }

        return $importStringPulls;
    }

    /**
     * @param ImportStringPulls $importStringPulls
     * @param Collection $mdtEnemiesByMdtNpcIndex
     * @param Collection $enemiesByNpcId
     * @param Collection $enemyForcesByNpcIds
     * @param int $totalEnemiesSelected
     * @param int $totalEnemiesMatched
     * @param array $killZoneAttributes
     * @param int $newPullIndex
     * @param string $pullKey
     * @param string|array $pullValue
     *
     * @return bool
     * @throws \App\Logic\MDT\Exception\ImportWarning
     */
    private function parsePull(
        ImportStringPulls $importStringPulls,
        Collection        $mdtEnemiesByMdtNpcIndex,
        Collection        $enemiesByNpcId,
        Collection        $enemyForcesByNpcIds,
        int               &$totalEnemiesSelected,
        int               &$totalEnemiesMatched,
        array             &$killZoneAttributes,
        int               $newPullIndex,
        string            $pullKey,
                          $pullValue
    ): bool
    {
        if ($pullKey === 'color') {
            // Make sure there is a pound sign in front of the value at all times, but never double up should
            // MDT decide to suddenly place it here
            $killZoneAttributes['color'] = (substr($pullValue, 0, 1) !== '#' ? '#' : '') . $pullValue;

            return false;
        } // Numeric means it's an index of the dungeon's NPCs, if it isn't numeric skip to the next pull
        else if (!is_numeric($pullKey)) {
            return false;
        }

        $seasonalIndexSkip = false;
        $npcIndex          = (int)$pullKey;
        $mdtClones         = $pullValue;

        $totalEnemiesSelected += count($mdtClones);
        // Only if filled
        foreach ($mdtClones as $index => $cloneIndex) {
            // This comes in through as a double, cast to int
            $cloneIndex = (int)$cloneIndex;

            // Hacky fix for a MDT bug where there's duplicate NPCs with the same npc_id etc.
            if ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_SIEGE_OF_BORALUS) {
                if ($npcIndex === 35) {
                    $cloneIndex += 15;
                }
            } else if ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_TOL_DAGOR) {
                if ($npcIndex === 11) {
                    $cloneIndex += 2;
                }
            } else if ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE) {
                if ($npcIndex === 23) {
                    $cloneIndex += 5;
                }
            }

            // Find the matching enemy of the clones
            /** @var Enemy $mdtEnemy */
            $mdtEnemy   = null;
            $isEmissary = false;
            if ($mdtEnemiesByMdtNpcIndex->has($npcIndex)) {
                foreach ($mdtEnemiesByMdtNpcIndex->get($npcIndex) as $mdtEnemyCandidate) {
                    // Skip Emissaries (Season 3), season is over
                    if ($isEmissary = in_array($mdtEnemyCandidate->npc_id, [155432, 155433, 155434])) {
                        break;
                    }

                    /** @var $mdtEnemyCandidate Enemy */
                    if ($mdtEnemyCandidate->mdt_id === $cloneIndex) {
                        // Found it
                        $mdtEnemy = $mdtEnemyCandidate;

                        break;
                    }
                }
            }

            // No matching MDT enemy found - skip to the next enemy
            if ($mdtEnemy === null) {
                if (!$isEmissary) {
                    $importStringPulls->getWarnings()->push(new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                        sprintf(__('logic.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index'), $cloneIndex, $npcIndex),
                        ['details' => __('logic.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index_details')]
                    ));
                }
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
                    $importStringPulls->getWarnings()->push(new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                        sprintf(__('logic.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy'), $mdtEnemy->mdt_id, $mdtEnemy->npc->name,
                            $mdtEnemy->npc_id),
                        ['details' => __('logic.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy_details')]
                    ));
                }
                continue;
            }

            // Don't add any teeming enemies
            if (!$importStringPulls->isRouteTeeming() && $enemy->teeming === Enemy::TEEMING_VISIBLE) {
                continue;
            }

            // Skip mdt placeholders - we found it, great, but we don't show this on our mapping so get rid of it
            if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER) {
                continue;
            }

            // Skip enemies that don't belong to our current seasonal index
            if ($enemy->seasonal_index !== null && $enemy->seasonal_index !== $importStringPulls->getSeasonalIndex()) {
                $seasonalIndexSkip = true;
                continue;
            }

            $killZoneAttributes['killZoneEnemies'][] = [
                'npc_id' => $enemy->npc_id,
                'mdt_id' => $enemy->mdt_id,
                // Cache for the hasFinalBoss check below - it's slow otherwise
                'enemy'  => $enemy,
            ];

            // Keep track of our enemy forces
            if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED) {
                $importStringPulls->addEnemyForces($importStringPulls->getMappingVersion()->enemy_forces_shrouded);
            } else if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX) {
                $importStringPulls->addEnemyForces($importStringPulls->getMappingVersion()->enemy_forces_shrouded_zul_gamux);
            } else {
                /** @var NpcEnemyForces $npcEnemyForces */
                $npcEnemyForces = $enemyForcesByNpcIds->get($enemy->npc->id);

                $importStringPulls->addEnemyForces(
                    $importStringPulls->isRouteTeeming() ?
                        $npcEnemyForces->enemy_forces_teeming :
                        $npcEnemyForces->enemy_forces
                );
            }

            // <editor-fold desc="Prideful">
            //                        if ($importStringPulls->isRoutePrideful()) {
            //                            // Do not add more than 5 regardless of circumstance
            //                            if ($dungeonRoute->pridefulEnemies->count() + $totalPridefulEnemiesToAdd < $pridefulEnemyCount) {
            //                                // If we should add a prideful enemy in this pull ..
            //                                $currentPercentage = ($dungeonRoute->enemy_forces / $enemyForcesRequired) * 100;
            //                                // Add one so that we start adding at 20%
            //                                if ($currentPercentage >= ($dungeonRoute->pridefulEnemies->count() + $totalPridefulEnemiesToAdd + 1) * (100 / $pridefulEnemyCount)) {
            //                                    $totalPridefulEnemiesToAdd++;
            //                                }
            //                            }
            //                        }
            // </editor-fold>

            $totalEnemiesMatched++;
        }

        // <editor-fold desc="Prideful">
        // No point doing this if we're not saving
        //                    if ($save) {
        //                        for ($i = 0; $i < $totalPridefulEnemiesToAdd; $i++) {
        //                            /** @var Enemy $pridefulEnemyEnemy */
        //                            $pridefulEnemyEnemy = $pridefulEnemies->slice($dungeonRoute->pridefulEnemies->count(), 1)->first();
        //                            $pridefulEnemy      = PridefulEnemy::create([
        //                                'dungeon_route_id' => $dungeonRoute->id,
        //                                'enemy_id'         => $pridefulEnemyEnemy->id,
        //                                'floor_id'         => $killZoneEnemies->first()->floor_id,
        //                                'lat'              => $killZoneEnemies->avg('lat'),
        //                                'lng'              => $killZoneEnemies->avg('lng'),
        //                            ]);
        //
        //                            $dungeonRoute->pridefulEnemies->push($pridefulEnemy);
        //
        //                            // Couple the prideful enemy to this pull
        //                            KillZoneEnemy::create([
        //                                'kill_zone_id' => $killZoneAttributes->id,
        //                                'npc_id'       => $pridefulEnemyEnemy->npc_id,
        //                                'mdt_id'       => $pridefulEnemyEnemy->mdt_id,
        //                            ]);
        //                        }
        //                    }
        // </editor-fold>

        // <editor-fold desc="Awakened">
        // if ($totalEnemiesMatched > 0) {
        //                        // In order to import Awakened Bosses that are killed at the final boss, we need to identify if this
        //                        // pull contains the final boss, and if so, convert all its Awakened enemies to the correct enemies
        //                        // that are around the boss instead
        //                        $hasFinalBoss = false;
        //                        foreach ($killZoneAttributes['killZoneEnemies'] as $kzEnemy) {
        //                            if ($kzEnemy['enemy']->npc !== null &&
        //                                $kzEnemy['enemy']->npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS]) {
        //                                $hasFinalBoss = true;
        //                                break;
        //                            }
        //                        }
        //
        //                        if ($hasFinalBoss) {
        //                            foreach ($killZoneAttributes['killZoneEnemies'] as $kzEnemy) {
        //                                if ($kzEnemy['enemy']->npc !== null && $kzEnemy['enemy']->npc->isAwakened()) {
        //                                    // Find the equivalent Awakened Enemy that's next to the boss.
        //                                    /** @var Enemy $bossAwakenedEnemy */
        //                                    $bossAwakenedEnemy = Enemy::where('npc_id', $kzEnemy['npc_id'])
        //                                        ->where('mdt_id', $kzEnemy['mdt_id'])
        //                                        ->where('seasonal_index', $kzEnemy['enemy']->seasonal_index)
        //                                        ->whereNotNull('enemy_pack_id')
        //                                        ->first();
        //
        //                                    if ($bossAwakenedEnemy !== null) {
        //                                        $kzEnemy->enemy_id = $bossAwakenedEnemy->id;
        //                                        // Just to be sure
        //                                        $kzEnemy->unsetRelation('enemy');
        //
        //                                        if ($save) {
        //                                            $kzEnemy->save();
        //                                        }
        //                                    } else {
        //                                        throw new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
        //                                            sprintf(__('unable_to_find_awakened_enemy_for_final_boss'), $kzEnemy->enemy->npc_id, $kzEnemy->enemy->seasonal_index ?? -1, __($dungeonRoute->dungeon->name)),
        //                                            ['details' => __('logic.mdt.io.import_string.unable_to_find_awakened_enemy_for_final_boss_details')]
        //                                        );
        //                                    }
        //                                }
        //                            }
        //                        }
        // } else
        // </editor-fold>

        // Don't throw this warning if we skipped things because they were not part of the seasonal index we're importing
        // Also don't throw it if the pull is simply empty in MDT, then just import an empty pull for consistency
        if (!$seasonalIndexSkip && $totalEnemiesSelected > 0 && $totalEnemiesMatched === 0) {
            throw new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped'),
                ['details' => __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped_details')]
            );
        }

        return true;
    }

    /**
     * Parse any saved objects from the MDT string to a $dungeonRoute, optionally $save'ing the objects to the database.
     *
     * @param ImportStringObjects $importStringObjects
     *
     * @return ImportStringObjects
     */
    private function parseObjects(ImportStringObjects $importStringObjects): ImportStringObjects
    {
        $floors = $importStringObjects->getDungeon()->floors;

        foreach ($importStringObjects->getMdtObjects() as $objectIndex => $object) {
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

                // If not shown/visible, ignore it
                if (!$details[3]) {
                    continue;
                }

                // If it's a line
                // MethodDungeonTools.lua:2529
                if (isset($object['l'])) {
                    $this->parseObjectLine($importStringObjects, $floor, $details, $object['l']);
                }
                // Map comment (n = note)
                // MethodDungeonTools.lua:2523
                else if (isset($object['n']) && $object['n']) {
                    $this->parseObjectComment($importStringObjects, $floor, $details);
                }
                // Triangles (t = triangle)
                // MethodDungeonTools.lua:2554
                // else if (isset($object['t']) && $object['t']) {

                // }

            } catch (ImportWarning $warning) {
                $importStringObjects->getWarnings()->push($warning);
            }
        }

        return $importStringObjects;
    }

    /**
     * @param ImportStringObjects $importStringObjects
     * @param Floor $floor
     * @param array $details
     * @param array $line
     *
     * @return void
     */
    private function parseObjectLine(ImportStringObjects $importStringObjects, Floor $floor, array $details, array $line): void
    {
        $isFreeDrawn = isset($details[6]) && $details[6];

        $vertices  = [];
        $lineCount = count($line);
        for ($i = 0; $i < $lineCount; $i += 2) {
            $vertices[] = Conversion::convertMDTCoordinateToLatLng(['x' => doubleval($line[$i]), 'y' => doubleval($line[$i + 1])]);
        }

        $lineOrPathAttribute = [
            'floor_id' => $floor->id,
            'polyline' => [
                // Make sure there is a pound sign in front of the value at all times, but never double up should
                // MDT decide to suddenly place it here
                'color'         => (substr($details[4], 0, 1) !== '#' ? '#' : '') . $details[4],
                'weight'        => (int)$details[0],
                'vertices_json' => json_encode($vertices),
                // To be set later
                // 'model_id' => ?,
                'model_class'   => $isFreeDrawn ? Brushline::class : Path::class,
            ],
        ];

        if ($isFreeDrawn) {
            $importStringObjects->getLines()->push($lineOrPathAttribute);
        } else {
            $importStringObjects->getPaths()->push($lineOrPathAttribute);
        }
    }

    /**
     * @param ImportStringObjects $importStringObjects
     * @param Floor $floor
     * @param array $details
     *
     * @return void
     */
    private function parseObjectComment(ImportStringObjects $importStringObjects, Floor $floor, array $details): void
    {
        $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $details[0], 'y' => $details[1]]);

        $mapIconType  = MapIconType::MAP_ICON_TYPE_COMMENT;
        $commentLower = strtolower(trim($details[4]));
        if ($commentLower === 'heroism') {
            $mapIconType = MapIconType::MAP_ICON_TYPE_SPELL_HEROISM;
        } else if ($commentLower === 'bloodlust') {
            $mapIconType = MapIconType::MAP_ICON_TYPE_SPELL_BLOODLUST;
        } else {
            foreach ($importStringObjects->getKillZoneAttributes() as $killZoneIndex => $killZoneAttribute) {
                foreach ($killZoneAttribute['killZoneEnemies'] as $killZoneEnemy) {
                    if (MathUtils::distanceBetweenPoints(
                            $killZoneEnemy['enemy']->lat, $latLng['lat'],
                            $killZoneEnemy['enemy']->lng, $latLng['lng']
                        ) < self::IMPORT_NOTE_AS_KILL_ZONE_DESCRIPTION_DISTANCE) {
                        // Set description directly on the object
                        $importStringObjects->getKillZoneAttributes()->put(
                            $killZoneIndex,
                            array_merge($killZoneAttribute, ['description' => $details[4]])
                        );

                        // Map icon was assigned to killzone instead - return, we're done
                        return;
                    }
                }
            }
        }

        $importStringObjects->getMapIcons()->push([
            'mapping_version_id' => null,
            'floor_id'           => $floor->id,
            'map_icon_type_id'   => MapIconType::ALL[$mapIconType],
            'comment'            => $details[4],
            'lat'                => $latLng['lat'],
            'lng'                => $latLng['lng'],
        ]);
    }

    /**
     * Gets an array that represents the currently set MDT string.
     *
     * @return array|null
     */
    public function getDecoded(): ?array
    {
        return $this->decode($this->encodedString);
    }

    /**
     * @param Collection $warnings
     *
     * @return ImportStringDetails
     * @throws InvalidMDTString
     * @throws \Exception
     */
    public function getDetails(Collection $warnings): ImportStringDetails
    {
        $decoded = $this->decode($this->encodedString);
        // Check if it's valid
        $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTString('Unable to validate MDT import string in Lua');
        }

        $warnings = collect();

        $dungeon = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);

        /** @var AffixGroup|null $affixGroup */
        $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeon);

        $importStringPulls = $this->parseValuePulls(new ImportStringPulls(
            $warnings,
            $dungeon,
            $dungeon->getCurrentMappingVersion(),
            optional($affixGroup)->hasAffix(Affix::AFFIX_TEEMING),
            null,
            $decoded['value']['pulls']
        ));

        $importStringObjects = $this->parseObjects(new ImportStringObjects(
            $warnings,
            $dungeon,
            $importStringPulls->getKillZoneAttributes(),
            $decoded['objects']
        ));

        $currentSeason               = $this->seasonService->getCurrentSeason($dungeon->expansion);
        $currentAffixGroupForDungeon = optional($currentSeason)->getCurrentAffixGroup();

        return new ImportStringDetails(
            $warnings,
            $dungeon,
            collect([optional($affixGroup)->getTextAttribute() ?? '']),
            $affixGroup !== null && $currentAffixGroupForDungeon !== null &&
            $affixGroup->id === optional($currentAffixGroupForDungeon)->id,
            $importStringPulls->getKillZoneAttributes()->count(),
            $importStringObjects->getPaths()->count(),
            $importStringObjects->getLines()->count(),
            $importStringObjects->getMapIcons()->count(),
            $importStringPulls->getEnemyForces(),
            $importStringPulls->isRouteTeeming() ?
                $importStringPulls->getMappingVersion()->enemy_forces_required_teeming :
                $importStringPulls->getMappingVersion()->enemy_forces_required,
        );
    }

    /**
     * Gets the dungeon route based on the currently encoded string.
     *
     * @param  $warnings Collection Collection that is passed by reference in which any warnings are stored.
     * @param  $sandbox boolean True to mark the dungeon as a sandbox route which will be automatically deleted at a later stage.
     * @param  $save bool True to save the route and all associated models, false to not save & couple.
     * @param  $importAsThisWeek bool True to replace the imported affixes with this week's affixes instead
     *
     * @return DungeonRoute DungeonRoute if the route could be constructed
     * @throws InvalidMDTString
     * @throws Exception
     */
    public function getDungeonRoute(Collection $warnings, bool $sandbox = false, bool $save = false, bool $importAsThisWeek = false): DungeonRoute
    {
        $decoded = $this->decode($this->encodedString);
        // Check if it's valid
        $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTString('Unable to validate MDT import string in Lua');
        }

        $dungeon        = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Create a dungeon route
        $dungeonRoute                     = new DungeonRoute();
        $dungeonRoute->author_id          = $sandbox ? -1 : Auth::id();
        $dungeonRoute->dungeon_id         = $dungeon->id;
        $dungeonRoute->mapping_version_id = $mappingVersion->id;

        // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
        $dungeonRoute->faction_id         = isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1;
        $dungeonRoute->published_state_id = PublishedState::ALL[PublishedState::UNPUBLISHED]; // Needs to be explicit otherwise redirect to edit will not have this value
        $dungeonRoute->public_key         = DungeonRoute::generateRandomPublicKey();
        $dungeonRoute->teeming            = boolval($decoded['value']['teeming']);
        $dungeonRoute->title              = empty($decoded['text']) ? 'No title' : $decoded['text'];
        $dungeonRoute->difficulty         = 'Casual';
        $dungeonRoute->level_min          = $decoded['difficulty'] ?? 2;
        $dungeonRoute->level_max          = $decoded['difficulty'] ?? 2;

        // Must expire if we're trying
        if ($sandbox) {
            $dungeonRoute->expires_at = Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString();
        }

        // Ensure we have an ID at this point
        $dungeonRoute->save();

        // Set some relations here so we can reference them later
        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);

        // Set the affix for this route
        $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeonRoute->dungeon, $importAsThisWeek);

        $this->applyAffixGroupToDungeonRoute($affixGroup, $dungeonRoute);

        // Create a path and map icons for MDT rift offsets
        $this->parseRiftOffsets($warnings, $decoded, $dungeonRoute, $save);

        // Create killzones and attach enemies
        $importStringPulls = $this->parseValuePulls(new ImportStringPulls(
            $warnings,
            $dungeonRoute->dungeon,
            $dungeonRoute->mappingVersion,
            $dungeonRoute->teeming,
            $dungeonRoute->seasonal_index,
            $decoded['value']['pulls']
        ));

        // For each object the user created
        $importStringObjects = $this->parseObjects(new ImportStringObjects(
            $warnings,
            $dungeonRoute->dungeon,
            $importStringPulls->getKillZoneAttributes(),
            $decoded['objects']
        ));

        // Only after parsing objects too since they may adjust the pulls before inserting
        $this->applyPullsToDungeonRoute($importStringPulls, $dungeonRoute);

        $this->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute);

        return $dungeonRoute;
    }

    /**
     * @param ImportStringPulls $importStringPulls
     * @param DungeonRoute $dungeonRoute
     *
     * @return void
     */
    private function applyPullsToDungeonRoute(ImportStringPulls $importStringPulls, DungeonRoute $dungeonRoute)
    {
        $dungeonRoute->update(['enemy_forces' => $importStringPulls->getEnemyForces()]);

        $killZones       = [];
        $killZoneEnemies = [];
        foreach ($importStringPulls->getKillZoneAttributes() as $killZoneAttributes) {
            $killZones[]                                   = [
                'dungeon_route_id' => $dungeonRoute->id,
                'color'            => $killZoneAttributes['color'] ?? randomHexColor(),
                'description'      => $killZoneAttributes['description'] ?? null,
                'index'            => $killZoneAttributes['index'],
            ];
            $killZoneEnemies[$killZoneAttributes['index']] = $killZoneAttributes['killZoneEnemies'];
        }

        KillZone::insert($killZones);
        $dungeonRoute->load(['killZones']);

        // For each of the saved killzones, assign the enemies
        $flatKillZoneEnemies = [];
        foreach ($dungeonRoute->killZones as $killZone) {
            foreach ($killZoneEnemies[$killZone->index] as &$killZoneEnemy) {
                $killZoneEnemy['kill_zone_id'] = $killZone->id;
                unset($killZoneEnemy['enemy']);
                $flatKillZoneEnemies[] = $killZoneEnemy;
            }
        }

        KillZoneEnemy::insert($flatKillZoneEnemies);
    }

    /**
     * @param AffixGroup|null $affixGroup
     * @param DungeonRoute $dungeonRoute
     *
     * @return void
     */
    private function applyAffixGroupToDungeonRoute(?AffixGroup $affixGroup, DungeonRoute $dungeonRoute): void
    {
        if ($affixGroup === null) {
            return;
        }

        // Something we can save to the database
        DungeonRouteAffixGroup::create([
            'affix_group_id'   => $affixGroup->id,
            'dungeon_route_id' => $dungeonRoute->id,
        ]);

        // Apply the seasonal index to the route
        $dungeonRoute->update(['seasonal_index' => $affixGroup->seasonal_index]);
    }

    /**
     * @param \App\Service\MDT\Models\ImportStringObjects $importStringObjects
     * @param \App\Models\DungeonRoute $dungeonRoute
     * @return void
     */
    private function applyObjectsToDungeonRoute(ImportStringObjects $importStringObjects, DungeonRoute $dungeonRoute)
    {
        $now                 = now();
        $polyLinesAttributes = [];

        $brushLinesAttributes = [];
        foreach ($importStringObjects->getLines() as $line) {
            $brushLinesAttributes[] = [
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $line['floor_id'],
                'polyline_id'      => -1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $polyLinesAttributes[]  = $line['polyline'];
        }
        Brushline::insert($brushLinesAttributes);

        $pathsAttributes = [];
        foreach ($importStringObjects->getPaths() as $path) {
            $pathsAttributes[]     = [
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $path['floor_id'],
                'polyline_id'      => -1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $polyLinesAttributes[] = $path['polyline'];
        }
        Path::insert($pathsAttributes);

        // Load back the brushlines and paths so that we know the IDs
        $dungeonRoute->load(['brushlines', 'paths']);

        // Assign these IDs to their respective polylines
        $polyLineIndex = 0;
        foreach ($dungeonRoute->brushlines as $brushLine) {
            $polyLinesAttributes[$polyLineIndex]['model_id'] = $brushLine->id;

            $polyLineIndex++;
        }
        foreach ($dungeonRoute->paths as $path) {
            $polyLinesAttributes[$polyLineIndex]['model_id'] = $path->id;

            $polyLineIndex++;
        }

        Polyline::insert($polyLinesAttributes);

        // Assign the polylines back to the brushlines/paths
        $polyLines = Polyline::where(function (Builder $builder) use ($dungeonRoute) {
            $builder->where(function (Builder $builder) use ($dungeonRoute) {
                $builder->whereIn('model_id', $dungeonRoute->brushlines->pluck('id'))
                    ->where('model_class', Brushline::class);
            })->orWhere(function (Builder $builder) use ($dungeonRoute) {
                $builder->whereIn('model_id', $dungeonRoute->paths->pluck('id'))
                    ->where('model_class', Path::class);
            });
        })->orderBy('id')
            ->get('id');

        // Assign the polylines back to the brushlines/paths
        $polyLineIndex = 0;
        foreach ($dungeonRoute->brushlines as $brushLine) {
            $brushLine->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }
        foreach ($dungeonRoute->paths as $path) {
            $path->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }

        // Assign map objects to the route
        $mapIconsAttributes = [];
        foreach ($importStringObjects->getMapIcons() as $mapIcon) {
            $mapIconsAttributes[] = array_merge($mapIcon, [
                'dungeon_route_id' => $dungeonRoute->id,
            ]);
        }

        MapIcon::insert($mapIconsAttributes);
    }

    /**
     * Sets the encoded string to be staged for translation to a DungeonRoute.
     *
     * @param $encodedString string The MDT encoded string.
     *
     * @return $this
     */
    public function setEncodedString(string $encodedString): self
    {
        $this->encodedString = $encodedString;

        return $this;
    }
}
