<?php

namespace Database\Seeders;

use App\Models\NpcType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NpcTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();

        $this->command->info('Adding known Npc types');

        foreach (NpcType::ALL as $npcTypeName => $id) {
            (new NpcType([
                'id'   => $id,
                'type' => $npcTypeName,
            ]))->save();
        }
    }

    private function rollback()
    {
        DB::table('npc_types')->truncate();
    }
}
