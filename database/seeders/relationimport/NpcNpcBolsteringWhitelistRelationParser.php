<?php

namespace Database\Seeders\RelationImport;

use App\Models\NpcBolsteringWhitelist;

class NpcNpcBolsteringWhitelistRelationParser implements RelationParser
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
        return $name === 'npcbolsteringwhitelists';
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
        foreach ($value as $npcBolsteringWhitelist) {
            NpcBolsteringWhitelist::insert($npcBolsteringWhitelist);
        }

        // Didn't really change anything so just return the value.
        return $modelData;
    }

}