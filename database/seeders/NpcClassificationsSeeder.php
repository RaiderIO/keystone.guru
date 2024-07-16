<?php

namespace Database\Seeders;

use App\Models\Npc\NpcClassification;
use Illuminate\Database\Seeder;

class NpcClassificationsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Adding known Npc Classifications');

        $npcClassificationAttributes = [
            [
                'id'    => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
                'name'  => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_NORMAL),
                'key'   => NpcClassification::NPC_CLASSIFICATION_NORMAL,
                'color' => 'white',
            ], [
                'id'    => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
                'name'  => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_ELITE),
                'key'   => NpcClassification::NPC_CLASSIFICATION_ELITE,
                'color' => 'yellow',
            ], [
                'id'    => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                'name'  => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_BOSS),
                'key'   => NpcClassification::NPC_CLASSIFICATION_BOSS,
                'color' => 'red',
            ], [
                'id'    => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
                'name'  => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS),
                'key'   => NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS,
                'color' => 'red',
            ], [
                'id'    => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
                'name'  => sprintf('npcclassifications.%s', NpcClassification::NPC_CLASSIFICATION_RARE),
                'key'   => NpcClassification::NPC_CLASSIFICATION_RARE,
                'color' => 'red',
            ],
        ];

        NpcClassification::from(DatabaseSeeder::getTempTableName(NpcClassification::class))->insert($npcClassificationAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [NpcClassification::class];
    }
}
