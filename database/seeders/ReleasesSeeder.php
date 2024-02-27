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
        $this->command->info('Adding releases');
        $rootDir         = database_path('seeders/releases/');
        $rootDirIterator = new FilesystemIterator($rootDir);

        // Keep a list of all data that we must insert
        $releaseChangeLogChangesAttributes = [];
        $releaseChangeLogAttributes        = [];
        $releaseAttributes                 = [];

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
            /** @var array{created_at: \Carbon\Carbon, updated_at: \Carbon\Carbon} $releaseAttribute */
            $releaseAttribute = array_filter($modelsData, function ($value) {
                return !is_array($value);
            });

            $releaseAttribute['created_at'] = Carbon::createFromFormat(Release::$SERIALIZED_DATE_TIME_FORMAT, $releaseAttribute['created_at'])->toDateTimeString();
            $releaseAttribute['updated_at'] = Carbon::createFromFormat(Release::$SERIALIZED_DATE_TIME_FORMAT, $releaseAttribute['updated_at'])->toDateTimeString();

            $releaseAttributes[] = $releaseAttribute;
        }

        $this->command->info(sprintf('Inserting %d releases..', count($releaseAttributes)));

        $result = Release::from(DatabaseSeeder::getTempTableName(Release::class))->insert($releaseAttributes) &&
            ReleaseChangelog::from(DatabaseSeeder::getTempTableName(ReleaseChangelog::class))->insert($releaseChangeLogAttributes) &&
            ReleaseChangelogChange::from(DatabaseSeeder::getTempTableName(ReleaseChangelogChange::class))->insert($releaseChangeLogChangesAttributes);

        if ($result) {
            $this->command->info(sprintf('Inserting %d releases OK', count($releaseAttributes)));
        } else {
            $this->command->warn(sprintf('Inserting %d releases FAILED', count($releaseAttributes)));
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            Release::class,
            ReleaseChangelog::class,
            ReleaseChangelogChange::class,
        ];
    }
}
