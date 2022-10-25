<?php


namespace Database\Seeders\RelationImport\Parsers;

use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\FloorCoupling;

class DungeonFloorsRelationParser implements RelationParser
{
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
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'floors';
    }

    /**
     * @param string $modelClassName
     * @param array $modelData
     * @param string $name
     * @param array $value
     * @return array
     */
    public function parseRelation(string $modelClassName, array $modelData, string $name, array $value): array
    {
        foreach ($value as $floor) {
            $floor['dungeon_id'] = $modelData['id'];

            foreach ($floor['floorcouplings'] as $floorcoupling) {
                FloorCoupling::insert($floorcoupling);
            }

            unset($floor['floorcouplings']);
            Floor::insert($floor);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
