<?php

namespace Database\Seeders;

use App\Models\ReleaseChangelogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReleaseChangelogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();
        $this->command->info('Adding known release changelog categories');

        foreach (ReleaseChangelogCategory::ALL as $key => $id) {
            ReleaseChangelogCategory::create([
                'id'   => $id,
                'key'  => $key,
                'name' => sprintf('releasechangelogcategories.%s', $key),
            ]);
        }
    }

    private function rollback()
    {
        DB::table('release_changelog_categories')->truncate();
    }
}
