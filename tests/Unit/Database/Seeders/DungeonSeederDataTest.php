<?php

namespace Tests\Unit\Database\Seeders;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DungeonSeederDataTest extends TestCase
{
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
}
