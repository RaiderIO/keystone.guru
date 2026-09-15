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
 * #3987: an NPC's game_version_id decides which Wowhead database its compendium link points at, and
 * MDTMappingImportService seeds it from the game version being imported. That seeding is creation-only:
 * an NPC that already exists may have had its game version corrected by hand, and importing the same
 * dungeon for another game version must not undo that.
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportPreservesExistingGameVersionTest extends PublicTestCase
{
    #[Test]
    public function importNpcsDataFromMDT_givenExistingNpcOfAnotherGameVersion_doesNotOverwriteItsGameVersion(): void
    {
        // Arrange - Avatar of Sethraliss, an NPC MDT knows about for this dungeon
        $dungeon = Dungeon::query()->where('key', 'templeofsethraliss')->firstOrFail();
        $npc     = Npc::query()->findOrFail(133392);

        $originalGameVersionId = $npc->game_version_id;

        Npc::query()->whereKey($npc->id)->update([
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_MOP],
        ]);

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        try {
            // Act
            $failures = [];
            $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');
            $this->assertSame(
                GameVersion::ALL[GameVersion::GAME_VERSION_MOP],
                $npc->fresh()->game_version_id,
                'Re-importing must not overwrite an already-curated game_version_id.',
            );
        } finally {
            Npc::query()->whereKey($npc->id)->update(['game_version_id' => $originalGameVersionId]);
        }
    }
}
