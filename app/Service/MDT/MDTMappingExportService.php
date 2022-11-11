<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
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
        $header                  = $this->getHeader($mappingVersion);
        $dungeonMaps             = $this->getDungeonMaps($mappingVersion);
        $dungeonSubLevels        = $this->getDungeonSubLevels($mappingVersion);
        $dungeonTotalCountString = $this->getDungeonTotalCount($mappingVersion);
        $mapPOIS                 = $this->getMapPOIs($mappingVersion);
        $dungeonEnemies          = $this->getDungeonEnemies($mappingVersion);

        echo $dungeonEnemies;
        dd();

        echo $header . $dungeonMaps . $dungeonSubLevels . $dungeonTotalCountString . $mapPOIS . $dungeonEnemies;

        dd();

//        dd('test', $dungeonEnemies);


        return '';
    }

    private function getHeader(MappingVersion $mappingVersion): string
    {
        return sprintf('
local MDT = MDT
local L = MDT.L
local dungeonIndex = %d
MDT.dungeonList[dungeonIndex] = L["%s"]
MDT.mapInfo[dungeonIndex] = {
#  viewportPositionOverrides =
#  {
#    [1] = {
#      zoomScale = 1.2999999523163;
#      horizontalPan = 102.41712541524;
#      verticalPan = 87.49594729527;
#    };
#  }
};
        ', $mappingVersion->dungeon->mdt_id, __($mappingVersion->dungeon->name));
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getDungeonMaps(MappingVersion $mappingVersion): string
    {
        return sprintf('
#MDT.dungeonMaps[dungeonIndex] = {
#  [0] = "DeOtherSide_Ardenweald",
#  [1] = "DeOtherSide_Main",
#  [2] = "DeOtherSide_Gnome",
#  [3] = "DeOtherSide_Hakkar",
#  [4] = "DeOtherSide_Ardenweald",
#}
        ');
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getDungeonSubLevels(MappingVersion $mappingVersion): string
    {
        $subLevels = [];
        $index     = 0;
        foreach ($mappingVersion->dungeon->floors as $floor) {
            $subLevels[] = sprintf('[%d] = L["%s"],', ++$index, __($floor->name));
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
            $dungeon->enemy_forces_required,
            $dungeon->enemy_forces_required_teeming
        );
    }

    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getMapPOIs(MappingVersion $mappingVersion): string
    {
        $dungeonFloorSwitchMarkers = [];
        $floorIndex                = 0;

        foreach ($mappingVersion->dungeon->floors as $floor) {
            $dungeonFloorSwitchMarkersOnFloor = [];
            $dungeonFloorSwitchMarkerIndex    = 0;

            foreach ($floor->dungeonfloorswitchmarkers as $dungeonFloorSwitchMarker) {
                $dungeonFloorSwitchMarkersOnFloor[++$dungeonFloorSwitchMarkerIndex] = array_merge([
                    'template'        => 'MapLinkPinTemplate',
                    'type'            => 'mapLink',
                    'target'          => $dungeonFloorSwitchMarker->targetfloor->mdt_sub_level ?? $dungeonFloorSwitchMarker->targetfloor->index,
                    'direction'       => '', // @TODO translate direction
                    'connectionIndex' => $dungeonFloorSwitchMarkerIndex, // @TODO this is wrong?
                ], Conversion::convertLatLngToMDTCoordinate(['lat' => $dungeonFloorSwitchMarker->lat, 'lng' => $dungeonFloorSwitchMarker->lng]));
            }
            $dungeonFloorSwitchMarkers[++$floorIndex] = $dungeonFloorSwitchMarkersOnFloor;
        }

        return json_encode($dungeonFloorSwitchMarkers);
    }

    /**
     * Takes a mapping version and outputs an array in the way MDT would read it
     *
     * @param MappingVersion $mappingVersion
     * @return string
     */
    private function getDungeonEnemies(MappingVersion $mappingVersion): string
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
                'name'            => $npc->name,
                'id'              => $npc->id,
                'count'           => $npc->enemy_forces,
                'health'          => $npc->base_health,
                'scale'           => 1,
                'displayId'       => 0, // @TODO
                'creatureType'    => $npc->type->type,
                'level'           => 60,
                'characteristics' => [], // @TODO
                'spells'          => [], // @TODO
                'clones'          => [],
            ];

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
