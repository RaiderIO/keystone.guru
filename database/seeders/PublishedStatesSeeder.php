<?php

namespace Database\Seeders;

use App\Models\PublishedState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublishedStatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();

        $this->command->info('Adding Published States');

        foreach (PublishedState::ALL as $publishedStateName => $id) {
            PublishedState::create([
                'id'   => $id,
                'name' => $publishedStateName,
            ]);
        }
    }

    private function rollback()
    {
        DB::table('published_states')->truncate();
    }
}
