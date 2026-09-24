<?php

namespace Tests\Unit\App\Models;

use App\Logic\Structs\LatLng;
use App\Models\MountableArea;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('MountableArea')]
final class MountableAreaTest extends PublicTestCase
{
    private const SQUARE_VERTICES_JSON = '[{"lat":-10,"lng":10},{"lat":-10,"lng":20},{"lat":-20,"lng":20},{"lat":-20,"lng":10}]';

    #[Test]
    public function contains_givenPointInsideItsPolyline_returnsTrue(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(self::SQUARE_VERTICES_JSON);

        // Act
        $contains = $mountableArea->contains(app(CoordinatesServiceInterface::class), new LatLng(-15, 15));

        // Assert
        $this->assertTrue($contains);
    }

    #[Test]
    public function contains_givenPointOutsideItsPolyline_returnsFalse(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(self::SQUARE_VERTICES_JSON);

        // Act
        $contains = $mountableArea->contains(app(CoordinatesServiceInterface::class), new LatLng(-30, 15));

        // Assert
        $this->assertFalse($contains);
    }

    #[Test]
    public function contains_givenNoPolyline_returnsFalse(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(null);

        // Act
        $contains = $mountableArea->contains(app(CoordinatesServiceInterface::class), new LatLng(-15, 15));

        // Assert
        $this->assertFalse($contains);
    }

    #[Test]
    public function getIntersections_givenLineCrossingItsPolyline_returnsBothCrossings(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(self::SQUARE_VERTICES_JSON);

        // Act
        $intersections = $mountableArea->getIntersections(
            app(CoordinatesServiceInterface::class),
            new LatLng(-15, 0),
            new LatLng(-15, 30),
        );

        // Assert
        $lngs = array_map(static fn(LatLng $latLng) => $latLng->getLng(), $intersections);
        sort($lngs);
        $this->assertEqualsWithDelta([10, 20], $lngs, 0.0001);
        foreach ($intersections as $intersection) {
            $this->assertEqualsWithDelta(-15, $intersection->getLat(), 0.0001);
        }
    }

    #[Test]
    public function getIntersections_givenLineOutsideItsPolyline_returnsEmptyArray(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(self::SQUARE_VERTICES_JSON);

        // Act
        $intersections = $mountableArea->getIntersections(
            app(CoordinatesServiceInterface::class),
            new LatLng(-30, 0),
            new LatLng(-30, 30),
        );

        // Assert
        $this->assertSame([], $intersections);
    }

    #[Test]
    public function getIntersections_givenNoPolyline_returnsEmptyArray(): void
    {
        // Arrange
        $mountableArea = $this->makeMountableArea(null);

        // Act
        $intersections = $mountableArea->getIntersections(
            app(CoordinatesServiceInterface::class),
            new LatLng(-15, 0),
            new LatLng(-15, 30),
        );

        // Assert
        $this->assertSame([], $intersections);
    }

    private function makeMountableArea(?string $verticesJson): MountableArea
    {
        $mountableArea = new MountableArea();
        $mountableArea->setRelation('polyline', $verticesJson === null ? null : new Polyline(['vertices_json' => $verticesJson]));

        return $mountableArea;
    }
}
