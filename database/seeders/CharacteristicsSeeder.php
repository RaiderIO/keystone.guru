<?php

namespace Database\Seeders;

use App\Models\Characteristic;
use Illuminate\Database\Seeder;

class CharacteristicsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $characteristicsAttributes = [];

        foreach (Characteristic::ALL as $key => $id) {
            $characteristicsAttributes[] = [
                'id'   => $id,
                'name' => sprintf('characteristics.%s', $key),
                'key'  => $key,
            ];
        }

        Characteristic::from(DatabaseSeeder::getTempTableName(Characteristic::class))->insert($characteristicsAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [Characteristic::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
