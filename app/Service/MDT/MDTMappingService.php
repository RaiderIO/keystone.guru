<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use Exception;
use Illuminate\Support\Collection;

class MDTMappingService implements MDTMappingServiceInterface
{

    /**
     * @param string $tableName
     * @param array $contents
     * @return string
     */
    private function toLuaTableString(string $tableName, array $contents): string
    {
        // TODO: Implement toLuaTableString() method.
    }

    /**
     * @inheritDoc
     */
    public function getMDTMapping(MappingVersion $mappingVersion): string
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

        dd('test', $dungeonEnemies);

        $dungeonEnemiesLua = $this->toLuaTableString('MDT.dungeonEnemies[dungeonIndex]', $dungeonEnemies);


        return $dungeonEnemiesLua;
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
