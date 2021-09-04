<?php

namespace Database\Seeders\RelationImport\Parsers;

use App\Models\NpcSpell;

class NpcNpcSpellsRelationParser implements RelationParser
{
    /**
     * @param $modelClassName string
     * @return mixed
     */
    public function canParseModel($modelClassName)
    {
        return $modelClassName === 'App\Models\Npc';
    }

    /**
     * @param $name string
     * @param $value array
     * @return mixed
     */
    public function canParseRelation($name, $value)
    {
        return $name === 'npcspells';
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
        foreach ($value as $spell) {
            NpcSpell::insert($spell);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}
