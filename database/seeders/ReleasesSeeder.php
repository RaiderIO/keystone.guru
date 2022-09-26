<?php

namespace Database\Seeders;

use App\Models\Release;
use App\Models\ReleaseChangelog;
use App\Models\ReleaseChangelogChange;
use FilesystemIterator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReleasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();

        $this->command->info('Adding releases');
        $rootDir         = database_path('/seeders/releases/');
        $rootDirIterator = new FilesystemIterator($rootDir);

        // Iterate over all saved releases
        foreach ($rootDirIterator as $releaseData) {
            $modelJson = file_get_contents($releaseData);
            // Convert to models
            $modelsData = json_decode($modelJson, true);

            // If it has a changelog (should)
            if (isset($modelsData['changelog'])) {
                $changelogData = $modelsData['changelog'];
                // Changelog
                $changelog = new ReleaseChangelog(array_filter($changelogData, function ($value) {
                    return !is_array($value);
                }));
                $changelog->save();

                // Save the changes for each changelog
                foreach ($changelogData['changes'] as $changeData) {
                    // Changelog changes
                    $changelogChange = new ReleaseChangelogChange(array_filter($changeData, function ($value) {
                        return !is_array($value);
                    }));
                    $changelogChange->save();
                }
            }

            // Save the release last!
            $this->command->info(sprintf('Adding release %s', $modelsData['version']));
            $release = new Release(array_filter($modelsData, function ($value) {
                return !is_array($value);
            }));
            $release->save();
        }
    }

    private function rollback()
    {
        DB::table('releases')->truncate();
        DB::table('release_changelogs')->truncate();
        DB::table('release_changelog_changes')->truncate();
    }
}
