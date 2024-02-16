<?php

namespace Database\Seeders;

use App\Models\PublishedState;
use Illuminate\Database\Seeder;

class PublishedStatesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Adding Published States');

        $publishedStateAttributes = [];
        foreach (PublishedState::ALL as $publishedStateName => $id) {
            $publishedStateAttributes[] = [
                'id'   => $id,
                'name' => $publishedStateName,
            ];
        }

        PublishedState::insert($publishedStateAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [PublishedState::class];
    }
}
