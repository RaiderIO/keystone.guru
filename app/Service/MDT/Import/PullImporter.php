<?php

namespace App\Service\MDT\Import;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Models\Npc\NpcEnemyForces;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Models\ImportStringPulls;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PullImporter
{
    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Parse the $decoded['value']['pulls'] value and save the result in the ImportStringPulls object.
     *
     *
     * @throws \App\Logic\MDT\Exception\InvalidMDTDungeonException
     * @throws InvalidArgumentException
     */
    public function parseValuePulls(
        ImportStringPulls $importStringPulls,
    ): ImportStringPulls {
        if (count($importStringPulls->getMdtPulls()) > config('keystoneguru.dungeon_route_limits.kill_zones')) {
            $importStringPulls->getErrors()->push(
                new ImportError(
                    __('services.mdt.io.import_string.category.pulls'),
                    __('services.mdt.io.import_string.limit_reached_pulls', ['limit' => config('keystoneguru.dungeon_route_limits.kill_zones')]),
                ),
            );
        }

        $floors = $importStringPulls->getDungeon()->floors;
        /** @var Collection<int, Enemy> $enemies */
        $enemies = $importStringPulls->getMappingVersion()->enemies->each(static function (Enemy $enemy) {
            $enemy->npc_id = $enemy->mdt_npc_id ?? $enemy->npc_id;
        });

        // Keep a list of prideful enemies to assign
        //        $pridefulEnemies    = $enemies->where('npc_id', config('keystoneguru.prideful.npc_id'));
        //        $pridefulEnemyCount = config('keystoneguru.prideful.count');
        // Group so that we pre-process the list once and fetch a grouped list later to greatly improve performance
        $enemiesByNpcId      = $enemies->groupBy('npc_id');
        $enemyForcesByNpcIds = NpcEnemyForces::where('mapping_version_id', $importStringPulls->getMappingVersion()->id)->get()->keyBy('npc_id');

        // Fetch all enemies of this dungeon
        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $importStringPulls->getDungeon())
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
                        $mdtClones,
                    );
                }

                // If the pull never contained any enemies at all, completely skip it
                if ($totalEnemiesSelected === 0) {
                    continue;
                }

                // Don't throw this warning if we skipped things because they were not part of the seasonal index we're importing
                // Also don't throw it if the pull is simply empty in MDT, then just import an empty pull for consistency
                if (!$seasonalIndexSkip && $totalEnemiesMatched === 0) {
                    throw new ImportWarning(
                        sprintf(__('services.mdt.io.import_string.category.pull'), $newPullIndex),
                        __('services.mdt.io.import_string.unable_to_find_enemies_pull_skipped'),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_enemies_pull_skipped_details')],
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
     * @param Collection<int|string, Collection<int, Enemy>> $mdtEnemiesByMdtNpcIndex
     * @param Collection<int|string, Collection<int, Enemy>> $enemiesByNpcId
     * @param Collection<int, NpcEnemyForces>                $enemyForcesByNpcIds
     * @param array<int|string, mixed>                       $killZoneAttributes
     * @param string|array<int, mixed>                       $mdtNpcClones
     */
    private function parseMdtNpcClonesInPull(
        ImportStringPulls $importStringPulls,
        Collection        $mdtEnemiesByMdtNpcIndex,
        Collection        $enemiesByNpcId,
        Collection        $enemyForcesByNpcIds,
        int               &               $totalEnemiesSelected,
        int               &               $totalEnemiesMatched,
        bool              &              $seasonalIndexSkip,
        array             &             $killZoneAttributes,
        int               $newPullIndex,
        string            $mdtNpcIndex,
                          $mdtNpcClones,
    ): bool {
        if ($mdtNpcIndex === 'color') {
            // Make sure there is a pound sign in front of the value at all times, but never double up should
            // MDT decide to suddenly place it here
            $killZoneAttributes['color'] = (!str_starts_with($mdtNpcClones, '#') ? '#' : '') . $mdtNpcClones;

            return false;
        } // Numeric means it's an index of the dungeon's NPCs, if it isn't numeric skip to the next pull
        elseif (!is_numeric($mdtNpcIndex)) {
            return false;
        }

        $seasonalIndexSkip = false;
        $npcIndex          = (int)$mdtNpcIndex;
        $mdtClones         = $mdtNpcClones;

        $totalEnemiesSelected = (int)($totalEnemiesSelected + count($mdtClones));
        // Only if filled
        foreach ($mdtClones as $index => $cloneIndex) {
            // This comes in through as a double, cast to int
            $cloneIndex = (int)$cloneIndex;

            // Hacky fix for a MDT bug where there's duplicate NPCs with the same npc_id etc.
            if ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_SIEGE_OF_BORALUS) {
                if ($npcIndex === 35) {
                    $cloneIndex += 15;
                }
            } elseif ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_TOL_DAGOR) {
                if ($npcIndex === 11) {
                    $cloneIndex += 2;
                }
            } elseif ($importStringPulls->getDungeon()->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE) {
                if ($npcIndex === 23) {
                    $cloneIndex += 5;
                }
            }

            // Find the matching enemy of the clones
            $mdtEnemy   = null;
            $isEmissary = false;
            if ($mdtEnemiesByMdtNpcIndex->has($npcIndex)) {
                foreach ($mdtEnemiesByMdtNpcIndex->get($npcIndex) as $mdtEnemyCandidate) {
                    // Skip Emissaries (Season 3), season is over
                    if ($isEmissary = in_array($mdtEnemyCandidate->npc_id, [
                        155432,
                        155433,
                        155434,
                    ])) {
                        break;
                    }

                    /** @var Enemy $mdtEnemyCandidate */
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
                    $importStringPulls->getWarnings()->push(new ImportWarning(
                        sprintf(__('services.mdt.io.import_string.category.pull'), $newPullIndex),
                        sprintf(__('services.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index'), $cloneIndex, $npcIndex),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index_details')],
                    ));
                }

                continue;
            }

            // We now know the MDT enemy that the user was trying to import. However, we need to know
            // our own enemy. Thus, try to find the enemy in our list which has the same npc_id and mdt_id.
            $enemy = null;
            // Only if we have the npc assigned at all
            if ($enemiesByNpcId->has($mdtEnemy->npc_id)) {
                foreach ($enemiesByNpcId->get($mdtEnemy->npc_id) as $enemyCandidate) {
                    /** @var Enemy $enemyCandidate */
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
                    $importStringPulls->getWarnings()->push(new ImportWarning(
                        sprintf(__('services.mdt.io.import_string.category.pull'), $newPullIndex),
                        sprintf(
                            __('services.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy'),
                            $mdtEnemy->mdt_id,
                            __($mdtEnemy->npc->name),
                            $mdtEnemy->npc_id,
                        ),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy_details')],
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
                'npc_id'   => $enemy->npc_id,
                'mdt_id'   => $enemy->mdt_id,
                'enemy_id' => $enemy->id,
                // Cache for the hasFinalBoss check below - it's slow otherwise
                'enemy' => $enemy,
            ];

            // Keep track of our enemy forces
            if ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED) {
                $importStringPulls->addEnemyForces($importStringPulls->getMappingVersion()->enemy_forces_shrouded);
            } elseif ($enemy->seasonal_type === Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX) {
                $importStringPulls->addEnemyForces($importStringPulls->getMappingVersion()->enemy_forces_shrouded_zul_gamux);
            } else {
                /** @var NpcEnemyForces|null $npcEnemyForces */
                $npcEnemyForces = $enemyForcesByNpcIds->get($enemy->npc->id);

                if ($npcEnemyForces !== null) {
                    $importStringPulls->addEnemyForces(
                        $importStringPulls->isRouteTeeming() ?
                            $npcEnemyForces->enemy_forces_teeming :
                            $npcEnemyForces->enemy_forces,
                    );
                } else {
                    logger()->warning(sprintf('Unable to find enemy forces for npc %d!', $enemy->npc->id));
                }
            }

            $totalEnemiesMatched++;
        }

        return true;
    }

    public function applyPullsToDungeonRoute(ImportStringPulls $importStringPulls, DungeonRoute $dungeonRoute): void
    {
        $dungeonRoute->update(['enemy_forces' => $importStringPulls->getEnemyForces()]);

        $killZones       = [];
        $killZoneEnemies = [];
        $killZoneSpells  = [];
        foreach ($importStringPulls->getKillZoneAttributes() as $killZoneAttributes) {
            $killZones[] = [
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
}
