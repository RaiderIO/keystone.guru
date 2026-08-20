<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #4208: config('keystoneguru.npc.curated_npc_data_npc_ids') is the one list of NPCs whose data is hand-curated, shared
 * by the MDT import and combatlog:extractnpchealth. Murder Row's Infernal is on it because MDT reports its real
 * 202,703,424 health while the mapping deliberately stores 2,703,424 - a 200M trash mob breaks the health-based enemy
 * sizing on the map - so an import must leave the NPC alone entirely rather than clobber the curated value back.
 */
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
        $this->assertNotSame(self::INFERNAL_CURATED_HEALTH, $mdtHealth, 'MDT must disagree with the curated value, or this test proves nothing.');

        // Act
        $failures = [];
        $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

        // Assert
        $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

        // Model caching is on in CI: the eager-loaded npcHealths cache under the Npc model, so both need flushing
        new Npc()->flushCache();
        new NpcHealth()->flushCache();
        $this->assertSame(
            self::INFERNAL_CURATED_HEALTH,
            Npc::query()->with('npcHealths')->findOrFail(self::INFERNAL_NPC_ID)->getHealthByGameVersion($retailGameVersion)?->health,
            sprintf('The import must not have replaced the curated health with MDT\'s %s.', var_export($mdtHealth, true)),
        );
    }
}
