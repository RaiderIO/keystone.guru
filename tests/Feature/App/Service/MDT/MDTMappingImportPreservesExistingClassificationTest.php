<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #4121: MDTMappingImportService::importNpcsDataFromMDT() now defaults a newly-created NPC's
 * classification to boss when MDT flags it isBoss - but must never overwrite an already-curated
 * classification_id on re-import, whether that curation came from the admin panel or from a
 * finalboss upgrade an MDT isBoss default alone would never produce.
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportPreservesExistingClassificationTest extends PublicTestCase
{
    #[Test]
    public function importNpcsDataFromMDT_givenExistingFinalBossNpcFlaggedIsBossInMDT_doesNotDowngradeClassification(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'templeofsethraliss')->firstOrFail();

        // Avatar of Sethraliss: MDT flags it isBoss, and it is already curated as finalboss - the
        // isBoss-derived default (boss) must not clobber that on re-import.
        $npc = Npc::query()->findOrFail(133392);
        $this->assertSame(
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
            $npc->classification_id,
            'Precondition: this NPC must already be curated as finalboss for the test to prove anything.',
        );

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
        $this->assertSame(
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
            $npc->fresh()->classification_id,
            'Re-importing must not overwrite an already-curated classification_id.',
        );
    }
}
