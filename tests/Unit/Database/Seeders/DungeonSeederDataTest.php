<?php

namespace Tests\Unit\Database\Seeders;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DungeonSeederDataTest extends TestCase
{
    /**
     * Return to Karazhan (instance map 1651) reuses classic Karazhan's (instance map 532) ui maps.
     *
     * @var array<int, array<int, string>>
     */
    private const array DUNGEON_KEYS_SHARING_UI_MAP_IDS_ACROSS_INSTANCE_MAPS = [
        ['karazhan', 'lowerkarazhan'],
        ['karazhan', 'upperkarazhan'],
    ];

    #[Test]
    public function dungeonsJson_givenEverySeededDungeonAndFloor_usesEnglishTranslationKeysForNames(): void
    {
        // Arrange
        $contents = file_get_contents(database_path('seeders/dungeondata/dungeons.json'));
        $this->assertIsString($contents, 'Unable to read dungeons.json.');

        /** @var array<int, array{key: string, name: string, abbreviation: string|null, floors?: array<int, array{name: string}>}> $dungeons */
        $dungeons = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        // Act
        $untranslatableNames = [];
        foreach ($dungeons as $dungeon) {
            $names = [$dungeon['name'], $dungeon['abbreviation']];
            foreach ($dungeon['floors'] ?? [] as $floor) {
                $names[] = $floor['name'];
            }

            foreach (array_filter($names) as $name) {
                if (!str_starts_with($name, 'dungeons.') || !is_string(trans($name, [], 'en_US')) || trans($name, [], 'en_US') === $name) {
                    $untranslatableNames[] = sprintf('%s: %s', $dungeon['key'], $name);
                }
            }
        }

        // Assert
        $this->assertSame([], $untranslatableNames, 'These dungeon/floor names are not en_US translation keys.');
    }

    #[Test]
    public function dungeonsJson_givenFloorsOfDifferentDungeons_onlyShareUiMapIdsOnTheSameInstanceMap(): void
    {
        // Arrange
        $contents = file_get_contents(database_path('seeders/dungeondata/dungeons.json'));
        $this->assertIsString($contents, 'Unable to read dungeons.json.');

        /** @var array<int, array{key: string, map_id: int|null, floors?: array<int, array{ui_map_id: int|null, facade: int}>}> $dungeons */
        $dungeons = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        $allowedDungeonKeyPairs = array_map(static function (array $dungeonKeys): string {
            sort($dungeonKeys);

            return implode('|', $dungeonKeys);
        }, self::DUNGEON_KEYS_SHARING_UI_MAP_IDS_ACROSS_INSTANCE_MAPS);

        // Act
        /** @var array<int, array<string, int|null>> $dungeonMapIdsByUiMapId */
        $dungeonMapIdsByUiMapId = [];
        foreach ($dungeons as $dungeon) {
            foreach ($dungeon['floors'] ?? [] as $floor) {
                // Facade floors carry 0 and some unreleased mappings a negative placeholder
                if ($floor['facade'] || ($floor['ui_map_id'] ?? 0) <= 0) {
                    continue;
                }

                $dungeonMapIdsByUiMapId[$floor['ui_map_id']][$dungeon['key']] = $dungeon['map_id'];
            }
        }

        $collisions = [];
        foreach ($dungeonMapIdsByUiMapId as $uiMapId => $mapIdsByDungeonKey) {
            $dungeonKeys = array_keys($mapIdsByDungeonKey);
            foreach ($dungeonKeys as $index => $dungeonKey) {
                foreach (array_slice($dungeonKeys, $index + 1) as $otherDungeonKey) {
                    if ($mapIdsByDungeonKey[$dungeonKey] === $mapIdsByDungeonKey[$otherDungeonKey]) {
                        continue;
                    }

                    $pair = [$dungeonKey, $otherDungeonKey];
                    sort($pair);
                    if (in_array(implode('|', $pair), $allowedDungeonKeyPairs, true)) {
                        continue;
                    }

                    $collisions[] = sprintf('ui_map_id %d: %s and %s', $uiMapId, $dungeonKey, $otherDungeonKey);
                }
            }
        }

        // Assert
        $this->assertGreaterThan(100, count($dungeonMapIdsByUiMapId), 'Expected dungeons.json to carry floors with a ui_map_id.');
        $this->assertSame([], $collisions, 'Floors of dungeons on different instance maps share a ui_map_id.');
    }
}
