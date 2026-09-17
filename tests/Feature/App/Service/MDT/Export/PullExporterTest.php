<?php

namespace Tests\Feature\App\Service\MDT\Export;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Service\MDT\Export\PullExporter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\MDT\MDTExportStringServiceTestBase;

#[Group('MDT')]
#[Group('PullExporter')]
final class PullExporterTest extends MDTExportStringServiceTestBase
{
    #[Test]
    public function export_givenKillZoneWithAnMdtEnemy_returnsThatEnemysCloneInThePull(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $enemy */
            $enemy = $this->getSafeMdtEnemies($dungeonRoute)->first();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert - Lua is 1 based, so the first pull sits at index 1
            $this->assertEmpty($warnings);
            $this->assertCount(1, $pulls);

            $pullNpcIndices = array_diff_key($pulls[1], ['color' => null]);
            $this->assertCount(1, $pullNpcIndices);
            $this->assertSame([$enemy->mdt_id], array_values($pullNpcIndices)[0]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenKillZoneColorWithLeadingHash_returnsColorWithoutIt(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $enemy */
            $enemy = $this->getSafeMdtEnemies($dungeonRoute)->first();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'color'            => '#ABCDEF',
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, collect());

            // Assert
            $this->assertSame('ABCDEF', $pulls[1]['color']);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenRouteWithoutKillZones_returnsNoPulls(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertEmpty($pulls);
        } finally {
            $dungeonRoute?->delete();
        }
    }
}
