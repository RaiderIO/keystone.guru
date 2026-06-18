<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Arrow;
use App\Service\MDT\MDTExportStringServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTExportStringService')]
#[Group('MDTExportStringServiceExtractArrows')]
class MDTExportStringServiceExtractArrowsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function extractObjects_givenRouteWithArrow_shouldExportArrowAsTriangleObject(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $dungeonRoute           = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createArrowForRoute($dungeonRoute);

            $warnings = new Collection();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = json_decode($this->decode($encodedString), true);

            Assert::assertIsArray($decodedString);
            Assert::assertEmpty($warnings);

            // The arrow must be exported as an object with a 't' (triangle/arrowhead) key
            $objects = $decodedString['objects'] ?? [];
            Assert::assertNotEmpty($objects, 'Expected at least one exported object');

            $arrowObject = reset($objects);
            Assert::assertArrayHasKey('t', $arrowObject, 'Arrow object must have a "t" key for the arrowhead rotation');
            Assert::assertArrayHasKey('l', $arrowObject, 'Arrow object must have an "l" key for the shaft vertices');
            Assert::assertNotEmpty($arrowObject['t'], 'Arrow "t" key must contain a rotation value');
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenRouteWithArrow_shouldNotExportAsBrushlineOrPath(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $mdtExportStringService = app()->make(MDTExportStringServiceInterface::class);
            $dungeonRoute           = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createArrowForRoute($dungeonRoute);

            $warnings = new Collection();

            // Act
            $encodedString = $mdtExportStringService->setDungeonRoute($dungeonRoute)->getEncodedString($warnings);

            // Assert
            $decodedString = json_decode($this->decode($encodedString), true);

            Assert::assertIsArray($decodedString);
            $objects = $decodedString['objects'] ?? [];

            // All exported objects with 'l' but without 't' would be brushlines/paths.
            // There should be exactly 1 object (the arrow as a triangle), not 3 (the old approach).
            Assert::assertCount(1, $objects, 'Arrow must export as exactly one triangle object, not 3 brushlines');
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenRouteWithArrow_roundTripShouldProduceOneArrow(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createArrowForRoute($dungeonRoute);

            // Act — export then re-import
            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            Assert::assertEquals(1, $importedRoute->arrows()->count(), 'Re-imported route should have exactly one arrow');
            Assert::assertEquals(0, $importedRoute->brushlines()->count(), 'Re-imported route must not create brushlines from arrows');

            // Arrow polyline data should be preserved
            /** @var Arrow $arrow */
            $arrow = $importedRoute->arrows()->with('polyline')->first();
            Assert::assertNotNull($arrow->polyline, 'Arrow must have a polyline after round-trip');
            Assert::assertEquals('#ff0032', strtolower($arrow->polyline->color), 'Arrow color must be preserved');
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
