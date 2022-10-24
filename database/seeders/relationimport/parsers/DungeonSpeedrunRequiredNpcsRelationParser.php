<?php


namespace Database\Seeders\RelationImport\Parsers;

use App\Models\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;

class DungeonSpeedrunRequiredNpcsRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === Floor::class;
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'dungeonspeedrunrequirednpcs';
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
        foreach ($value as $dungeonSpeedrunRequiredNpc) {
            $dungeonSpeedrunRequiredNpc['floor_id'] = $modelData['id'];
            DungeonSpeedrunRequiredNpc::create($dungeonSpeedrunRequiredNpc);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
