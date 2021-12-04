<?php

namespace Database\Seeders;

use App\Models\NpcClassification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NpcClassificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known Npc Classifications');

        $npcClassifications = collect([
            new NpcClassification([
                'name'      => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_NORMAL),
                'shortname' => NpcClassification::NPC_CLASSIFICATION_NORMAL,
                'color'     => 'white',
            ]),
            new NpcClassification([
                'name'      => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_ELITE),
                'shortname' => NpcClassification::NPC_CLASSIFICATION_ELITE,
                'color'     => 'yellow',
            ]),
            new NpcClassification([
                'name'      => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_BOSS),
                'shortname' => NpcClassification::NPC_CLASSIFICATION_BOSS,
                'color'     => 'red',
            ]),
            new NpcClassification([
                'name'      => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS),
                'shortname' => NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS,
                'color'     => 'red',
            ]),
        ]);

        foreach ($npcClassifications as $npcClassification) {
            $npcClassification->save();
        }
    }

    private function _rollback()
    {
        DB::table('npc_classifications')->truncate();
    }
}
