<?php

namespace Database\Seeders;

use App\Models\Release;
use App\Models\ReleaseChangelog;
use App\Models\ReleaseChangelogChange;
use FilesystemIterator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReleasesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootDir         = database_path('seeders/releases/');
        $rootDirIterator = new FilesystemIterator($rootDir);

        // Keep a list of all data that we must insert
        $releaseChangeLogChangesAttributes = [];
        $releaseChangeLogAttributes        = [];
        $releaseAttributes                 = [];

        $existingReleases = Release::all()->keyBy('id');

        // Iterate over all saved releases
        foreach ($rootDirIterator as $releaseData) {
            $modelJson = file_get_contents($releaseData);
            // Convert to models
            $modelsData = json_decode($modelJson, true);

            // If it has a changelog (should)
            if (isset($modelsData['changelog'])) {
                $changelogData = $modelsData['changelog'];
                // Changelog
                $releaseChangeLogAttributes[] = array_filter($changelogData, function ($value) {
                    return !is_array($value);
                });

                // Save the changes for each changelog
                foreach ($changelogData['changes'] as $changeData) {
                    // Changelog changes
                    $releaseChangeLogChangesAttributes[] = array_filter($changeData, function ($value) {
                        return !is_array($value);
                    });
                }
            }

            // Save the release last!
            /** @var array{created_at: Carbon, updated_at: Carbon} $releaseAttribute */
            $releaseAttribute = array_filter($modelsData, function ($value) {
                return !is_array($value);
            });

            $releaseAttribute['created_at'] = Carbon::createFromFormat(Release::SERIALIZED_DATE_TIME_FORMAT, $releaseAttribute['created_at'])->toDateTimeString();
            $releaseAttribute['updated_at'] = Carbon::createFromFormat(Release::SERIALIZED_DATE_TIME_FORMAT, $releaseAttribute['updated_at'])->toDateTimeString();

            if($existingReleases->has($releaseAttribute['id'])){
                /** @var Release $existingRelease */
                $existingRelease = $existingReleases->get($releaseAttribute['id']);

                $releaseAttribute['released'] = $existingRelease->released;
            } else {
                $releaseAttributes[] = $releaseAttribute;
            }
            $releaseAttributes[] = $releaseAttribute;
        }

        Release::from(DatabaseSeeder::getTempTableName(Release::class))->insert($releaseAttributes) &&
        ReleaseChangelog::from(DatabaseSeeder::getTempTableName(ReleaseChangelog::class))->insert($releaseChangeLogAttributes) &&
        ReleaseChangelogChange::from(DatabaseSeeder::getTempTableName(ReleaseChangelogChange::class))->insert($releaseChangeLogChangesAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            Release::class,
            ReleaseChangelog::class,
            ReleaseChangelogChange::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
