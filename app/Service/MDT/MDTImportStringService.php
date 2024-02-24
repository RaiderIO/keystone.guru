<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Logic\MDT\Exception\MDTStringParseException;
use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Path;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Models\PublishedState;
use App\Models\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Models\ImportStringDetails;
use App\Service\MDT\Models\ImportStringObjects;
use App\Service\MDT\Models\ImportStringPulls;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** * Class MDTImportStringService
 *
 * @package App\Service\MDT
 * @author Wouter
 * @since 09/11/2022
 */
class MDTImportStringService extends MDTBaseService implements MDTImportStringServiceInterface
{
    /** @var int */
    private const IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS = 50;

    /** @var string $encodedString The MDT encoded string that's currently staged for conversion to a DungeonRoute. */
    private string $encodedString;

    function __construct(
        /** @var SeasonService Used for grabbing info about the current M+ season. */
        private readonly SeasonServiceInterface      $seasonService,
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService
    ) {
    }

    /**
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
     *
     * @return ImportStringRiftOffsets
     * @throws ImportWarning
     */
    private function parseRiftOffsets(ImportStringRiftOffsets $importStringRiftOffsets): ImportStringRiftOffsets
    {
        // Build an array with a structure that makes more sense
        $rifts = $importStringRiftOffsets->getRiftOffsets()[$importStringRiftOffsets->getWeek()] ?? [];

        if (empty($rifts)) {
            return $importStringRiftOffsets;
        }

        // Loaded for the comment import
        $floorIds = $importStringRiftOffsets->getDungeon()->floors->pluck('id');

        try {
            $seasonalIndexWhere = function (Builder $query) use ($importStringRiftOffsets) {
                $query->whereNull('seasonal_index')
                    ->orWhere('seasonal_index', $importStringRiftOffsets->getSeasonalIndex() ?? 1);
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

        } catch (Exception) {
            throw new ImportWarning(__('logic.mdt.io.import_string.category.awakened_obelisks'),
                __('logic.mdt.io.import_string.unable_to_find_awakened_obelisks')
            );
        }

        // From the built array, construct our map icons / paths
        foreach ($rifts as $npcId => $mdtXy) {
            try {
                // Find out the floor where the NPC is standing on
                /** @var Enemy $enemy */
                $enemy = Enemy::where('npc_id', $npcId)
                    ->where('mapping_version_id', $importStringRiftOffsets->getMappingVersion()->id)
                    ->whereNotNull('enemy_pack_id')
                    ->whereIn('floor_id', $floorIds)
                    ->firstOrFail();

                /** @var MapIcon $obeliskMapIcon */
                $obeliskMapIcon = $npcIdToMapIconMapping[$npcId];

                if (isset($mdtXy['sublevel'])) {
                    throw new ImportWarning(__('logic.mdt.io.import_string.category.awakened_obelisks'),
                        __('logic.mdt.io.import_string.unable_to_find_awakened_obelisk_different_floor',
                            ['name' => __($obeliskMapIcon->mapicontype->name)])
                    );
                }

                $mapIconEndAttributes = array_merge([
                    'mapping_version_id' => null,
                    'floor_id'           => $enemy->floor_id,
                    'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY],
                    'comment'            => __($obeliskMapIcon->mapicontype->name),
                    'obelisk_map_icon'   => $obeliskMapIcon,
                    // MDT has the x and y inverted here
                ], Conversion::convertMDTCoordinateToLatLng(['x' => $mdtXy['x'], 'y' => $mdtXy['y']], $enemy->floor)->toArray());

                $hasAnimatedLines = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES);

                $pathAttributes = [
                    'floor_id' => $enemy->floor_id,
                    'polyline' => [
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
                                'lat' => $mapIconEndAttributes['lat'],
                                'lng' => $mapIconEndAttributes['lng'],
                            ],
                        ]),
                    ],
                ];

                $importStringRiftOffsets->getMapIcons()->push($mapIconEndAttributes);

                $importStringRiftOffsets->getPaths()->push($pathAttributes);

            } catch (ImportWarning $warning) {
                $importStringRiftOffsets->getWarnings()->add($warning);
            }
        }

        return $importStringRiftOffsets;
    }

    /**
     * Parse the $decoded['value']['pulls'] value and save the result in the ImportStringPulls object.
     *
     *
     * @return ImportStringPulls
     * @throws InvalidMDTDungeonException
     * @throws InvalidArgumentException
     */
    private function parseValuePulls(
        ImportStringPulls $importStringPulls
    ): ImportStringPulls {
        if (count($importStringPulls->getMdtPulls()) > config('keystoneguru.dungeon_route_limits.kill_zones')) {
            $importStringPulls->getErrors()->push(
                new ImportError(
                    __('logic.mdt.io.import_string.category.pulls'),
                    __('logic.mdt.io.import_string.limit_reached_pulls', ['limit' => config('keystoneguru.dungeon_route_limits.kill_zones')])
                )
            );
        }

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
        $mdtEnemies = (new MDTDungeon($this->cacheService, $this->coordinatesService, $importStringPulls->getDungeon()))
            ->getClonesAsEnemies($importStringPulls->getMappingVersion(), $floors);
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
                'spells'          => [],
            ];

            // The amount of enemies selected in MDT pull
            $totalEnemiesSelected = 0;
            // The amount of enemies that we actually matched with
            $totalEnemiesMatched = 0;
            // If the pull was empty because all enemies in it were skipped based on seasonal index
            $seasonalIndexSkip = false;
            // Keeps track of the amount of prideful enemies to add, a pull can in theory require us to add multiple
            // But mostly since we add them in the center in the pack, we need to know all coordinates of the pack enemies
            // first before we can place the prideful enemies
            //            $totalPridefulEnemiesToAdd = 0;

            try {
                // For each NPC that is killed in this pull (and their clones)
                foreach ($pull as $mdtNpcIndex => $mdtClones) {
                    $this->parseMdtNpcClonesInPull(
                        $importStringPulls,
                        $mdtEnemiesByMdtNpcIndex,
                        $enemiesByNpcId,
                        $enemyForcesByNpcIds,
                        $totalEnemiesSelected,
                        $totalEnemiesMatched,
                        $seasonalIndexSkip,
                        $killZoneAttributes,
                        $newPullIndex,
                        $mdtNpcIndex,
                        $mdtClones
                    );
                }

                // If the pull never contained any enemies at all, completely skip it
                if ($totalEnemiesSelected === 0) {
                    continue;
                }

                // Don't throw this warning if we skipped things because they were not part of the seasonal index we're importing
                // Also don't throw it if the pull is simply empty in MDT, then just import an empty pull for consistency
                if (!$seasonalIndexSkip && $totalEnemiesMatched === 0) {
                    throw new ImportWarning(sprintf(__('logic.mdt.io.import_string.category.pull'), $newPullIndex),
                        __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped'),
                        ['details' => __('logic.mdt.io.import_string.unable_to_find_enemies_pull_skipped_details')]
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
     * @param string|array $mdtNpcClones
     *
     * @return bool
     */
    private function parseMdtNpcClonesInPull(
        ImportStringPulls $importStringPulls,
        Collection        $mdtEnemiesByMdtNpcIndex,
        Collection        $enemiesByNpcId,
        Collection        $enemyForcesByNpcIds,
        int               &$totalEnemiesSelected,
        int               &$totalEnemiesMatched,
        bool              &$seasonalIndexSkip,
        array             &$killZoneAttributes,
        int               $newPullIndex,
        string            $mdtNpcIndex,
                          $mdtNpcClones
    ): bool {
        if ($mdtNpcIndex === 'color') {
            // Make sure there is a pound sign in front of the value at all times, but never double up should
            // MDT decide to suddenly place it here
            $killZoneAttributes['color'] = (!str_starts_with($mdtNpcClones, '#') ? '#' : '') . $mdtNpcClones;

            return false;
        } // Numeric means it's an index of the dungeon's NPCs, if it isn't numeric skip to the next pull
        else if (!is_numeric($mdtNpcIndex)) {
            return false;
        }

        $seasonalIndexSkip = false;
        $npcIndex          = (int)$mdtNpcIndex;
        $mdtClones         = $mdtNpcClones;

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

                if ($npcEnemyForces !== null) {
                    $importStringPulls->addEnemyForces(
                        $importStringPulls->isRouteTeeming() ?
                            $npcEnemyForces->enemy_forces_teeming :
                            $npcEnemyForces->enemy_forces
                    );
                } else {
                    logger()->warning(sprintf('Unable to find enemy forces for npc %d!', $enemy->npc->id));
                }
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

        return true;
    }

    /**
     * Parse any saved objects from the MDT string to a $dungeonRoute, optionally $save'ing the objects to the database.
     *
     *
     * @return ImportStringObjects
     */
    private function parseObjects(ImportStringObjects $importStringObjects): ImportStringObjects
    {
        if (count($importStringObjects->getMdtObjects()) > config('keystoneguru.dungeon_route_limits.map_icons')) {
            $importStringObjects->getErrors()->push(
                new ImportError(
                    __('logic.mdt.io.import_string.category.notes'),
                    __('logic.mdt.io.import_string.limit_reached_notes', ['limit' => config('keystoneguru.dungeon_route_limits.map_icons')])
                )
            );

            return $importStringObjects;
        }

        $mappingVersion = $importStringObjects->getDungeon()->currentMappingVersion;

        $floors = $importStringObjects->getDungeon()->floorsForMapFacade(
            $importStringObjects->getDungeon()->facade_enabled
        )->get();

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
                 *
                 * Triangle
                 * 1 = rotation (rad)
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
                $floor = $floors->first(fn(Floor $floor) => ($floor->mdt_sub_level ?? $floor->index) === $mdtSubLevel);

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

                // Triangles (t = triangle)
                // MethodDungeonTools.lua:2554
                if (isset($object['t']) && $object['t']) {
                    $this->parseObjectTriangle($importStringObjects, $mappingVersion, $floor, $details, $object['l'], $object['t'][0]);
                }
                // If it's a line
                // MethodDungeonTools.lua:2529
                else if (isset($object['l'])) {
                    $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, $object['l']);
                }
                // Map comment (n = note)
                // MethodDungeonTools.lua:2523
                else if (isset($object['n']) && $object['n']) {
                    $this->parseObjectComment($importStringObjects, $mappingVersion, $floor, $details);
                }

            } catch (ImportWarning $warning) {
                $importStringObjects->getWarnings()->push($warning);
            }
        }

        return $importStringObjects;
    }

    /**
     * @return void
     */
    private function parseObjectTriangle(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        array               $line,
        float               $rotationRad): void
    {
        // Don't parse as paths, but as free-drawn instead
        $details[6] = true;

        // Create the main line
        $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, $line);

        // Second to last and last point
        $lastPoint    = [
            $line[count($line) - 2],
            last($line),
        ];
        $lastPoint[0] = (float)$lastPoint[0];
        $lastPoint[1] = (float)$lastPoint[1];


        $lastPointLatLng = new LatLng($lastPoint[0], $lastPoint[1], $floor);
        // Create the left part of the arrow
        $leftPartLatLng = (new LatLng($lastPointLatLng->getLat() + 5, $lastPointLatLng->getLng() + 5, $floor))->rotate(
            $lastPointLatLng,
            rad2deg($rotationRad)
        );

        $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, array_merge(
            $lastPoint,
            [$leftPartLatLng->getLat(), $leftPartLatLng->getLng()]
        ));

        // Create the right part of the arrow
        $rightPartLatLng = (new LatLng($lastPointLatLng->getLat() + 5, $lastPointLatLng->getLng() - 5, $floor))->rotate(
            $lastPointLatLng,
            rad2deg($rotationRad)
        );
        $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, array_merge(
            $lastPoint,
            [$rightPartLatLng->getLat(), $rightPartLatLng->getLng()],
        ));
    }

    /**
     *
     * @return void
     */
    private function parseObjectLine(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        array               $line): void
    {
        $isFreeDrawn = isset($details[6]) && $details[6];

        $vertices      = [];
        $lineCount     = count($line);
        $dominantFloor = null;

        for ($i = 0; $i < $lineCount; $i += 2) {
            $latLng = Conversion::convertMDTCoordinateToLatLng(
                ['x' => doubleval($line[$i]), 'y' => doubleval($line[$i + 1])],
                $floor
            );

            if ($floor->facade) {
                $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                    $mappingVersion,
                    $latLng,
                    $dominantFloor
                );

                // Attempt to set the dominant floor, or fall back to what was set before
                $dominantFloor ??= $latLng->getFloor();
            }

            $vertices[] = $latLng->toArray();
        }

        // Between 1 and 5
        $weight = min(5, max(1, (int)$details[0]));

        $lineOrPathAttribute = [
            'floor_id' => ($dominantFloor ?? $floor)->id,
            'polyline' => [
                // Make sure there is a pound sign in front of the value at all times, but never double up should
                // MDT decide to suddenly place it here
                'color'         => (!str_starts_with((string) $details[4], '#') ? '#' : '') . $details[4],
                'weight'        => $weight,
                'vertices_json' => json_encode($vertices),
                // To be set later
                // 'model_id' => ?,
                'model_class'   => $isFreeDrawn ? Brushline::class : Path::class,
            ],
        ];

        if ($isFreeDrawn) {
            $importStringObjects->getLines()->push($lineOrPathAttribute);

            if ($importStringObjects->getLines()->count() > config('keystoneguru.dungeon_route_limits.brushlines')) {
                $importStringObjects->getErrors()->push(
                    new ImportError(
                        __('logic.mdt.io.import_string.category.brushlines'),
                        __('logic.mdt.io.import_string.limit_reached_brushlines', ['limit' => config('keystoneguru.dungeon_route_limits.brushlines')])
                    )
                );
            }
        } else {
            $importStringObjects->getPaths()->push($lineOrPathAttribute);

            if ($importStringObjects->getPaths()->count() > config('keystoneguru.dungeon_route_limits.paths')) {
                $importStringObjects->getErrors()->push(
                    new ImportError(
                        __('logic.mdt.io.import_string.category.paths'),
                        __('logic.mdt.io.import_string.limit_reached_paths', ['limit' => config('keystoneguru.dungeon_route_limits.paths')])
                    )
                );
            }
        }
    }

    /**
     *
     * @return void
     */
    private function parseObjectComment(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details): void
    {
        $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $details[0], 'y' => $details[1]], $floor);

        if ($floor->facade) {
            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                $mappingVersion,
                $latLng
            );
        }

        $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($latLng);

        // Try to see if we can import this comment and apply it to our pulls directly instead
        foreach ($importStringObjects->getKillZoneAttributes() as $killZoneIndex => $killZoneAttribute) {
            foreach ($killZoneAttribute['killZoneEnemies'] as $killZoneEnemy) {
                $enemyIngameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    new LatLng($killZoneEnemy['enemy']->lat, $killZoneEnemy['enemy']->lng, $floor)
                );
                if ($this->coordinatesService->distanceBetweenPoints(
                        $enemyIngameXY->getX(), $ingameXY->getX(),
                        $enemyIngameXY->getY(), $ingameXY->getY()
                    ) < self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS) {

                    $bloodLustNames = ['bloodlust', 'heroism', 'fury of the ancients', 'time warp', 'timewarp', 'ancient hysteria'];

                    // If the user wants to put heroism/bloodlust on this pull, directly assign it instead
                    $commentLower = strtolower(trim((string) $details[4]));
                    if (in_array($commentLower, $bloodLustNames)) {
                        $spellId = 0;

                        if ($commentLower === 'bloodlust') {
                            $spellId = Spell::SPELL_BLOODLUST;
                        } else if ($commentLower === 'heroism') {
                            $spellId = Spell::SPELL_HEROISM;
                        } else if ($commentLower === 'fury of the aspects') {
                            $spellId = Spell::SPELL_FURY_OF_THE_ASPECTS;
                        } else if ($commentLower === 'time warp' || $commentLower === 'timewarp') {
                            $spellId = Spell::SPELL_TIME_WARP;
                        } else if ($commentLower === 'ancient hysteria') {
                            $spellId = Spell::SPELL_ANCIENT_HYSTERIA;
                        } else if ($commentLower === 'drums') {
                            $spellId = Spell::SPELL_FERAL_HIDE_DRUMS;
                        } else if ($commentLower === 'primal rage') {
                            $spellId = Spell::SPELL_PRIMAL_RAGE;
                        }

                        $newAttributes = $killZoneAttribute['spells'][] = [
                            'spell_id' => $spellId,
                        ];
                    } else {
                        // Add it as a comment instead
                        $newAttributes = ['description' => $details[4]];
                    }

                    // Set description directly on the object
                    $importStringObjects->getKillZoneAttributes()->put(
                        $killZoneIndex,
                        array_merge($killZoneAttribute, $newAttributes)
                    );

                    // Map icon was assigned to killzone instead - return, we're done
                    return;
                }
            }
        }

        $importStringObjects->getMapIcons()->push(array_merge([
            'mapping_version_id' => null,
            'floor_id'           => $latLng->getFloor()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            'comment'            => $details[4],
        ], $latLng->toArray()));
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
     * @param Collection $errors
     * @return ImportStringDetails
     * @throws InvalidMDTDungeonException
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     */
    public function getDetails(Collection $warnings, Collection $errors): ImportStringDetails
    {
        $decoded = $this->decode($this->encodedString);

        if ($decoded === null) {
            throw new MDTStringParseException('Unable to decode MDT import string');
        }

        // Check if it's valid
        $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTStringException('Unable to validate MDT import string in Lua');
        }

        $warnings = collect();
        $errors   = collect();

        $dungeon = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);

        /** @var AffixGroup|null $affixGroup */
        $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeon);

        $importStringPulls = $this->parseValuePulls(new ImportStringPulls(
            $warnings,
            $errors,
            $dungeon,
            $dungeon->currentMappingVersion,
            optional($affixGroup)->hasAffix(Affix::AFFIX_TEEMING) ?? false,
            null,
            $decoded['value']['pulls']
        ));

        $importStringObjects = $this->parseObjects(new ImportStringObjects(
            $warnings,
            $errors,
            $dungeon,
            $importStringPulls->getKillZoneAttributes(),
            $decoded['objects']
        ));

        $currentSeason               = $this->seasonService->getCurrentSeason($dungeon->expansion);
        $currentAffixGroupForDungeon = optional($currentSeason)->getCurrentAffixGroup();

        return new ImportStringDetails(
            $warnings,
            $errors,
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
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     * @throws Exception
     */
    public function getDungeonRoute(
        Collection $warnings,
        Collection $errors,
        bool       $sandbox = false,
        bool       $save = false,
        bool       $importAsThisWeek = false
    ): DungeonRoute {
        $decoded = $this->decode($this->encodedString);

        if ($decoded === null) {
            throw new MDTStringParseException('Unable to decode MDT import string');
        }

        // Check if it's valid
        $isValid = $this->getLua()->call('ValidateImportPreset', [$decoded]);

        if (!$isValid) {
            throw new InvalidMDTStringException('Unable to validate MDT import string in Lua');
        }

        $dungeon        = Conversion::convertMDTDungeonIDToDungeon($decoded['value']['currentDungeonIdx']);
        $mappingVersion = $dungeon->currentMappingVersion;

        // Create a dungeon route
        $titleSlug    = Str::slug($decoded['text']);
        $dungeonRoute = DungeonRoute::create([
            'author_id'          => $sandbox ? -1 : Auth::id() ?? -1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,

            // Undefined if not defined, otherwise 1 = horde, 2 = alliance (and default if out of range)
            'faction_id'         => isset($decoded['faction']) ? ((int)$decoded['faction'] === 1 ? 2 : 3) : 1,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            // Needs to be explicit otherwise redirect to edit will not have this value
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'teeming'            => boolval($decoded['value']['teeming']),
            'title'              => empty($titleSlug) ? __($dungeon->name, [], 'en-US') : $titleSlug,
            'difficulty'         => 'Casual',
            'level_min'          => $decoded['difficulty'] ?? 2,
            'level_max'          => $decoded['difficulty'] ?? 2,
            'expires_at'         => $sandbox ? Carbon::now()->addHours(config('keystoneguru.sandbox_dungeon_route_expires_hours'))->toDateTimeString() : null,
        ]);

        // Set some relations here so we can reference them later
        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);

        // Set the affix for this route
        $affixGroup = $this->parseAffixes($warnings, $decoded, $dungeonRoute->dungeon, $importAsThisWeek);

        $this->applyAffixGroupToDungeonRoute($affixGroup, $dungeonRoute);

        // Create a path and map icons for MDT rift offsets
        if (isset($decoded['value']['riftOffsets'])) {
            $importStringRiftOffsets = $this->parseRiftOffsets(new ImportStringRiftOffsets(
                $warnings,
                $dungeon,
                $mappingVersion,
                $dungeonRoute->seasonal_index,
                $decoded['value']['riftOffsets'],
                $decoded['week'],
            ));

            $this->applyRiftOffsetsToDungeonRoute($importStringRiftOffsets, $dungeonRoute);
        }

        // Create killzones and attach enemies
        $importStringPulls = $this->parseValuePulls(new ImportStringPulls(
            $warnings,
            $errors,
            $dungeonRoute->dungeon,
            $dungeonRoute->mappingVersion,
            $dungeonRoute->teeming,
            $dungeonRoute->seasonal_index,
            $decoded['value']['pulls']
        ));

        // For each object the user created
        $importStringObjects = $this->parseObjects(new ImportStringObjects(
            $warnings,
            $errors,
            $dungeonRoute->dungeon,
            $importStringPulls->getKillZoneAttributes(),
            $decoded['objects']
        ));

        if ($errors->isNotEmpty()) {
            // Get rid of it again!
            $dungeonRoute->delete();

            throw new InvalidMDTStringException('Unable to MDT import string - there have been errors converting your string to a route');
        } else {
            // Only after parsing objects too since they may adjust the pulls before inserting
            $this->applyPullsToDungeonRoute($importStringPulls, $dungeonRoute);

            $this->applyObjectsToDungeonRoute($importStringObjects, $dungeonRoute);
        }

        return $dungeonRoute;
    }

    /**
     *
     * @return void
     */
    private function applyPullsToDungeonRoute(ImportStringPulls $importStringPulls, DungeonRoute $dungeonRoute)
    {
        $dungeonRoute->update(['enemy_forces' => $importStringPulls->getEnemyForces()]);

        $killZones       = [];
        $killZoneEnemies = [];
        $killZoneSpells  = [];
        foreach ($importStringPulls->getKillZoneAttributes() as $killZoneAttributes) {
            $killZones[]                                   = [
                'dungeon_route_id' => $dungeonRoute->id,
                'color'            => $killZoneAttributes['color'] ?? randomHexColorNoMapColors(),
                'description'      => $killZoneAttributes['description'] ?? null,
                'index'            => $killZoneAttributes['index'],
            ];
            $killZoneEnemies[$killZoneAttributes['index']] = $killZoneAttributes['killZoneEnemies'];
            $killZoneSpells[$killZoneAttributes['index']]  = $killZoneAttributes['spells'];
        }

        KillZone::insert($killZones);
        $dungeonRoute->load(['killZones']);

        // For each of the saved killzones, assign the enemies
        $flatKillZoneEnemies = [];
        foreach ($dungeonRoute->killZones as $killZone) {
            foreach ($killZoneEnemies[$killZone->index] as $killZoneEnemy) {
                $killZoneEnemy['kill_zone_id'] = $killZone->id;
                unset($killZoneEnemy['enemy']);
                $flatKillZoneEnemies[] = $killZoneEnemy;
            }
        }

        KillZoneEnemy::insert($flatKillZoneEnemies);

        // For each of the saved spells, assign the enemies
        $flatKillZoneSpells = [];
        foreach ($dungeonRoute->killZones as $killZone) {
            foreach ($killZoneSpells[$killZone->index] as $killZoneSpell) {
                $killZoneSpell['kill_zone_id'] = $killZone->id;
                $flatKillZoneSpells[]          = $killZoneSpell;
            }
        }

        KillZoneSpell::insert($flatKillZoneSpells);
    }

    /**
     * @param AffixGroup|null $affixGroup
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
     *
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
     *
     * @return void
     */
    private function applyRiftOffsetsToDungeonRoute(ImportStringRiftOffsets $importStringRiftOffsets, DungeonRoute $dungeonRoute)
    {
        $now = now();

        // Assign map objects to the route
        $mapIconsAttributes = [];
        foreach ($importStringRiftOffsets->getMapIcons() as $mapIcon) {
            $mapIconsAttributes[] = array_merge($mapIcon, [
                'dungeon_route_id'   => $dungeonRoute->id,
                'mapping_version_id' => $mapIcon['mapping_version_id'],
                'floor_id'           => $mapIcon['floor_id'],
                'map_icon_type_id'   => $mapIcon['map_icon_type_id'],
                'comment'            => $mapIcon['comment'],
            ]);
        }

        MapIcon::insert($mapIconsAttributes);

        $polyLinesAttributes = [];

        $pathsAttributes = [];
        foreach ($importStringRiftOffsets->getPaths() as $path) {
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

        // Get only the paths that have no assigned polyline for this route
        $polyLineIndex = 0;
        $paths         = $dungeonRoute->paths()
            ->where('polyline_id', -1)
            ->orderBy('id')
            ->get();

        foreach ($paths as $path) {
            /** @var Path $path */
            $polyLinesAttributes[$polyLineIndex]['model_id'] = $path->id;
            $path->setLinkedAwakenedObeliskByMapIconId($mapIconsAttributes[$polyLineIndex]['obelisk_map_icon']->id);

            $polyLineIndex++;
        }

        Polyline::insert($polyLinesAttributes);

        // Assign the polylines back to the brushlines/paths
        $polyLines = Polyline::whereIn('model_id', $paths->pluck('id'))
            ->where('model_class', Path::class)
            ->orderBy('id')
            ->get('id');

        // Assign the polylines back to the brushlines/paths
        $polyLineIndex = 0;
        foreach ($paths as $path) {
            $path->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }

        // Assign awakened obelisks
        $obeliskMapIcons = $dungeonRoute->mapicons()
            ->whereIn('map_icon_type_id', [
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC],
            ])
            ->orderBy('id')
            ->get();

        $obeliskMapIconIndex = 0;
        foreach ($obeliskMapIcons as $obeliskMapIcon) {
            /** @var MapIcon $obeliskMapIcon */
            $obeliskMapIcon->setLinkedAwakenedObeliskByMapIconId($mapIconsAttributes[$obeliskMapIconIndex]['obelisk_map_icon']->id);

            $obeliskMapIconIndex++;
        }
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
