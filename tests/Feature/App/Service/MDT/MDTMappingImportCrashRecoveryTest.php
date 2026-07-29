<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

/**
 * #3737: `mdt:importmapping` used to record the new mapping version's `mdt_mapping_hash` up front, before any
 * of the actual import steps ran. A crash partway through then left the half-built mapping version behind as
 * the dungeon's current one, already carrying the hash of the MDT data it failed to import - so every
 * subsequent run compared hashes, found them equal, and silently no-op'd forever while the dungeon rendered
 * with zero enemies.
 *
 * The hash is now written only once every import step has succeeded, and a crash deletes the partial mapping
 * version instead of leaving it behind - so the dungeon's current mapping version is left completely
 * unchanged and the next run genuinely retries.
 *
 * The forced failure is injected via a decorator around MappingServiceInterface (a method parameter of
 * importMappingVersionFromMDT(), not a constructor dependency) that throws from
 * copyEnemyForcesCheckpointsToMappingVersion() - the first call inside the try block - so nothing downstream
 * of it runs: no shared Npc/Spell rows are touched, and the only residue is the mapping version row itself.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportCrashRecoveryTest extends PublicTestCase
{
    #[Test]
    public function importMappingVersionFromMDT_givenImportThrowsPartway_leavesDungeonsCurrentMappingVersionUnchanged(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon     = Dungeon::query()->where('key', 'throne_of_the_tides')->firstOrFail();
        $gameVersion = GameVersion::query()->findOrFail($dungeon->getCurrentMappingVersion()->game_version_id);

        $mappingVersionIdsBefore       = MappingVersion::query()->where('dungeon_id', $dungeon->id)->pluck('id')->sort()->values()->all();
        $currentMappingVersionIdBefore = $dungeon->getCurrentMappingVersion($gameVersion)->id;

        $realMappingService   = $this->app->make(MappingServiceInterface::class);
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $throwingMappingService = new class ($realMappingService) implements MappingServiceInterface {
            public function __construct(private readonly MappingServiceInterface $real)
            {
            }

            public function createNewBareMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
            {
                return $this->real->createNewBareMappingVersion($dungeon, $gameVersion);
            }

            public function createNewMappingVersionFromPreviousMapping(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
            {
                return $this->real->createNewMappingVersionFromPreviousMapping($dungeon, $gameVersion);
            }

            public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?GameVersion $gameVersion): MappingVersion
            {
                return $this->real->createNewMappingVersionFromMDTMapping($dungeon, $gameVersion);
            }

            public function getMappingVersionForMdtAddonVersion(Dungeon $dungeon, ?int $addonVersion, ?GameVersion $gameVersion = null): ?MappingVersion
            {
                return $this->real->getMappingVersionForMdtAddonVersion($dungeon, $addonVersion, $gameVersion);
            }

            public function copyMappingVersionToDungeon(MappingVersion $sourceMappingVersion, Dungeon $dungeon): MappingVersion
            {
                return $this->real->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            }

            public function copyMappingVersionContentsToDungeon(
                MappingVersion $sourceMappingVersion,
                MappingVersion $targetMappingVersion,
            ): MappingVersion {
                return $this->real->copyMappingVersionContentsToDungeon($sourceMappingVersion, $targetMappingVersion);
            }

            public function copyEnemyForcesCheckpointsToMappingVersion(
                MappingVersion $sourceMappingVersion,
                MappingVersion $targetMappingVersion,
            ): array {
                throw new RuntimeException('Forced failure to test crash recovery (#3737)');
            }
        };

        try {
            // Act
            try {
                $mappingImportService->importMappingVersionFromMDT($throwingMappingService, $dungeon, $gameVersion, true);

                $this->fail('Expected the import to throw.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Forced failure to test crash recovery (#3737)', $exception->getMessage());
            }

            // Assert
            /** @var Dungeon $freshDungeon */
            $freshDungeon = Dungeon::query()->findOrFail($dungeon->id);

            $this->assertSame(
                $currentMappingVersionIdBefore,
                $freshDungeon->getCurrentMappingVersion($gameVersion)->id,
                'A crashed import must not change the dungeon\'s current mapping version.',
            );

            $this->assertSame(
                $mappingVersionIdsBefore,
                MappingVersion::query()->where('dungeon_id', $dungeon->id)->pluck('id')->sort()->values()->all(),
                'A crashed import must not leave a half-built mapping version behind.',
            );
        } finally {
            MappingVersion::query()
                ->where('dungeon_id', $dungeon->id)
                ->whereNotIn('id', $mappingVersionIdsBefore)
                ->delete();
        }
    }

    /**
     * The crash-recovery test above only proves that a failed import does not stamp a hash - it does not
     * distinguish "the hash is written after success" from "the hash is never written at all", since it
     * forces a failure before that point is ever reached. This exercises the real, full import pipeline (no
     * decorator) so the actual write on success is asserted directly.
     */
    #[Test]
    public function importMappingVersionFromMDT_givenImportSucceeds_stampsTheNewMappingVersionWithTheMdtMappingHash(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon     = Dungeon::query()->where('key', 'throne_of_the_tides')->firstOrFail();
        $gameVersion = GameVersion::query()->findOrFail($dungeon->getCurrentMappingVersion()->game_version_id);

        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $expectedHash = $mappingImportService->getMDTMappingHash($dungeon);

        $newMappingVersion = null;

        // importNpcsDataFromMDT() does not eager-load Npc::npcHealths before reading it - a real,
        // pre-existing bug unrelated to #3737 (it is one of the crash causes #3737 cites as an example
        // trigger, not something this PR fixes). In production that only logs and lazy-loads anyway
        // (AppServiceProvider::boot()); in dev/test it throws. Match production's tolerance here so this
        // test can exercise a genuinely successful import instead of tripping over that unrelated bug.
        Model::preventLazyLoading(false);

        try {
            // Act
            $newMappingVersion = $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $gameVersion, true);

            // Assert
            $this->assertSame(
                $expectedHash,
                $newMappingVersion->fresh()->mdt_mapping_hash,
                'A successful import must stamp the new mapping version with the freshly computed MDT hash.',
            );
        } finally {
            Model::preventLazyLoading();

            $newMappingVersion?->delete();
        }
    }
}
