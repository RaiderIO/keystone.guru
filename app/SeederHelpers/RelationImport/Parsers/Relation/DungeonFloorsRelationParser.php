<?php


namespace App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use Database\Seeders\DatabaseSeeder;

class DungeonFloorsRelationParser implements RelationParserInterface
{
    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseRootModel(string $modelClassName): bool
    {
        return false;
    }

    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Dungeon::class;
    }

    /**
     * @param string $name
     * @param array  $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'floors';
    }

    /**
     * @param string $modelClassName
     * @param array  $modelData
     * @param string $name
     * @param array  $value
     * @return array
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $floor) {
            $floor['dungeon_id'] = $modelData['id'];

            foreach ($floor['floorcouplings'] ?? [] as $floorcoupling) {
                FloorCoupling::from(DatabaseSeeder::getTempTableName(FloorCoupling::class))->insert($floorcoupling);
            }

            foreach ($floor['dungeon_speedrun_required_npcs10_man'] ?? [] as $dungeonSpeedrunRequiredNpc) {
                if (!isset($dungeonSpeedrunRequiredNpc['difficulty'])) {
                    $dungeonSpeedrunRequiredNpc['difficulty'] = Dungeon::DIFFICULTY_25_MAN;
                }
                DungeonSpeedrunRequiredNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpc::class))->insert($dungeonSpeedrunRequiredNpc);
            }

            foreach ($floor['dungeon_speedrun_required_npcs25_man'] ?? [] as $dungeonSpeedrunRequiredNpc) {
                if (!isset($dungeonSpeedrunRequiredNpc['difficulty'])) {
                    $dungeonSpeedrunRequiredNpc['difficulty'] = Dungeon::DIFFICULTY_25_MAN;
                }
                DungeonSpeedrunRequiredNpc::from(DatabaseSeeder::getTempTableName(DungeonSpeedrunRequiredNpc::class))->insert($dungeonSpeedrunRequiredNpc);
            }

            unset($floor['floorcouplings']);
            unset($floor['dungeon_speedrun_required_npcs10_man']);
            unset($floor['dungeon_speedrun_required_npcs25_man']);

            Floor::from(DatabaseSeeder::getTempTableName(Floor::class))->insert($floor);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
