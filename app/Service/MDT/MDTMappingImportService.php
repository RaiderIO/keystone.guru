<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcType;
use App\Models\Polyline;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{

    private MDTMappingImportServiceLoggingInterface $log;

    /**
     * @param MDTMappingImportServiceLoggingInterface $log
     */
    public function __construct(MDTMappingImportServiceLoggingInterface $log)
    {
        $this->log = $log;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon, bool $forceImport = false): MappingVersion
    {
        $latestMdtMappingHash = $this->getMDTMappingHash($dungeon);

        $currentMappingVersion = $dungeon->getCurrentMappingVersion();
        if ($forceImport || $currentMappingVersion->mdt_mapping_hash !== $latestMdtMappingHash) {
            $this->log->importMappingVersionFromMDTMappingChanged($currentMappingVersion->mdt_mapping_hash, $latestMdtMappingHash);

            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping($dungeon, $this->getMDTMappingHash($dungeon));
            $this->log->importMappingVersionFromMDTCreateMappingVersion($newMappingVersion->version, $newMappingVersion->id);

            $mdtDungeon = new MDTDungeon($dungeon);

            try {
                $this->log->importMappingVersionFromMDTStart($dungeon->id);

                $this->importDungeon($mdtDungeon, $dungeon);
                $this->importNpcs($mdtDungeon, $dungeon);
                $enemies = $this->importEnemies($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon);
                $this->importEnemyPacks($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->importEnemyPatrols($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->importDungeonFloorSwitchMarkers($newMappingVersion, $mdtDungeon, $dungeon);
            } finally {
                $this->log->importMappingVersionFromMDTEnd();
            }

            return $newMappingVersion;
        } else {
            throw new Exception(
                sprintf('Most recent mapping version is already imported from this MDT version! (%s - %s)', $dungeon->key, $latestMdtMappingHash)
            );
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMDTMappingHash(Dungeon $dungeon): string
    {
        $mdtDungeon = new MDTDungeon($dungeon);

        return md5(
            json_encode([
                'counts'             => $mdtDungeon->getDungeonTotalCount(),
                'npcs'               => $mdtDungeon->getMDTNPCs()->toArray(),
                'floorSwitchMarkers' => $mdtDungeon->getMDTMapPOIs()->toArray(),
            ])
        );
    }

    /**
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     * @throws Exception
     */
    private function importDungeon(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importDungeonStart();
            $totalCount = $mdtDungeon->getDungeonTotalCount();
            $this->log->importDungeonTotalCounts($mdtDungeon->getMDTDungeonID(), $totalCount['normal'], $totalCount['teeming']);

            if ($dungeon->update([
                'mdt_id'                        => $mdtDungeon->getMDTDungeonID(),
                'enemy_forces_required'         => $totalCount['normal'],
                'enemy_forces_required_teeming' => $totalCount['teeming'],
            ])) {
                $this->log->importDungeonOK();
            } else {
                $this->log->importDungeonFailed();
                throw new Exception(sprintf('Unable to update dungeon %s!', __($dungeon->name)));
            }
        } finally {
            $this->log->importDungeonEnd();
        }
    }

    /**
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     * @throws Exception
     */
    private function importNpcs(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importNpcsStart();

            // Get a list of NPCs and update/save them
            $npcs = $dungeon->npcs->keyBy('id');

            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                $npc = $npcs->get($mdtNpc->getId());
                if ($newlyCreated = ($npc === null)) {
                    $npc = new Npc();
                }

                $npc->id = $mdtNpc->getId();
                // Allow manual override to -1
                $npc->dungeon_id           = $npc->dungeon_id === -1 ? -1 : $dungeon->id;
                $npc->display_id           = $mdtNpc->getDisplayId();
                $npc->name                 = $mdtNpc->getName();
                $npc->base_health          = $mdtNpc->getHealth();
                $npc->enemy_forces         = $mdtNpc->getCount();
                $npc->enemy_forces_teeming = $mdtNpc->getCountTeeming();
                $npc->npc_type_id          = NpcType::ALL[$mdtNpc->getCreatureType()] ?? NpcType::HUMANOID;
                try {
                    if ($npc->save()) {
                        if ($newlyCreated) {
                            $this->log->importNpcsSaveNewNpc($npc->id);
                        } else {
                            $this->log->importNpcsUpdateExistingNpc($npc->id);
                        }
                    } else {
                        throw new Exception(sprintf('Unable to save npc %d!', $npc->id));
                    }
                } catch (Exception $exception) {
                    $this->log->importNpcsSaveNpcException($exception);
                }
            }
        } finally {
            $this->log->importNpcsEnd();
        }
    }

    /**
     * @param MappingVersion $currentMappingVersion
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return Collection|Enemy
     * @throws InvalidArgumentException
     */
    private function importEnemies(Mappingversion $currentMappingVersion, MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon): Collection
    {
        // Get a list of new enemies and save them
        try {
            $this->log->importEnemiesStart();

            $currentEnemies = $currentMappingVersion->enemies->keyBy(function (Enemy $enemy) {
                return $enemy->getUniqueKey();
            });

            $enemies = $mdtDungeon->getClonesAsEnemies($dungeon->floors);

            foreach ($enemies as $enemy) {
                $enemy->exists = false;
                $enemy->unsetRelations();

                // Not saved in the database
                unset($enemy->npc);
                unset($enemy->id);
                unset($enemy->mdt_npc_index);
                unset($enemy->is_mdt);
                unset($enemy->enemy_id);

                // Is group ID - we handle this later on
                $enemy->enemy_pack_id      = null;
                $enemy->mapping_version_id = $newMappingVersion->id;

                $currentEnemy = $currentEnemies->get($enemy->getUniqueKey());
                if ($currentEnemy instanceof Enemy) {
                    $fields        = ['teeming', 'faction', 'required', 'skippable'];
                    $updatedFields = [];
                    foreach ($fields as $field) {
                        $enemy->$field         = $currentEnemy->$field;
                        $updatedFields[$field] = $currentEnemy->$field;
                    }
                    $this->log->importEnemiesRecoverPropertiesFromExistingEnemy($enemy->getUniqueKey(), $updatedFields);
                } else {
                    $this->log->importEnemiesCannotRecoverPropertiesFromExistingEnemy($enemy->getUniqueKey());
                }

                try {
                    if ($enemy->save()) {
                        $this->log->importEnemiesSaveNewEnemy($enemy->id);
                    } else {
                        throw new Exception(sprintf('Unable to save enemy %d!', $enemy->id));
                    }
                } catch (Exception $exception) {
                    $this->log->importEnemiesSaveNewEnemyException($exception);
                }
            }
        } finally {
            $this->log->importEnemiesEnd();
        }

        return $enemies;
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @param Collection $savedEnemies
     * @return void
     * @throws InvalidArgumentException
     */
    private function importEnemyPacks(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon, Collection $savedEnemies): void
    {
        try {
            $this->log->importEnemyPacksStart();


            $savedEnemies = $savedEnemies->keyBy('id');

            // Conserve the enemy_pack_id
            $enemiesWithGroups = $mdtDungeon->getClonesAsEnemies($dungeon->floors);
            $enemyPacks        = $enemiesWithGroups->groupBy('enemy_pack_id');

            // Save enemy packs
            foreach ($enemyPacks as $groupIndex => $enemiesWithGroupsByEnemyPack) {
                /** @var $enemiesWithGroupsByEnemyPack Collection|Enemy[] */
                $enemiesWithGroupsByEnemyPack = $enemiesWithGroupsByEnemyPack->keyBy('id');

                // Enemies without a group - don't import that group
                if (is_null($groupIndex) || $groupIndex === -1) {
                    continue;
                }

                $enemyPack = EnemyPack::create([
                    'mapping_version_id' => $newMappingVersion->id,
                    'floor_id'           => $enemiesWithGroupsByEnemyPack->first()->floor_id,
                    'group'              => $groupIndex,
                    'teeming'            => null,
                    'faction'            => Faction::FACTION_ANY,
                    'label'              => sprintf('Imported from MDT - group %d', $groupIndex),
                    'vertices_json'      => json_encode($this->getVerticesBoundingBoxFromEnemies($enemiesWithGroupsByEnemyPack)),
                ]);
                if ($enemyPack === null) {
                    throw new Exception('Unable to save enemy pack!');
                }
                $this->log->importEnemyPacksSaveNewEnemyPackOK($enemyPack->id, $enemiesWithGroupsByEnemyPack->count());

                try {
                    $this->log->importEnemyPacksCoupleEnemyToPackStart($enemyPack->id);
//                logger()->channel('stderr')->info(sprintf('- Enemy Pack %d OK (%d enemies)', $enemyPack->id, $enemiesWithGroupsByEnemyPack->count()));

                    foreach ($enemiesWithGroupsByEnemyPack as $enemyWithGroup) {
                        // In the list of enemies that we saved to the database, find the enemy that still had the group intact.
                        // Write the saved enemy's enemy pack back to the database
                        $savedEnemy = $this->findSavedEnemyFromCloneEnemy($savedEnemies, $enemyWithGroup->npc_id, $enemyWithGroup->mdt_id);

                        if ($savedEnemy->update(['enemy_pack_id' => $enemyPack->id])) {
                            $this->log->importEnemyPacksCoupleEnemyToEnemyPack($savedEnemy->id);
//                        logger()->channel('stderr')->info(sprintf('-- Enemy %d -> Enemy Pack %d OK', $savedEnemy->id, $enemyPack->id));
                        } else {
                            throw new Exception('Unable to update enemy with enemy pack!');
                        }
                    }
                } finally {
                    $this->log->importEnemyPacksCoupleEnemyToPackEnd();
                }
            }
        } finally {
            $this->log->importEnemyPacksEnd();
        }
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @param Collection $savedEnemies
     * @return void
     * @throws Exception
     */
    private function importEnemyPatrols(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon, Collection $savedEnemies)
    {
        try {
            $this->log->importEnemyPatrolsStart();

            // Get a list of new enemies and save them
            $mdtNPCs = $mdtDungeon->getMDTNPCs();

            foreach ($mdtNPCs as $mdtNPC) {
                foreach ($mdtNPC->getRawMdtNpc()['clones'] as $mdtCloneIndex => $mdtNpcClone) {
                    if (!isset($mdtNpcClone['patrol'])) {
                        continue;
                    }

                    $savedEnemy = $this->findSavedEnemyFromCloneEnemy($savedEnemies, $mdtNPC->getId(), $mdtCloneIndex);
                    $this->log->importEnemyPatrolsEnemyHasPatrol($savedEnemy->getUniqueKey());

                    if (empty($mdtNpcClone['patrol'])) {
                        $this->log->importEnemyPatrolsFoundPatrolIsEmpty($savedEnemy->getUniqueKey());
                        continue;
                    }

                    $vertices = [];
                    foreach ($mdtNpcClone['patrol'] as $xy) {
                        $vertices[] = Conversion::convertMDTCoordinateToLatLng($xy);
                    }

                    // MDT automatically closes up the patrol which I don't, so correct for this (confirmed by Nnoggie)
                    $vertices[] = $vertices[0];

                    // Polyline
                    $polyLine = Polyline::create([
                        'model_id'       => -1,
                        'model_class'    => EnemyPatrol::class,
                        'color'          => '#003280',
                        'color_animated' => null,
                        'weight'         => 2,
                        'vertices_json'  => json_encode($vertices),
                    ]);
                    if ($polyLine !== null) {
                        $this->log->importEnemyPatrolsSaveNewPolyline($polyLine->id);
                    } else {
                        throw new Exception(sprintf('Unable to save polyline!'));
                    }

                    // Enemy patrols
                    $enemyPatrol = EnemyPatrol::create([
                        'mapping_version_id' => $newMappingVersion->id,
                        'floor_id'           => $savedEnemy->floor_id,
                        'polyline_id'        => $polyLine->id,
                        'teeming'            => null,
                        'faction'            => Faction::FACTION_ANY,
                    ]);
                    if ($enemyPatrol !== null) {
                        $this->log->importEnemyPatrolsSaveNewEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception(sprintf('Unable to save enemy patrol!'));
                    }

                    // Couple polyline to enemy patrol
                    $polyLineSaveResult = $polyLine->update(['model_id' => $enemyPatrol->id]);
                    if ($polyLineSaveResult) {
                        $this->log->importEnemyPatrolsCoupleEnemyPatrolToPolyline($enemyPatrol->id, $polyLine->id);
                    } else {
                        throw new Exception(sprintf('Unable to save polyline!'));
                    }

                    // Couple enemy/enemies to enemy patrol
                    if ($savedEnemy->enemy_pack_id !== null) {
                        $enemyUpdateResult = Enemy::where('enemy_pack_id', $savedEnemy->enemy_pack_id)->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    } else {
                        $enemyUpdateResult = $savedEnemy->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    }
                    if ($enemyUpdateResult) {
                        $this->log->importEnemyPatrolsCoupleEnemiesToEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception(sprintf('Unable to update enemy to have attached patrol!'));
                    }
                }
            }
        } finally {
            $this->log->importEnemyPatrolsEnd();
        }
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     * @throws Exception
     */
    private function importDungeonFloorSwitchMarkers(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon)
    {
        try {
            $this->log->importDungeonFloorSwitchMarkersStart();
            $mdtMapPOIs = $mdtDungeon->getMDTMapPOIs();

            foreach ($mdtMapPOIs as $mdtMapPOI) {
                if ($mdtMapPOI->getType() !== MDTMapPOI::TYPE_MAP_LINK) {
                    continue;
                }

                $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::create(array_merge([
                    'mapping_version_id' => $newMappingVersion->id,
                    'floor_id'           => $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getSubLevel())->id,
                    'target_floor_id'    => $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getTarget())->id,
                ], Conversion::convertMDTCoordinateToLatLng(['x' => $mdtMapPOI->getX(), 'y' => $mdtMapPOI->getY()])));
                if ($dungeonFloorSwitchMarker !== null) {
                    $this->log->importDungeonFloorSwitchMarkersNewDungeonFloorSwitchMarkerOK(
                        $dungeonFloorSwitchMarker->id,
                        $dungeonFloorSwitchMarker->floor_id,
                        $dungeonFloorSwitchMarker->target_floor_id,
                    );
                } else {
                    throw new Exception(sprintf('Unable to save dungeon floor switch marker!'));
                }
            }
        } finally {
            $this->log->importDungeonFloorSwitchMarkersEnd();
        }
    }

    /**
     * @param Collection $savedEnemies
     * @param int $npcId
     * @param int $mdtId
     * @return Enemy
     */
    private function findSavedEnemyFromCloneEnemy(Collection $savedEnemies, int $npcId, int $mdtId): Enemy
    {
        return $savedEnemies->firstOrFail(function (Enemy $enemy) use ($npcId, $mdtId) {
            return $enemy->npc_id === $npcId && $enemy->mdt_id === $mdtId;
        });
    }

    /**
     * @param Dungeon $dungeon
     * @param int $mdtSubLevel
     * @return Floor
     */
    public function findFloorByMdtSubLevel(Dungeon $dungeon, int $mdtSubLevel): Floor
    {
        // First check for mdt_sub_level, if that isn't found just match on our own index
        return $dungeon->floors->first(function (Floor $floor) use ($mdtSubLevel) {
            return $floor->mdt_sub_level === $mdtSubLevel;
        }) ?? $dungeon->floors->first(function (Floor $floor) use ($mdtSubLevel) {
            return $floor->index === $mdtSubLevel;
        });
    }

    /**
     * Get a bounding box which encompasses all passed enemies
     * @param Collection|Enemy[] $enemies
     * @return array
     */
    private function getVerticesBoundingBoxFromEnemies(Collection $enemies): array
    {
        $minLat = $minLng = 1000;
        $maxLat = $maxLng = -1000;

        foreach ($enemies as $enemy) {
            // Find the min and max of lat and lng so we have a nice square
            if ($minLat > $enemy->lat) {
                $minLat = $enemy->lat;
            }
            if ($maxLat < $enemy->lat) {
                $maxLat = $enemy->lat;
            }

            if ($minLng > $enemy->lng) {
                $minLng = $enemy->lng;
            }
            if ($maxLng < $enemy->lng) {
                $maxLng = $enemy->lng;
            }
        }

        // Expand the box a bit
        $padding = 1;
        $minLat  -= $padding;
        $minLng  -= $padding;
        $maxLat  += $padding;
        $maxLng  += $padding;

        // Create a box
        return [
            ['lat' => $minLat, 'lng' => $minLng],
            ['lat' => $maxLat, 'lng' => $minLng],
            ['lat' => $maxLat, 'lng' => $maxLng],
            ['lat' => $minLat, 'lng' => $maxLng],
        ];
    }
}
