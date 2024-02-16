<?php

namespace Database\Seeders;

use App\Models\NpcClass;
use Illuminate\Database\Seeder;

class NpcClassesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Adding known Npc classes');

        $npcClassAttributes = [];
        foreach (NpcClass::ALL as $key) {
            $npcClassAttributes[] = [
                'key'  => $key,
                'name' => sprintf('npcclasses.%s', $key),
            ];
        }

        NpcClass::insert($npcClassAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            NpcClass::class,
        ];
    }
}
