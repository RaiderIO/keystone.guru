<?php

namespace Database\Seeders;

use App\Models\Npc\NpcClass;
use Illuminate\Database\Seeder;

class NpcClassesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $npcClassAttributes = [];
        foreach (NpcClass::ALL as $key => $id) {
            $npcClassAttributes[] = [
                'id'   => $id,
                'key'  => $key,
                'name' => sprintf('npcclasses.%s', $key),
            ];
        }

        NpcClass::from(DatabaseSeeder::getTempTableName(NpcClass::class))->insert($npcClassAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            NpcClass::class,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
