<?php

use App\Models\PublishedState;
use Illuminate\Database\Seeder;

class PublishedStatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding Published States');

        foreach (PublishedState::ALL as $publishedStateName) {
            (new PublishedState(['name' => $publishedStateName]))->save();
        }
    }

    private function _rollback()
    {
        DB::table('published_states')->truncate();
    }
}
