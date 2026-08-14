<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\MapIconType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the invariants of the map icon type data that MapIconTypesSeeder imports from
 * database/seeders/mapicontypedata/.
 *
 * The file is hand-maintained and holds no ids - those come from MapIconType::ALL - so the two halves
 * can drift apart without anything else noticing until a map icon renders as "unknown".
 */
#[Group('MapIconTypesSeeder')]
final class MapIconTypesSeederTest extends PublicTestCase
{
    #[Test]
    public function run_givenDataFile_mirrorsTheAllConstant(): void
    {
        // Arrange - the data file keeps the order the seeder's array literal had, MapIconType::ALL its own,
        // and neither matters to the seeder: it resolves each row's id by key.
        $expected = array_keys(MapIconType::ALL);
        sort($expected);

        // Act
        $actual = array_column($this->loadDataFile(), 'key');
        sort($actual);

        // Assert
        $this->assertSame(
            $expected,
            $actual,
            'The map icon type data file no longer matches MapIconType::ALL. Adding a map icon type requires adding its MAP_ICON_TYPE_* constant and ALL entry too.',
        );
    }

    #[Test]
    public function run_givenDataFile_holdsEveryAttributeTheSeederInserts(): void
    {
        // Arrange & Act - admin_only is optional and defaults to 0, the rest are not nullable columns.
        $mapIconTypes = $this->loadDataFile();

        // Assert
        foreach ($mapIconTypes as $mapIconType) {
            $key = $mapIconType['key'] ?? '<missing key>';

            $this->assertIsString($mapIconType['name'] ?? null, sprintf('Map icon type %s has no name.', $key));
            $this->assertIsInt($mapIconType['width'] ?? null, sprintf('Map icon type %s has no width.', $key));
            $this->assertIsInt($mapIconType['height'] ?? null, sprintf('Map icon type %s has no height.', $key));
        }

        $this->assertNotEmpty($mapIconTypes);
    }

    #[Test]
    public function run_givenSeededDatabase_seedsEveryMapIconTypeOfTheDataFile(): void
    {
        // Arrange
        $expected = MapIconType::ALL;
        ksort($expected);

        // Act
        $actual = MapIconType::query()
            ->pluck('id', 'key')
            ->all();
        ksort($actual);

        // Assert
        $this->assertSame($expected, $actual, 'The seeded map icon types no longer match MapIconType::ALL - re-seed MapIconTypesSeeder.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadDataFile(): array
    {
        $contents = file_get_contents(database_path('seeders/mapicontypedata/map_icon_types.json'));

        $this->assertIsString($contents, 'Unable to read the map icon type data file.');

        return json_decode($contents, true);
    }
}
