<?php

use Illuminate\Database\Seeder;

class NpcTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known Npc types');

        $npcTypes = [new App\Models\NpcType([
            'type' => 'Aberration'
        ]), new App\Models\NpcType([
            'type' => 'Beast'
        ]), new App\Models\NpcType([
            'type' => 'Critter'
        ]), new App\Models\NpcType([
            'type' => 'Demon'
        ]), new App\Models\NpcType([
            'type' => 'Dragonkin'
        ]), new App\Models\NpcType([
            'type' => 'Elemental'
        ]), new App\Models\NpcType([
            'type' => 'Giant'
        ]), new App\Models\NpcType([
            'type' => 'Humanoid'
        ]), new App\Models\NpcType([
            'type' => 'Mechanical'
        ]), new App\Models\NpcType([
            'type' => 'Undead'
        ]), new App\Models\NpcType([
            'type' => 'Uncategorized'
        ])
        ];


        foreach ($npcTypes as $npcType) {
            $npcType->save();
        }
    }

    private function _rollback()
    {
        DB::table('npc_types')->truncate();
    }
}
