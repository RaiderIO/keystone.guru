<?php

namespace Tests\Unit\MapTiles;

use App\Models\Dungeon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class MapTilesExistenceTest extends PublicTestCase
{
    #[Test]
    #[Group('MapTiles')]
    public function mapTilesExistence_givenDungeon_shouldHaveAllMapTilesAvailable(): void
    {
        // Arrange
        $zoomLevels = 5;
        /** @var Collection<Dungeon> $dungeons */
        $dungeons = Dungeon::all();

        // Act & Assert
        foreach ($dungeons as $dungeon) {
            if (in_array($dungeon->key, ['prioryofthesacredflame', 'therookery', // Missing MDT floor (but it's already created since I expect it to come)
                                         'auchindoun', 'bloodmaul_slag_mines', // Prematurely created - no tiles exist for these yet
            ])) {
                continue;
            }

            foreach ($dungeon->floors as $floor) {

                $floorDirectory = public_path(
                    sprintf('images/tiles/%s/%s/%d', $dungeon->expansion->shortname, $dungeon->key, $floor->index)
                );
                Assert::assertDirectoryExists($floorDirectory);

                for ($zoomLevel = 1; $zoomLevel <= $zoomLevels; $zoomLevel++) {
                    $maxX = 2 ** $zoomLevel;
                    $maxY = 2 ** $zoomLevel;

                    $zoomLevelDirectory = sprintf('%s/%d', $floorDirectory, $zoomLevel);
                    Assert::assertDirectoryExists($zoomLevelDirectory);

                    for ($x = 0; $x < $maxX; $x++) {
                        for ($y = 0; $y < $maxY; $y++) {
                            Assert::assertFileExists(sprintf('%s/%d_%d.png', $zoomLevelDirectory, $x, $y));
                        }
                    }
                }
            }
        }
    }
}
