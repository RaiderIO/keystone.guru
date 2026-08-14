<?php

namespace Database\Seeders;

use App\Models\MapIconType;
use App\SeederHelpers\Traits\LoadsMapIconTypeData;
use Exception;
use Illuminate\Database\Seeder;

class MapIconTypesSeeder extends Seeder implements TableSeederInterface
{
    use LoadsMapIconTypeData;

    /**
     * Seeds map icon types from database/seeders/mapicontypedata/map_icon_types.json.
     *
     * That file is hand-maintained and holds the data of each type only; the id keeps coming from
     * {@see MapIconType::ALL}, which stays in PHP because the MAP_ICON_TYPE_* constants are referenced
     * throughout the application at compile time.
     *
     * @throws Exception
     */
    public function run(): void
    {
        $mapIconTypes = $this->loadMapIconTypeDataFile('map_icon_types.json');

        $this->assertKeysMatchAllConstant(array_column($mapIconTypes, 'key'));

        $mapIconTypeAttributes = [];
        foreach ($mapIconTypes as $mapIconType) {
            $mapIconTypeAttributes[] = [
                'id'         => MapIconType::ALL[$mapIconType['key']],
                'key'        => $mapIconType['key'],
                'name'       => $mapIconType['name'],
                'width'      => $mapIconType['width'],
                'height'     => $mapIconType['height'],
                'admin_only' => $mapIconType['admin_only'] ?? 0,
            ];
        }

        MapIconType::from(DatabaseSeeder::getTempTableName(MapIconType::class))->insert($mapIconTypeAttributes);
    }

    /**
     * The JSON file supplies the data of a map icon type, MapIconType::ALL supplies its id - a key present
     * in only one of the two would otherwise silently seed nothing, or seed a duplicate id.
     *
     * @param array<int, string> $keys
     *
     * @throws Exception
     */
    private function assertKeysMatchAllConstant(array $keys): void
    {
        $duplicateKeys = array_keys(array_filter(array_count_values($keys), static fn(int $count): bool => $count > 1));
        if ($duplicateKeys !== []) {
            throw new Exception(sprintf('Map icon type data file contains duplicate keys: %s', implode(', ', $duplicateKeys)));
        }

        $keysWithoutId = array_diff($keys, array_keys(MapIconType::ALL));
        if ($keysWithoutId !== []) {
            throw new Exception(sprintf('Map icon type data file contains keys that have no id in MapIconType::ALL: %s', implode(', ', $keysWithoutId)));
        }

        $idsWithoutData = array_diff(array_keys(MapIconType::ALL), $keys);
        if ($idsWithoutData !== []) {
            throw new Exception(sprintf('MapIconType::ALL contains keys that are missing from the map icon type data file: %s', implode(', ', $idsWithoutData)));
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [MapIconType::class];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
