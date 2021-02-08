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
        $this->_rollback();

        $this->command->info('Adding releases');
        $rootDir = database_path('/seeders/releases/');
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
                $changelog = new ReleaseChangelog([
                    'id'          => $changelogData['id'],
                    'release_id'  => $changelogData['release_id'],
                    'description' => $changelogData['description'],
                ]);
                $changelog->save();

                // Save the changes for each changelog
                foreach ($changelogData['changes'] as $changeData) {
                    // Changelog changes
                    $changelogChange = new ReleaseChangelogChange([
                        'release_changelog_id'          => $changeData['release_changelog_id'],
                        'release_changelog_category_id' => $changeData['release_changelog_category_id'],
                        'ticket_id'                     => $changeData['ticket_id'],
                        'change'                        => $changeData['change'],
                    ]);
                    $changelogChange->save();
                }
            }

            // Save the release last!
            $this->command->info(sprintf('Adding release %s', $modelsData['version']));
            $release = new Release([
                'id'                   => $modelsData['id'],
                'release_changelog_id' => $modelsData['release_changelog_id'],
                'version'              => $modelsData['version'],
                'silent'               => $modelsData['silent'],
                'created_at'           => $modelsData['created_at'],
                'updated_at'           => $modelsData['updated_at'],
            ]);
            $release->save();
        }
    }

    private function _rollback()
    {
        DB::table('releases')->truncate();
        DB::table('release_changelogs')->truncate();
        DB::table('release_changelog_changes')->truncate();
    }
}
