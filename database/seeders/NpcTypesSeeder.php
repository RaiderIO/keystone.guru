<?php

namespace Database\Seeders;

use App\Models\Npc\NpcType;
use Illuminate\Database\Seeder;

class NpcTypesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $npcTypeAttributes = [];
        foreach (NpcType::ALL as $npcTypeName => $id) {
            $npcTypeAttributes[] = [
                'id'   => $id,
                'type' => $npcTypeName,
            ];
        }

        NpcType::from(DatabaseSeeder::getTempTableName(NpcType::class))->insert($npcTypeAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [NpcType::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
