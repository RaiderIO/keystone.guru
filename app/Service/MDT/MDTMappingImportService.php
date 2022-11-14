<?php

namespace App\Service\MDT;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcType;
use App\Service\Mapping\MappingServiceInterface;
use Exception;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{
    /**
     * @inheritDoc
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon): MappingVersion
    {
//        $newMappingVersion     = $mappingService->createNewMappingVersion($dungeon, true);
        $mdtDungeon = new MDTDungeon($dungeon);

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
        die();


        // Get a list of new enemies and save them
        $enemies = $mdtDungeon->getClonesAsEnemies($dungeon->floors);
        // Conserve the enemy_pack_id
        $enemyPacks = $enemies->groupBy('enemy_pack_id');

        foreach ($enemies as $enemy) {
            $enemy->exists = false;
            $enemy->unsetRelations();
            // Not saved in the database
            unset($enemy->enemy_id);
            // Is group ID - we handle this later on
            $enemy->enemy_pack_id      = null;
            $enemy->mapping_version_id = $newMappingVersion->id;

            if (!$enemy->save()) {
                throw new Exception(sprintf('Unable to save enemy!'));
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
