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
use Tests\TestCases\PublicTestCase;

#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportCuratedNpcDataTest extends PublicTestCase
{
    private const int INFERNAL_NPC_ID = 238414;

    private const int INFERNAL_CURATED_HEALTH = 2_703_424;

    #[Test]
    public function importNpcsDataFromMDT_givenCuratedNpc_leavesItsHealthAlone(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'murder_row')->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $infernal = Npc::query()->with('npcHealths')->findOrFail(self::INFERNAL_NPC_ID);
        $this->assertSame(
            self::INFERNAL_CURATED_HEALTH,
            $infernal->getHealthByGameVersion($retailGameVersion)?->health,
            'The seeder must ship the curated health, or this test proves nothing.',
        );

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        $mdtHealth = collect($mdtDungeon->getMDTNPCs())
            ->first(static fn($mdtNpc) => $mdtNpc->getId() === self::INFERNAL_NPC_ID)
            ?->getHealth();
        $this->assertContains(
            self::INFERNAL_NPC_ID,
            Npc::getCuratedDataNpcIds(),
            'The NPC must be on the curated list, or the import has no reason to skip it regardless of what MDT reports.',
        );

        // Act
        $failures = [];
        $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

        // Assert
        $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

        $this->assertSame(
            self::INFERNAL_CURATED_HEALTH,
            Npc::query()->with('npcHealths')->findOrFail(self::INFERNAL_NPC_ID)->getHealthByGameVersion($retailGameVersion)?->health,
            sprintf('The import must not have replaced the curated health with MDT\'s %s.', var_export($mdtHealth, true)),
        );
    }

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

        $infernal = Npc::query()->findOrFail(self::INFERNAL_NPC_ID);

        // A display id MDT does not report: any update of this row from MDT's data overwrites it
        $sentinelDisplayId = $mdtNpc->getDisplayId() + 1;
        Npc::query()->whereKey($infernal->id)->update(['display_id' => $sentinelDisplayId]);

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        try {
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
        } finally {
            Npc::query()->whereKey($infernal->id)->update(['display_id' => $infernal->display_id]);
        }
    }
}
