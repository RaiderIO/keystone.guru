<?php

namespace Tests\Unit\App\Service\RaiderIO\Dtos;

use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapHotspot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOHeatmapHotspot')]
final class RaiderIOHeatmapHotspotTest extends PublicTestCase
{
    /**
     * @param list<array{0: float|int|string, 1: float|int|string}> $polygon
     */
    #[Test]
    #[DataProvider('squareProvider')]
    public function fromArray_givenSquare_returnsItsAreaAndCentroid(array $polygon): void
    {
        // Arrange
        $floor = new Floor();

        // Act
        $hotspot = RaiderIOHeatmapHotspot::fromArray($floor, [
            'polygon' => $polygon,
            'npcs'    => [['npcId' => 448, 'deaths' => 3]],
        ]);

        // Assert
        $this->assertNotNull($hotspot);
        $this->assertCount(4, $hotspot->vertices);
        $this->assertEqualsWithDelta(10000.0, $hotspot->area, 0.001);
        $this->assertEqualsWithDelta(150.0, $hotspot->centroid->getX(), 0.001);
        $this->assertEqualsWithDelta(-250.0, $hotspot->centroid->getY(), 0.001);
        $this->assertSame($floor, $hotspot->centroid->getFloor());
        $this->assertSame($floor, $hotspot->vertices[0]->getFloor());
    }

    /**
     * @return array<string, array{0: list<array{0: float|int|string, 1: float|int|string}>}>
     */
    public static function squareProvider(): array
    {
        return [
            'counter clockwise'       => [[[100, -300], [200, -300], [200, -200], [100, -200]]],
            'clockwise'               => [[[100, -300], [100, -200], [200, -200], [200, -300]]],
            'closed ring'             => [[[100, -300], [200, -300], [200, -200], [100, -200], [100, -300]]],
            'numeric string vertices' => [[['100', '-300'], ['200.0', '-300'], ['200', '-200'], ['100', '-200']]],
        ];
    }

    #[Test]
    public function fromArray_givenNpcs_sortsThemByDeathsAndSumsTheirDeaths(): void
    {
        // Arrange
        $npcs = [
            ['npcId' => 1, 'deaths' => 5],
            ['npcId' => '2', 'deaths' => '20'],
            ['npcId' => 3, 'deaths' => 0],
            ['npcId' => 'not a number', 'deaths' => 7],
            ['deaths' => 7],
            ['npcId' => 4, 'deaths' => 10],
        ];

        // Act
        $hotspot = RaiderIOHeatmapHotspot::fromArray(new Floor(), [
            'polygon' => [[0, 0], [10, 0], [0, 10]],
            'npcs'    => $npcs,
        ]);

        // Assert
        $this->assertNotNull($hotspot);
        $this->assertSame([
            ['npcId' => 2, 'deaths' => 20],
            ['npcId' => 4, 'deaths' => 10],
            ['npcId' => 1, 'deaths' => 5],
        ], $hotspot->npcs);
        $this->assertSame(35, $hotspot->deaths);
    }

    /**
     * @param array<string, mixed> $hotspot
     */
    #[Test]
    #[DataProvider('unusableHotspotProvider')]
    public function fromArray_givenUnusableHotspot_returnsNull(array $hotspot): void
    {
        // Arrange
        $floor = new Floor();

        // Act
        $result = RaiderIOHeatmapHotspot::fromArray($floor, $hotspot);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function unusableHotspotProvider(): array
    {
        $npcs = [['npcId' => 448, 'deaths' => 3]];

        return [
            'no polygon'               => [['npcs' => $npcs]],
            'two vertices'             => [['polygon' => [[0, 0], [10, 0]], 'npcs' => $npcs]],
            'closed ring of two'       => [['polygon' => [[0, 0], [10, 0], [0, 0]], 'npcs' => $npcs]],
            'collinear vertices'       => [['polygon' => [[0, 0], [10, 10], [20, 20]], 'npcs' => $npcs]],
            'non-numeric vertex'       => [['polygon' => [[0, 0], [10, 0], [10, 'x'], [0, 10]], 'npcs' => $npcs]],
            'vertex missing y'         => [['polygon' => [[0, 0], [10, 0], [10], [0, 10]], 'npcs' => $npcs]],
            'no npcs'                  => [['polygon' => [[0, 0], [10, 0], [0, 10]]]],
            'only npcs without deaths' => [['polygon' => [[0, 0], [10, 0], [0, 10]], 'npcs' => [['npcId' => 448, 'deaths' => 0]]]],
        ];
    }

    #[Test]
    #[DataProvider('containsPointProvider')]
    public function containsPoint_givenPoint_returnsWhetherItLiesInsideThePolygon(float $x, float $y, bool $expected): void
    {
        // Arrange - an L shape, so its bounding box holds a point that is outside
        $floor   = new Floor();
        $hotspot = RaiderIOHeatmapHotspot::fromArray($floor, [
            'polygon' => [[0, 0], [20, 0], [20, 10], [10, 10], [10, 20], [0, 20]],
            'npcs'    => [['npcId' => 448, 'deaths' => 3]],
        ]);
        $this->assertNotNull($hotspot);

        // Act
        $result = $hotspot->containsPoint(new IngameXY($x, $y, $floor));

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: bool}>
     */
    public static function containsPointProvider(): array
    {
        return [
            'inside the long leg'      => [5.0, 15.0, true],
            'inside the short leg'     => [15.0, 5.0, true],
            'in the notch of the L'    => [15.0, 15.0, false],
            'outside the bounding box' => [25.0, 5.0, false],
        ];
    }
}
