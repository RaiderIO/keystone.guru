<?php

namespace Tests\Unit\MapTiles;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\RaidKey;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('MapTiles')]
#[Group('Nightly')]
#[SlowTest]
final class MapTilesExistenceTest extends PublicTestCase
{
    /**
     * The tiles directory is gitignored in keystone.guru.assets (it is synced outside git), so it only
     * exists on a developer machine; a CI runner never has it.
     */
    private const string TILES_PATH = '../keystone.guru.assets/tiles';

    #[Test]
    public function mapTilesExistence_givenDungeon_shouldHaveAllMapTilesAvailable(): void
    {
        // Arrange
        if (realpath(base_path(self::TILES_PATH)) === false) {
            $this->markTestSkipped(sprintf('%s is not present on this machine', self::TILES_PATH));
        }

        $zoomLevels = 5;
        /** @var Collection<int, Dungeon> $dungeons */
        $dungeons = Dungeon::with('floors')->get();

        // Act & Assert
        foreach ($dungeons as $dungeon) {
            if (in_array($dungeon->key, [
                DungeonKey::PRIORY_OF_THE_SACRED_FLAME->value,
                DungeonKey::THE_ROOKERY->value, // Missing MDT floor (but it's already created since I expect it to come)
                DungeonKey::AUCHINDOUN->value,
                DungeonKey::BLOODMAUL_SLAG_MINES->value,
                DungeonKey::BLOODMAUL_SLAG_MINES->value, // Not implemented
                DungeonKey::DEN_OF_NALORAKK->value, // Missing first map
                DungeonKey::VOIDSCAR_ARENA->value, // Not implemented
                RaidKey::ONYXIAS_LAIR_WOTLK->value,
                RaidKey::ONYXIAS_LAIR->value,
                RaidKey::RUINS_OF_AHN_QIRAJ->value,
                RaidKey::TEMPLE_OF_AHN_QIRAJ->value,
                RaidKey::NAXXRAMAS->value,
                // Prematurely created - no tiles exist for these yet
            ])) {
                continue;
            }

            foreach ($dungeon->floors as $floor) {
                $basePath = base_path(
                    sprintf('%s/%s/%s/%d', self::TILES_PATH, $dungeon->expansion->shortname, $dungeon->key, $floor->index),
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
