<?php

namespace App\SeederHelpers\Traits;

use Exception;

/**
 * Reads the map icon type data files in database/seeders/mapicontypedata/.
 *
 * Unlike the season data files these are hand-maintained - map icon types are not managed through the
 * admin panel, so there is no `mapping:save` exporter for them. Adding a type means adding both a
 * MAP_ICON_TYPE_* constant with its id to {@see \App\Models\MapIconType::ALL} and a matching object to
 * the JSON file; the seeder fails loudly when the two drift apart.
 */
trait LoadsMapIconTypeData
{
    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function loadMapIconTypeDataFile(string $fileName): array
    {
        $filePath = database_path(sprintf('seeders/mapicontypedata/%s', $fileName));

        if (!file_exists($filePath)) {
            throw new Exception(sprintf('Unable to find map icon type data file %s', $filePath));
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new Exception(sprintf('Unable to read map icon type data file %s', $filePath));
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            throw new Exception(sprintf('Unable to parse map icon type data file %s as JSON', $filePath));
        }

        if ($decoded === []) {
            throw new Exception(sprintf('Map icon type data file %s is empty - refusing to seed, this would wipe the table', $filePath));
        }

        return $decoded;
    }
}
