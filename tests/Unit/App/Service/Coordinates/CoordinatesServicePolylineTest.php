<?php

namespace Tests\Unit\App\Service\Coordinates;

use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('CoordinatesService')]
final class CoordinatesServicePolylineTest extends PublicTestCase
{
    private const float DELTA = 0.0001;

    #[Test]
    public function calculateIngameLocationsForPolyline_givenMaxSpacing_injectsEvenlySpacedPointsAlongEachSegment(): void
    {
        // Arrange - a 300 yard segment followed by a 100 yard one
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor();
        $polyline           = $this->createPolyline($coordinatesService, $floor, [[100, 100], [400, 100], [400, 200]]);

        // Act
        $result = $coordinatesService->calculateIngameLocationsForPolyline($polyline, $floor, 100);

        // Assert
        $this->assertIngameXYs([[100, 100], [200, 100], [300, 100], [400, 100], [400, 200]], $result);
        $this->assertSame($floor, $result[1]->getFloor());
    }

    #[Test]
    public function calculateIngameLocationsForPolyline_givenSegmentNotAMultipleOfMaxSpacing_keepsEveryGapWithinIt(): void
    {
        // Arrange - 250 yards at most 100 apart needs three equal gaps of 83.3
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor();
        $polyline           = $this->createPolyline($coordinatesService, $floor, [[100, 100], [350, 100]]);

        // Act
        $result = $coordinatesService->calculateIngameLocationsForPolyline($polyline, $floor, 100);

        // Assert
        $this->assertIngameXYs([[100, 100], [100 + 250 / 3, 100], [100 + 500 / 3, 100], [350, 100]], $result);
    }

    #[Test]
    public function calculateIngameLocationsForPolyline_givenNoMaxSpacing_returnsOnlyTheVertices(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor();
        $polyline           = $this->createPolyline($coordinatesService, $floor, [[100, 100], [400, 100], [400, 200]]);

        // Act
        $result = $coordinatesService->calculateIngameLocationsForPolyline($polyline, $floor);

        // Assert
        $this->assertIngameXYs([[100, 100], [400, 100], [400, 200]], $result);
    }

    #[Test]
    public function calculateIngameLocationsForPolyline_givenMaxSpacing_leavesTheStoredVerticesUntouched(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor();
        $polyline           = $this->createPolyline($coordinatesService, $floor, [[100, 100], [400, 100]]);
        $verticesJson       = $polyline->vertices_json;

        // Act
        $result = $coordinatesService->calculateIngameLocationsForPolyline($polyline, $floor, 10);

        // Assert
        $this->assertCount(31, $result);
        $this->assertSame($verticesJson, $polyline->vertices_json);
        $this->assertCount(2, $polyline->getDecodedLatLngs($floor));
    }

    #[Test]
    public function calculateIngameLocationsForPolyline_givenNonPositiveMaxSpacing_throwsInvalidArgumentException(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $floor              = $this->createFloor();
        $polyline           = $this->createPolyline($coordinatesService, $floor, [[100, 100], [400, 100]]);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Max spacing must be positive');

        // Act
        $coordinatesService->calculateIngameLocationsForPolyline($polyline, $floor, 0);
    }

    #[Test]
    public function distanceIngameXYToPath_givenPointMidwayAlongALongSegment_returnsTheDistanceToTheSegment(): void
    {
        // Arrange - both vertices are 500+ yards away, the segment itself only 30
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $path               = [new IngameXY(0, 0), new IngameXY(1000, 0), new IngameXY(1000, 1000)];

        // Act
        $result = $coordinatesService->distanceIngameXYToPath(new IngameXY(500, 30), $path);

        // Assert
        $this->assertEqualsWithDelta(30, $result, self::DELTA);
    }

    #[Test]
    public function distanceIngameXYToPath_givenPointPastTheLastVertex_returnsTheDistanceToThatVertex(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);
        $path               = [new IngameXY(0, 0), new IngameXY(1000, 0)];

        // Act
        $result = $coordinatesService->distanceIngameXYToPath(new IngameXY(1030, 40), $path);

        // Assert
        $this->assertEqualsWithDelta(50, $result, self::DELTA);
    }

    #[Test]
    public function distanceIngameXYToPath_givenSingleVertex_returnsTheDistanceToIt(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->distanceIngameXYToPath(new IngameXY(3, 4), [new IngameXY(0, 0)]);

        // Assert
        $this->assertEqualsWithDelta(5, $result, self::DELTA);
    }

    #[Test]
    public function distanceIngameXYToPath_givenEmptyPath_returnsNull(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->distanceIngameXYToPath(new IngameXY(3, 4), []);

        // Assert
        $this->assertNull($result);
    }

    private function createFloor(): Floor
    {
        return new Floor()->forceFill([
            'id'           => 2,
            'name'         => 'floor.target',
            'facade'       => false,
            'ingame_min_x' => 0,
            'ingame_max_x' => 1000,
            'ingame_min_y' => 0,
            'ingame_max_y' => 1000,
        ]);
    }

    /**
     * @param list<array{0: float, 1: float}> $ingameVertices
     */
    private function createPolyline(CoordinatesServiceInterface $coordinatesService, Floor $floor, array $ingameVertices): Polyline
    {
        $vertices = [];
        foreach ($ingameVertices as [$x, $y]) {
            $latLng     = $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));
            $vertices[] = ['lat' => $latLng->getLat(), 'lng' => $latLng->getLng()];
        }

        return new Polyline()->forceFill(['vertices_json' => json_encode($vertices)]);
    }

    /**
     * @param list<array{0: float, 1: float}> $expected
     * @param list<IngameXY>                  $actual
     */
    private function assertIngameXYs(array $expected, array $actual): void
    {
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $index => [$x, $y]) {
            $this->assertEqualsWithDelta($x, $actual[$index]->getX(), self::DELTA, sprintf('x of point %d', $index));
            $this->assertEqualsWithDelta($y, $actual[$index]->getY(), self::DELTA, sprintf('y of point %d', $index));
        }
    }
}
