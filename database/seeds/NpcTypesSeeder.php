<?php

use App\Models\NpcType;
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

        foreach (NpcType::ALL as $npcTypeName => $id) {
            (new App\Models\NpcType([
                'id'   => $id,
                'type' => $npcTypeName
            ]))->save();
        }
    }

    private function _rollback()
    {
        DB::table('npc_types')->truncate();
    }
}
