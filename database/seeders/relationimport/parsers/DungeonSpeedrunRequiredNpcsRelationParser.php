<?php


namespace Database\Seeders\RelationImport\Parsers;

use App\Models\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;

class DungeonSpeedrunRequiredNpcsRelationParser implements RelationParser
{
    /**
     * @param string $modelClassName
     * @return bool
     */
    public function canParseModel(string $modelClassName): bool
    {
        return $modelClassName === Floor::class;
    }

    /**
     * @param string $name
     * @param array $value
     * @return bool
     */
    public function canParseRelation(string $name, array $value): bool
    {
        return $name === 'dungeonspeedrunrequirednpcs';
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
        foreach ($value as $dungeonSpeedrunRequiredNpc) {
            $dungeonSpeedrunRequiredNpc['floor_id'] = $modelData['id'];
            DungeonSpeedrunRequiredNpc::create($dungeonSpeedrunRequiredNpc);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
