<?php

namespace Tests\Feature\App\Service\MDT\Export;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Spell\KnownSpell;
use App\Service\MDT\Export\KillZoneSpellsExporter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\MDT\MDTExportStringServiceTestBase;

#[Group('MDT')]
#[Group('KillZoneSpellsExporter')]
final class KillZoneSpellsExporterTest extends MDTExportStringServiceTestBase
{
    #[Test]
    public function export_givenKillZoneWithSpells_returnsOneNoteListingThem(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createKillZone($dungeonRoute, [KnownSpell::TimeWarp->value, KnownSpell::Bloodlust->value]);

            $warnings = collect();

            // Act
            $objects = app(KillZoneSpellsExporter::class)->export($dungeonRoute, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertCount(1, $objects);
            $this->assertTrue($objects[0]['n']);
            $this->assertSame("Time Warp\nBloodlust", $objects[0]['d'][5]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenKillZoneWithoutSpells_returnsNoObjects(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createKillZone($dungeonRoute, []);

            $warnings = collect();

            // Act
            $objects = app(KillZoneSpellsExporter::class)->export($dungeonRoute, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertEmpty($objects);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenKillZoneWithSpellsButNoEnemiesAndNoKillArea_warnsAndReturnsNoObjects(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            KillZone::factory()
                ->withSpells(KnownSpell::Bloodlust->value)
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
            $objects = app(KillZoneSpellsExporter::class)->export($dungeonRoute, $warnings);

            // Assert
            $this->assertCount(1, $warnings);
            $this->assertEmpty($objects);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    /**
     * @param array<int, int> $spellIds
     */
    private function createKillZone(DungeonRoute $dungeonRoute, array $spellIds): KillZone
    {
        /** @var Enemy $enemy */
        $enemy = $this->getSafeMdtEnemies($dungeonRoute)->first();

        return KillZone::factory()
            ->withEnemies($enemy)
            ->withSpells(...$spellIds)
            ->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => 1,
                'description'      => null,
                'floor_id'         => null,
                'lat'              => null,
                'lng'              => null,
            ]);
    }
}
