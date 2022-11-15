<?php

namespace App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcType;
use App\Service\Mapping\MappingServiceInterface;
use Exception;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{
    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon): MappingVersion
    {
        $newMappingVersion = $mappingService->createNewMappingVersion($dungeon, true);
        logger()->channel('stderr')->info(sprintf('Creating version %d (%d) OK', $newMappingVersion->version, $newMappingVersion->id));

        $mdtDungeon = new MDTDungeon($dungeon);

        $this->importNpcs($mdtDungeon, $dungeon);
        $enemies = $this->importEnemies($newMappingVersion, $mdtDungeon, $dungeon);
        $this->importEnemyPacks($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
        $this->importEnemyPatrols($newMappingVersion, $mdtDungeon, $dungeon);
        $this->importDungeonFloorSwitchMarkers($newMappingVersion, $mdtDungeon, $dungeon);

        return $newMappingVersion;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMDTMappingHash(Dungeon $dungeon): string
    {
        return md5((new MDTDungeon($dungeon))
            ->getMDTNPCs()
            ->toJson());
    }

    /**
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     * @throws Exception
     */
    private function importNpcs(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        // Get a list of NPCs and update/save them
        logger()->channel('stderr')->info('Updating NPCs');
        $npcs = $dungeon->npcs->keyBy('id');

        foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
            $npc = $npcs->get($mdtNpc->getId());
            if ($npc === null) {
                $npc = new Npc();
            }

            $npc->id                   = $mdtNpc->getId();
            $npc->display_id           = $mdtNpc->getDisplayId();
            $npc->enemy_forces         = $mdtNpc->getCount();
            $npc->enemy_forces_teeming = $mdtNpc->getCountTeeming();
            $npc->base_health          = $mdtNpc->getHealth();
            $npc->npc_type_id          = NpcType::ALL[$mdtNpc->getCreatureType()] ?? NpcType::HUMANOID;
            if ($npc->save()) {
                logger()->channel('stderr')->info(sprintf('- NPC %d OK', $npc->id));
            } else {
                logger()->channel('stderr')->info(sprintf('- Unable to save %d', $npc->id));
            }
        }
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return Collection|Enemy[]
     * @throws InvalidArgumentException
     */
    private function importEnemies(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon): Collection
    {
        // Get a list of new enemies and save them
        logger()->channel('stderr')->info('Updating Enemies');
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

            if ($enemy->save()) {
                logger()->channel('stderr')->info(sprintf('- Enemy %d OK', $enemy->id));
            } else {
                throw new Exception(sprintf('Unable to save enemy!'));
            }
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
        logger()->channel('stderr')->info('Updating Enemy Packs');

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
                'teeming'            => Enemy::TEEMING_VISIBLE,
                'faction'            => Faction::FACTION_ANY,
                'label'              => sprintf('Imported from MDT - group %d', $groupIndex),
                'vertices_json'      => json_encode($this->getVerticesBoundingBoxFromEnemies($enemiesWithGroupsByEnemyPack)),
            ]);
            if ($enemyPack === null) {
                throw new Exception('Unable to save enemy pack!');
            }
            logger()->channel('stderr')->info(sprintf('- Enemy pack %d OK (%d enemies)', $enemyPack->id, $enemiesWithGroupsByEnemyPack->count()));

            foreach ($enemiesWithGroupsByEnemyPack as $enemyWithGroup) {
                // In the list of enemies that we saved to the database, find the enemy that still had the group intact.
                // Write the saved enemy's enemy pack back to the database
                $savedEnemy = $savedEnemies->firstOrFail(function (Enemy $enemy) use ($enemyWithGroup) {
                    return $enemy->npc_id === $enemyWithGroup->npc_id && $enemy->mdt_id === $enemyWithGroup->mdt_id;
                });

                if ($savedEnemy->update(['enemy_pack_id' => $enemyPack->id])) {
                    logger()->channel('stderr')->info(sprintf('-- Enemy %d -> enemy pack %d OK', $savedEnemy->id, $enemyPack->id));
                } else {
                    throw new Exception('Unable to update enemy with enemy pack!');
                }
            }
        }
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     * @throws InvalidArgumentException
     */
    private function importEnemyPatrols(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon)
    {
        // Get a list of new enemies and save them
        logger()->channel('stderr')->info('Updating Enemy Patrols');
//        $enemies = $mdtDungeon->getMDTNPCs();

//        foreach ($enemies as $enemy) {
//            $enemy->exists = false;
//            $enemy->unsetRelations();
//
//            // Not saved in the database
//            unset($enemy->npc);
//            unset($enemy->id);
//            unset($enemy->mdt_npc_index);
//            unset($enemy->is_mdt);
//            unset($enemy->enemy_id);
//
//            // Is group ID - we handle this later on
//            $enemy->enemy_pack_id      = null;
//            $enemy->mapping_version_id = $newMappingVersion->id;
//
//            if ($enemy->save()) {
//                logger()->channel('stderr')->info(sprintf('- Enemy %d OK', $enemy->id));
//            } else {
//                throw new Exception(sprintf('Unable to save enemy!'));
//            }
//        }
    }

    /**
     * @param MappingVersion $newMappingVersion
     * @param MDTDungeon $mdtDungeon
     * @param Dungeon $dungeon
     * @return void
     */
    private function importDungeonFloorSwitchMarkers(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon)
    {
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
