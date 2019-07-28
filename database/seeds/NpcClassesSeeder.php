<?php

use Illuminate\Database\Seeder;

class NpcClassesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known Npc classes');

        $npcClasses = [new App\Models\NpcClass([
            'name' => 'Melee'
        ]), new App\Models\NpcClass([
            'name' => 'Ranged'
        ]), new App\Models\NpcClass([
            'name' => 'Caster'
        ]), new App\Models\NpcClass([
            'name' => 'Healer'
        ])
        ];


        foreach ($npcClasses as $npcClass) {
            $npcClass->save();
        }
    }

    private function _rollback()
    {
        DB::table('npc_classes')->truncate();
    }
}
