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

class MDTMappingImportService implements MDTMappingImportServiceInterface
{
    /**
     * @inheritDoc
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon): MappingVersion
    {
        $newMappingVersion = $mappingService->createNewMappingVersion($dungeon, true);
        $mdtDungeon        = new MDTDungeon($dungeon);

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
                logger()->channel('stderr')->info(sprintf('- %d OK', $npc->id));
            } else {
                logger()->channel('stderr')->info(sprintf('- Unable to save %d', $npc->id));
            }
        }


        // Get a list of new enemies and save them
        logger()->channel('stderr')->info('Updating Enemies');
        $enemies = $mdtDungeon->getClonesAsEnemies($dungeon->floors);
        // Conserve the enemy_pack_id
        $enemyPacks = $enemies->groupBy('enemy_pack_id');

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
                logger()->channel('stderr')->info(sprintf('- %d OK', $enemy->id));
            } else {
                throw new Exception(sprintf('Unable to save enemy!'));
            }
        }

        // Save enemy packs
        foreach ($enemyPacks as $groupIndex => $enemies) {
            /** @var $enemies Collection|Enemy[] */
            // Enemies without a group - don't import that group
            if (is_null($groupIndex) || $groupIndex === -1) {
                continue;
            }

            $floorId  = null;
            $vertices = [];
            foreach ($enemies as $enemy) {
                $vertices[] = ['lat' => $enemy->lat, 'lng' => $enemy->lng];
                // Assign this multiple times, it's ok
                $floorId = $enemy->floor_id;
            }

            $enemyPack = EnemyPack::create([
                'mapping_version_id' => $newMappingVersion->id,
                'floor_id'           => $floorId,
                'group'              => $groupIndex,
                'teeming'            => Enemy::TEEMING_VISIBLE,
                'faction'            => Faction::FACTION_ANY,
                'label'              => sprintf('Imported from MDT - group %d', $groupIndex),
                'vertices_json'      => json_encode($vertices),
            ]);
            if ($enemyPack === null) {
                throw new Exception(sprintf('Unable to save enemy pack!'));
            }
            logger()->channel('stderr')->info(sprintf('- %d OK (%d enemies)', $enemyPack->id, $enemies->count()));

            foreach ($enemies as $enemy) {
                if ($enemy->update(['enemy_pack_id' => $enemyPack->id])) {
                    logger()->channel('stderr')->info(sprintf('-- %d -> %d OK', $enemy->id, $enemyPack->id));
                } else {
                    throw new Exception(sprintf('Unable to update enemy with enemy pack!'));
                }
            }
        }

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
}
