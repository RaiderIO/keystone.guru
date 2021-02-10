<?php

namespace Database\Seeders;

use App\Models\NpcClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $npcClasses = [
            new NpcClass([
                'name' => 'Melee'
            ]), new NpcClass([
                'name' => 'Ranged'
            ]), new NpcClass([
                'name' => 'Caster'
            ]), new NpcClass([
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
