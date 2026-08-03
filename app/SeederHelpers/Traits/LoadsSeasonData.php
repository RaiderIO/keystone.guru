<?php

namespace App\SeederHelpers\Traits;

use Exception;

/**
 * Reads the season data files that `php artisan mapping:save` generates into database/seeders/seasondata/.
 *
 * These files are generated from the database - they are not meant to be edited by hand. Edit the data
 * in the admin panel, run mapping:save, and commit the resulting diff.
 */
trait LoadsSeasonData
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    private function loadSeasonDataFile(string $fileName): array
    {
        $filePath = database_path(sprintf('seeders/seasondata/%s', $fileName));

        if (!file_exists($filePath)) {
            throw new Exception(sprintf('Unable to find season data file %s - run `php artisan mapping:save` to generate it', $filePath));
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new Exception(sprintf('Unable to read season data file %s', $filePath));
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            throw new Exception(sprintf('Unable to parse season data file %s as JSON', $filePath));
        }

        if ($decoded === []) {
            throw new Exception(sprintf('Season data file %s is empty - refusing to seed, this would wipe the table', $filePath));
        }

        return $decoded;
    }
}
