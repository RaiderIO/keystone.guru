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
            if (in_array($dungeon->key, [
                Dungeon::DUNGEON_PRIORY_OF_THE_SACRED_FLAME,
                Dungeon::DUNGEON_THE_ROOKERY, // Missing MDT floor (but it's already created since I expect it to come)
                Dungeon::DUNGEON_AUCHINDOUN,
                Dungeon::DUNGEON_BLOODMAUL_SLAG_MINES,
                Dungeon::DUNGEON_BLOODMAUL_SLAG_MINES, // Not implemented
                Dungeon::DUNGEON_DEN_OF_NALORAKK, // Missing first map
                Dungeon::DUNGEON_VOIDSCAR_ARENA, // Not implemented
                Dungeon::RAID_ONYXIAS_LAIR_WOTLK,
                Dungeon::RAID_ONYXIAS_LAIR,
                Dungeon::RAID_RUINS_OF_AHN_QIRAJ,
                Dungeon::RAID_TEMPLE_OF_AHN_QIRAJ,
                Dungeon::RAID_NAXXRAMAS,
                // Prematurely created - no tiles exist for these yet
            ])) {
                continue;
            }

            foreach ($dungeon->floors as $floor) {
                $basePath = base_path(
                    sprintf('../keystone.guru.assets/tiles/%s/%s/%d', $dungeon->expansion->shortname, $dungeon->key, $floor->index),
                );
                $floorDirectory = realpath($basePath);
                Assert::assertDirectoryExists($floorDirectory, $basePath);

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
