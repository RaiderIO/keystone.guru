<?php

use App\Models\ReleaseChangelogCategory;
use Illuminate\Database\Seeder;

class ReleaseChangelogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known release changelog categories');

        $categories = [
            new ReleaseChangelogCategory(['category' => 'General changes']),
            new ReleaseChangelogCategory(['category' => 'Route changes']),
            new ReleaseChangelogCategory(['category' => 'Map changes']),
            new ReleaseChangelogCategory(['category' => 'Mapping changes']),
            new ReleaseChangelogCategory(['category' => 'Bugfixes']),
            new ReleaseChangelogCategory(['category' => 'MDT importer changes']),
            new ReleaseChangelogCategory(['category' => 'Team changes']),
        ];

        foreach ($categories as $category) {
            /** @var $category \Illuminate\Database\Eloquent\Model */
            $category->save();
        }
    }

    private function _rollback()
    {
        DB::table('release_changelog_categories')->truncate();
    }
}
