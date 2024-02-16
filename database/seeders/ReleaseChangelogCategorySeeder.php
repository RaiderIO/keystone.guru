<?php

namespace Database\Seeders;

use App\Models\ReleaseChangelogCategory;
use Illuminate\Database\Seeder;

class ReleaseChangelogCategorySeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Adding known release changelog categories');

        $releaseChangelogCategoryAttributes = [];
        foreach (ReleaseChangelogCategory::ALL as $key => $id) {
            $releaseChangelogCategoryAttributes[] = [
                'id'   => $id,
                'key'  => $key,
                'name' => sprintf('releasechangelogcategories.%s', $key),
            ];
        }
        ReleaseChangelogCategory::insert($releaseChangelogCategoryAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [ReleaseChangelogCategory::class];
    }
}
