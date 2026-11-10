<?php

namespace Tests\Feature\App\Service\RaiderIO\Mock;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Service\RaiderIO\Mock\ContinentHeatmapFixtureGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('ContinentHeatmapFixtureGenerator')]
final class ContinentHeatmapFixtureGeneratorTest extends PublicTestCase
{
    private const int GRID_SIZE_X = 30;

    private const int GRID_SIZE_Y = 20;

    #[Test]
    #[DataProvider('continentProvider')]
    public function generate_givenContinent_fillsEveryZoneFloorWithCellsOnItsGrid(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon      = Dungeon::with('floors')->where('key', $dungeonKey->value)->firstOrFail();
        $cellsPerZone = 50;

        // Act
        $grid = new ContinentHeatmapFixtureGenerator()->generate($dungeon, $cellsPerZone, self::GRID_SIZE_X, self::GRID_SIZE_Y, 1);

        // Assert
        $zoneFloors = $dungeon->floors->where('facade', false)->keyBy('id');
        $this->assertGreaterThan(1, $zoneFloors->count());
        $this->assertEqualsCanonicalizing($zoneFloors->keys()->all(), array_keys($grid['gridsByFloor']));

        $counts = [];
        foreach ($grid['gridsByFloor'] as $floorId => $rows) {
            /** @var Floor $floor */
            $floor = $zoneFloors->get($floorId);
            $cells = array_chunk($rows, 3);
            $this->assertGreaterThanOrEqual($cellsPerZone, count($cells));

            $stepX = ($floor->ingame_max_x - $floor->ingame_min_x) / self::GRID_SIZE_X;
            $stepY = ($floor->ingame_max_y - $floor->ingame_min_y) / self::GRID_SIZE_Y;
            foreach ($cells as [$x, $y, $count]) {
                $gridX = ($x - $floor->ingame_min_x) / $stepX;
                $gridY = ($y - $floor->ingame_min_y) / $stepY;
                $this->assertEqualsWithDelta(round($gridX), $gridX, 0.01);
                $this->assertEqualsWithDelta(round($gridY), $gridY, 0.01);
                $this->assertGreaterThanOrEqual(0, round($gridX));
                $this->assertLessThan(self::GRID_SIZE_X, round($gridX));
                $this->assertGreaterThanOrEqual(0, round($gridY));
                $this->assertLessThan(self::GRID_SIZE_Y, round($gridY));
                $this->assertGreaterThan(0, $count);
                $counts[] = $count;
            }
        }

        $this->assertSame(array_sum($counts), $grid['numRuns']);
        $this->assertSame(max($counts), $grid['maxSamplesInGrid']);
    }

    /**
     * @return array<string, array{0: DungeonKey}>
     */
    public static function continentProvider(): array
    {
        return [
            'kalimdor'         => [DungeonKey::KALIMDOR],
            'eastern kingdoms' => [DungeonKey::EASTERN_KINGDOMS],
        ];
    }

    #[Test]
    public function generate_givenTheSameSeedTwice_returnsTheSameGrid(): void
    {
        // Arrange
        $dungeon   = Dungeon::with('floors')->where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        $generator = new ContinentHeatmapFixtureGenerator();

        // Act
        $first       = $generator->generate($dungeon, 20, self::GRID_SIZE_X, self::GRID_SIZE_Y, 7);
        $second      = $generator->generate($dungeon, 20, self::GRID_SIZE_X, self::GRID_SIZE_Y, 7);
        $otherSeeded = $generator->generate($dungeon, 20, self::GRID_SIZE_X, self::GRID_SIZE_Y, 8);

        // Assert
        $this->assertSame($first, $second);
        $this->assertNotSame($first, $otherSeeded);
    }
}
