<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\RaidMarker;
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
