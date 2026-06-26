<?php

namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use Database\Seeders\DatabaseSeeder;

class DungeonFloorsRelationParser implements RelationParserInterface
{
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Dungeon::class;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'floors';
    }

    /**
     * @param  array<string, mixed> $modelData
     * @param  array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $floor) {
            $floor['dungeon_id'] = $modelData['id'];

            foreach ($floor['floorcouplings'] ?? [] as $floorcoupling) {
                FloorCoupling::from(DatabaseSeeder::getTempTableName(FloorCoupling::class))->insert($floorcoupling);
            }

            foreach ($floor['dungeon_speedrun_required_npcs'] ?? [] as $dungeonSpeedrunRequiredNpc) {
                $npcEntries = $dungeonSpeedrunRequiredNpc['dungeon_speedrun_required_npc_npcs'] ?? [];
                unset($dungeonSpeedrunRequiredNpc['dungeon_speedrun_required_npc_npcs']);

                DungeonSpeedrunRequiredNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpc::class))
                    ->insert($dungeonSpeedrunRequiredNpc);

                foreach ($npcEntries as $npcEntry) {
                    $npcEntry['dungeon_speedrun_required_npc_id'] = $dungeonSpeedrunRequiredNpc['id'];
                    DungeonSpeedrunRequiredNpcNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpcNpc::class))
                        ->insert($npcEntry);
                }
            }

            unset($floor['floorcouplings']);
            unset($floor['dungeon_speedrun_required_npcs']);

            Floor::from(DatabaseSeeder::getTempTableName(Floor::class))->insert($floor);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }
}
