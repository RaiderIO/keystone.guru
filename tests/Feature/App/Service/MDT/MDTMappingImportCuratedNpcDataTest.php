<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\RestoresNpcsImportedFromMdt;
use Tests\TestCases\PublicTestCase;

#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportCuratedNpcDataTest extends PublicTestCase
{
    use RestoresNpcsImportedFromMdt;

    private const int INFERNAL_NPC_ID = 238414;

    #[Test]
    public function importNpcsDataFromMDT_givenCuratedNpc_leavesItsNpcRowAlone(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'murder_row')->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        $mdtNpc = collect($mdtDungeon->getMDTNPCs())
            ->first(static fn($mdtNpc) => $mdtNpc->getId() === self::INFERNAL_NPC_ID);
        $this->assertNotNull($mdtNpc, 'MDT must list the curated NPC, or the import has nothing to skip.');
        $this->assertContains(self::INFERNAL_NPC_ID, Npc::getCuratedDataNpcIds());

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);
        $this->restoreNpcsImportedFromMdtAfterTheTest($mdtDungeon);

        // A display id MDT does not report: any update of this row from MDT's data overwrites it
        $sentinelDisplayId = $mdtNpc->getDisplayId() + 1;
        Npc::query()->whereKey(self::INFERNAL_NPC_ID)->update(['display_id' => $sentinelDisplayId]);

        // Act
        $failures = [];
        $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

        // Assert
        $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');
        $this->assertSame(
            $sentinelDisplayId,
            Npc::query()->findOrFail(self::INFERNAL_NPC_ID)->display_id,
            'The import must skip a curated NPC entirely instead of updating it from MDT.',
        );
    }
}
