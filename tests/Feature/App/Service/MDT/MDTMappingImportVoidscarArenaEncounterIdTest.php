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

/**
 * #4148 found MDT reporting the same encounter_id (2791) for all three Voidscar Arena bosses, an upstream
 * MDT bug worked around with a manual override in MDTMappingImportService. #4158 confirmed MDT now reports
 * distinct, correct encounter_ids per boss and removed the override. This test guards against the bug
 * resurfacing upstream by asserting the import produces distinct ids straight from MDT's data.
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportVoidscarArenaEncounterIdTest extends PublicTestCase
{
    #[Test]
    public function importNpcsDataFromMDT_givenVoidscarArenaBosses_usesDistinctEncounterIdsFromMDT(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'voidscar_arena')->firstOrFail();

        $tazrah   = Npc::query()->findOrFail(238887);
        $atroxus  = Npc::query()->findOrFail(239008);
        $charonus = Npc::query()->findOrFail(239167);

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        // Act
        $failures = [];
        $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

        // Assert
        $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');
        $this->assertSame(2791, $tazrah->fresh()->encounter_id, "Taz'Rah's encounter_id must come from MDT.");
        $this->assertSame(2792, $atroxus->fresh()->encounter_id, "Atroxus's encounter_id must come from MDT, not collide with Taz'Rah's.");
        $this->assertSame(2793, $charonus->fresh()->encounter_id, "Charonus's encounter_id must come from MDT, not collide with the other two bosses.");
    }
}
