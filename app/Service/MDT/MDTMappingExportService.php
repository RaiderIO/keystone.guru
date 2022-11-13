<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use Exception;
use Illuminate\Support\Collection;

class MDTMappingExportService implements MDTMappingExportServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getMDTMapping(MappingVersion $mappingVersion): string
    {
        $translations = collect();

        $dungeonMaps             = $this->getDungeonMaps($mappingVersion);
        $dungeonSubLevels        = $this->getDungeonSubLevels($mappingVersion, $translations);
        $dungeonTotalCountString = $this->getDungeonTotalCount($mappingVersion);
        $mapPOIS                 = $this->getMapPOIs($mappingVersion);
        $dungeonEnemies          = $this->getDungeonEnemies($mappingVersion, $translations);
        $header                  = $this->getHeader($mappingVersion, $translations);

        return $header . $dungeonMaps . $dungeonSubLevels . $dungeonTotalCountString . $mapPOIS . $dungeonEnemies;
    }

    /**
     * @param MappingVersion $mappingVersion
     * @param Collection $translations
     * @return string
     */
    private function getHeader(MappingVersion $mappingVersion, Collection $translations): string
    {
        $translations->push(__($mappingVersion->dungeon->name));

        $translationsLua = $this->getTranslations($translations);
        return sprintf('
local MDT = MDT
local L = MDT.L
%s
local dungeonIndex = %d
MDT.dungeonList[dungeonIndex] = L["%s"]
MDT.mapInfo[dungeonIndex] = {
--  viewportPositionOverrides =
--  {
--    [1] = {
--      zoomScale = 1.2999999523163;
--      horizontalPan = 102.41712541524;
--      verticalPan = 87.49594729527;
--    };
--  }
};
        ', $translationsLua, $mappingVersion->dungeon->mdt_id, __($mappingVersion->dungeon->name));
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getDungeonMaps(MappingVersion $mappingVersion): string
    {
        $dungeonMaps   = [];
        $index         = 0;
        $dungeonMaps[] = sprintf('[%d] = "%s",', $index, $mappingVersion->dungeon->key);
        foreach ($mappingVersion->dungeon->floors as $floor) {
            $dungeonMaps[] = sprintf('[%d] = "%s%d_",', ++$index, $mappingVersion->dungeon->key, $index);
        }
        return sprintf('
MDT.dungeonMaps[dungeonIndex] = {
%s
}
        ', implode(PHP_EOL, $dungeonMaps));
    }

    /**
     * @param MappingVersion $mappingVersion
     * @param Collection $translations
     * @return string
     */
    private function getDungeonSubLevels(MappingVersion $mappingVersion, Collection $translations): string
    {
        $subLevels = [];
        $index     = 0;
        foreach ($mappingVersion->dungeon->floors as $floor) {
            $subLevels[] = sprintf('    [%d] = L["%s"],', ++$index, __($floor->name));
            $translations->push(__($floor->name));
        }

        return sprintf("
MDT.dungeonSubLevels[dungeonIndex] = {
%s
}
        ", implode(PHP_EOL, $subLevels));
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getDungeonTotalCount(MappingVersion $mappingVersion): string
    {
        $dungeon = $mappingVersion->dungeon;
        return sprintf(
            '
MDT.dungeonTotalCount[dungeonIndex] = { normal = %d, teeming = %s, teemingEnabled = true }
            ',
            $dungeon->enemy_forces_required <= 0 ? 300 : $dungeon->enemy_forces_required,
            $dungeon->enemy_forces_required_teeming <= 0 ? 1000 : $dungeon->enemy_forces_required_teeming
        );
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getMapPOIs(MappingVersion $mappingVersion): string
    {
        $dungeonFloorSwitchMarkers = [];

        foreach ($mappingVersion->dungeon->floors as $floor) {
            $dungeonFloorSwitchMarkersOnFloor = [];
            $dungeonFloorSwitchMarkerIndex    = 0;

            foreach ($floor->dungeonfloorswitchmarkers($mappingVersion)->get() as $dungeonFloorSwitchMarker) {
                /** @var $dungeonFloorSwitchMarker DungeonFloorSwitchMarker */
                $dungeonFloorSwitchMarkersOnFloor[++$dungeonFloorSwitchMarkerIndex] = array_merge([
                    'template'        => 'MapLinkPinTemplate',
                    'type'            => 'mapLink',
                    'target'          => $dungeonFloorSwitchMarker->targetfloor->mdt_sub_level ?? $dungeonFloorSwitchMarker->targetfloor->index,
                    'direction'       => $dungeonFloorSwitchMarker->getMdtDirection(),
                    'connectionIndex' => $dungeonFloorSwitchMarkerIndex, // @TODO this is wrong?
                ], Conversion::convertLatLngToMDTCoordinate(['lat' => $dungeonFloorSwitchMarker->lat, 'lng' => $dungeonFloorSwitchMarker->lng]));
            }

            if (!empty($dungeonFloorSwitchMarkersOnFloor)) {
                $dungeonFloorSwitchMarkers[$floor->mdt_sub_level ?? $floor->index] = $dungeonFloorSwitchMarkersOnFloor;
            }
        }

        return (new PhpArray2LuaTable())->toLuaTableString('MDT.mapPOIs[dungeonIndex]', $dungeonFloorSwitchMarkers);
    }

    /**
     * Takes a mapping version and outputs an array in the way MDT would read it
     *
     * @param MappingVersion $mappingVersion
     * @param Collection $translations
     * @return string
     */
    private function getDungeonEnemies(MappingVersion $mappingVersion, Collection $translations): string
    {
        $dungeonEnemies = [];

        $npcs = Npc::whereIn('dungeon_id', [-1, $mappingVersion->dungeon_id])->get()->keyBy('id');

        // A variable for storing my enemy packs and assigning them a group numbers
        $enemyPackGroups = collect();

        $dungeonEnemyIndex = 0;
        foreach ($mappingVersion->enemies->groupBy('npc_id') as $npcId => $enemies) {
            /** @var Collection|Enemy[] $enemies */
            /** @var Npc $npc */
            $npc = $npcs->get($npcId);

            $dungeonEnemy = [
                'name'         => $npc->name,
                'id'           => $npc->id,
                'count'        => $npc->enemy_forces,
                'health'       => $npc->base_health,
                'scale'        => 1,
                'displayId'    => $npc->display_id,
                'creatureType' => $npc->type->type,
                'level'        => 60,
                //                'characteristics' => [], // @TODO
                //                'spells'          => [], // @TODO
                'clones'       => [],
            ];
            $translations->push($npc->name);

            $cloneIndex = 0;
            foreach ($enemies as $enemy) {
                if (!$enemyPackGroups->has($enemy->enemy_pack_id)) {
                    $enemyPackGroups->put($enemy->enemy_pack_id, $enemyPackGroups->count() + 1);
                }

                $dungeonEnemy['clones'][++$cloneIndex] = array_merge([
                    'g'        => $enemyPackGroups->get($enemy->enemy_pack_id),
                    'sublevel' => $enemy->floor->mdt_sub_level ?? $enemy->floor->index,
                ], Conversion::convertLatLngToMDTCoordinate(['lat' => $enemy->lat, 'lng' => $enemy->lng]));
            }

            $dungeonEnemies[++$dungeonEnemyIndex] = $dungeonEnemy;
        }

        return (new PhpArray2LuaTable())->toLuaTableString('MDT.dungeonEnemies[dungeonIndex]', $dungeonEnemies);
    }

    /**
     * @param Collection $translations
     * @return string
     */
    private function getTranslations(Collection $translations): string
    {
        $lua = [];
        foreach ($translations as $translation) {
            $lua[] = sprintf('L["%s"] = "%s"', $translation, $translation);
        }

        // Add another EOL at the end of it
        $lua[] = '';

        return implode(PHP_EOL, $lua);
    }

    /**
     * @inheritDoc
     */
    public function getMappingVersion(string $dungeon): MappingVersion
    {
        // TODO: Implement getMappingVersion() method.
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMDTMappingHash(string $dungeon): string
    {
        return md5((new MDTDungeon($dungeon))
            ->getMDTNPCs()
            ->toJson());
    }
}
