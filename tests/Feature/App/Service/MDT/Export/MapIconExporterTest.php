<?php

namespace Tests\Feature\App\Service\MDT\Export;

use App\Models\MapIcon;
use App\Service\MDT\Export\MapIconExporter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\MDT\MDTExportStringServiceTestBase;

#[Group('MDT')]
#[Group('MapIconExporter')]
final class MapIconExporterTest extends MDTExportStringServiceTestBase
{
    #[Test]
    public function export_givenMapIconWithComment_returnsNoteCarryingThatComment(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();

            /** @var MapIcon $mapIcon */
            $mapIcon = MapIcon::factory()->create(['comment' => 'Skip this pack']);
            $dungeonRoute->mapIcons()->save($mapIcon);

            // Act
            $objects = app(MapIconExporter::class)->export($dungeonRoute, collect());

            // Assert
            $this->assertCount(1, $objects);
            $this->assertTrue($objects[0]['n']);
            $this->assertSame('Skip this pack', $objects[0]['d'][5]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenMapIconWithoutComment_returnsNoteCarryingTheTranslatedMapIconTypeName(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();

            /** @var MapIcon $mapIcon */
            $mapIcon = MapIcon::factory()->create(['comment' => null]);
            $dungeonRoute->mapIcons()->save($mapIcon);

            // Act
            $objects = app(MapIconExporter::class)->export($dungeonRoute, collect());

            // Assert
            $this->assertCount(1, $objects);
            $this->assertSame(__($mapIcon->mapIconType->name), $objects[0]['d'][5]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenRouteWithoutMapIcons_returnsNoObjects(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();

            // Act
            $objects = app(MapIconExporter::class)->export($dungeonRoute, collect());

            // Assert
            $this->assertEmpty($objects);
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
