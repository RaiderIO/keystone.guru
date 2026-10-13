<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\RaidMarker;
use App\Service\MDT\Export\EnemyAssignmentExporter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
#[Group('MDTExportStringService')]
#[Group('MDTRaidMarker')]
class MDTRaidMarkerTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function getDungeonRoute_givenRouteWithRaidMarker_preservesRaidMarkerOnImport(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();

            DungeonRouteEnemyRaidMarker::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'raid_marker_id'   => RaidMarker::ALL['skull'],
                'npc_id'           => $enemy->getMdtNpcId(),
                'mdt_id'           => $enemy->mdt_id,
                'enemy_id'         => $enemy->id,
            ]);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $importedRaidMarker = $importedRoute->enemyRaidMarkers()
                ->where('npc_id', $enemy->getMdtNpcId())
                ->where('mdt_id', $enemy->mdt_id)
                ->first();

            $this->assertNotNull($importedRaidMarker, 'Expected the raid marker to survive the export/import round trip.');
            $this->assertSame(RaidMarker::ALL['skull'], $importedRaidMarker->raid_marker_id);
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->enemyRaidMarkers()->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenRaidMarkerOnTolDagorEnemyOfOffsetMdtNpcIndex_preservesRaidMarkerOnThatEnemy(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->createDungeonRouteForCurrentMappingVersion(DungeonKey::TOL_DAGOR);

            /** @var Enemy $enemy */
            $enemy = $dungeonRoute->mappingVersion->enemies()
                ->where('npc_id', 131112)
                ->where('mdt_id', 3)
                ->sole();

            DungeonRouteEnemyRaidMarker::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'raid_marker_id'   => RaidMarker::ALL['skull'],
                'npc_id'           => $enemy->npc_id,
                'mdt_id'           => $enemy->mdt_id,
                'enemy_id'         => $enemy->id,
            ]);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $importedRaidMarkers = $importedRoute->enemyRaidMarkers()->get();
            $this->assertCount(1, $importedRaidMarkers);
            $this->assertSame($enemy->mdt_id, $importedRaidMarkers->first()->mdt_id);
            $this->assertSame(RaidMarker::ALL['skull'], $importedRaidMarkers->first()->raid_marker_id);
        } finally {
            $importedRoute?->enemyRaidMarkers()->delete();
            $importedRoute?->delete();
            $dungeonRoute?->enemyRaidMarkers()->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenRaidMarkerOnNpcUnknownToMdt_warnsWithTheRaidMarkerKey(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            DungeonRouteEnemyRaidMarker::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'raid_marker_id'   => RaidMarker::ALL[RaidMarker::RAID_MARKER_MOON],
                'npc_id'           => 987654321,
                'mdt_id'           => 1,
                'enemy_id'         => null,
            ]);
            $warnings = collect();

            // Act
            $result = app(EnemyAssignmentExporter::class)->export($dungeonRoute->fresh(), $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertSame([], $result);
            $this->assertCount(1, $warnings);
            $this->assertSame(
                sprintf(__('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_raid_marker'), RaidMarker::RAID_MARKER_MOON, 987654321),
                $warnings->first()->getMessage(),
            );
        } finally {
            $dungeonRoute?->enemyRaidMarkers()->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenRouteWithoutRaidMarker_importsNoRaidMarkers(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame(0, $importedRoute->enemyRaidMarkers()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
