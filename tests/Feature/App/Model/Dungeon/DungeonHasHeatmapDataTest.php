<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Season;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('DungeonHeatmap')]
final class DungeonHasHeatmapDataTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('hasHeatmapData_givenDungeonAndSeason_DataProvider')]
    public function hasHeatmapData_givenDungeonAndSeason_returnsWhetherThereIsData(
        string $key,
        ?int   $mapId,
        bool   $withSeason,
        bool   $expected,
    ): void {
        // Arrange
        $dungeon = new Dungeon(['key' => $key, 'map_id' => $mapId]);
        $season  = $withSeason ? new Season() : null;

        // Act
        $result = $dungeon->hasHeatmapData($season);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{key: string, mapId: int|null, withSeason: bool, expected: bool}>
     */
    public static function hasHeatmapData_givenDungeonAndSeason_DataProvider(): array
    {
        return [
            'instance with a season' => [
                'key'        => DungeonKey::THE_ROOKERY->value,
                'mapId'      => 2648,
                'withSeason' => true,
                'expected'   => true,
            ],
            'instance without a season' => [
                'key'        => DungeonKey::THE_ROOKERY->value,
                'mapId'      => 2648,
                'withSeason' => false,
                'expected'   => false,
            ],
            'continent without a season' => [
                'key'        => DungeonKey::KALIMDOR->value,
                'mapId'      => 1,
                'withSeason' => false,
                'expected'   => true,
            ],
            'continent with map id 0 without a season' => [
                'key'        => DungeonKey::EASTERN_KINGDOMS->value,
                'mapId'      => 0,
                'withSeason' => false,
                'expected'   => true,
            ],
            'continent without a map id' => [
                'key'        => DungeonKey::KALIMDOR->value,
                'mapId'      => null,
                'withSeason' => false,
                'expected'   => false,
            ],
            'continent without a map id with a season' => [
                'key'        => DungeonKey::KALIMDOR->value,
                'mapId'      => null,
                'withSeason' => true,
                'expected'   => false,
            ],
        ];
    }
}
