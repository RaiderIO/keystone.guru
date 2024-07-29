<?php

namespace Database\Seeders;

use App\Models\PublishedState;
use Illuminate\Database\Seeder;

class PublishedStatesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publishedStateAttributes = [];
        foreach (PublishedState::ALL as $publishedStateName => $id) {
            $publishedStateAttributes[] = [
                'id'   => $id,
                'name' => $publishedStateName,
            ];
        }

        PublishedState::from(DatabaseSeeder::getTempTableName(PublishedState::class))->insert($publishedStateAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [PublishedState::class];
    }
}
