<?php


namespace Database\Seeders\RelationImport\Parsers;

use App\Models\Floor;
use App\Models\FloorCoupling;

class DungeonFloorsRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === 'App\Models\Dungeon';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'floors';
    }

    /**
     * @param $modelClassName string
     * @param $modelData array
     * @param $name string
     * @param $value array
     * @return array
     */
    public function parseRelation($modelClassName, $modelData, $name, $value)
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
