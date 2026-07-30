<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Mapping\MappingService;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Exceptions\MDTMappingImportPartialFailureException;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use App\Service\MDT\MDTAddonVersionServiceInterface;
use App\Service\MDT\MDTMappingImportService;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
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
 * The forced failure is injected via a partial mock of the concrete MappingService (a method parameter of
 * importMappingVersionFromMDT(), not a constructor dependency) that throws from
 * copyEnemyForcesCheckpointsToMappingVersion() - the first call inside the try block - while every other
 * method falls through to the real implementation, so nothing downstream of it runs: no shared Npc/Spell
 * rows are touched, and the only residue is the mapping version row itself.
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

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        // Partial mock of the concrete MappingService: only copyEnemyForcesCheckpointsToMappingVersion()
        // is mocked to throw, every other method falls through to the real implementation.
        $throwingMappingService = $this->getMockBuilderPublic(MappingService::class)
            ->setConstructorArgs([
                $this->app->make(MDTAddonVersionServiceInterface::class),
            ])
            ->onlyMethods(['copyEnemyForcesCheckpointsToMappingVersion'])
            ->getMock();

        $throwingMappingService
            ->method('copyEnemyForcesCheckpointsToMappingVersion')
            ->willThrowException(new RuntimeException('Forced failure to test crash recovery (#3737)'));

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
            // A per-model destroy(), not a query-builder mass delete: a mass delete never fires
            // MappingVersion's `deleting` model event, which is what cascades the delete down to any
            // cloned dungeonFloorSwitchMarkers/mapIcons/mountableAreas/floorUnions/floorUnionAreas the
            // half-built version already holds if this safety net is ever actually hit (the fix above
            // regressing). Without the cascade those clones would be orphaned instead of cleaned up.
            MappingVersion::destroy(
                MappingVersion::query()
                    ->where('dungeon_id', $dungeon->id)
                    ->whereNotIn('id', $mappingVersionIdsBefore)
                    ->pluck('id'),
            );
        }
    }

    /**
     * The crash-recovery test above only proves that a failed import does not stamp a hash - it does not
     * distinguish "the hash is written after success" from "the hash is never written at all", since it
     * forces a failure before that point is ever reached. This exercises the real import pipeline - the
     * MappingService passed in below is the real one, unlike the crash-recovery test above - so the actual
     * write on success is asserted directly.
     *
     * importNpcsDataFromMDT() is stubbed out via a partial mock rather than left real: it save()s every MDT
     * NPC, upserts NpcHealth, and mass-inserts NpcSpell/NpcDungeon, and for genuinely new NPCs also writes
     * NpcEnemyForces into every historical mapping version - none of which is scoped to the new mapping
     * version, so a mapping-version delete cannot revert it. That would pollute the shared dev DB with
     * unrevertable rows on every run - exactly what #3737 itself warns about. throne_of_the_tides already
     * has every NPC MDT reports for it seeded, so importNpcs() (which calls the stubbed method first, then
     * re-fetches the dungeon's NPCs to write per-mapping-version NpcEnemyForces) still finds a real NPC for
     * every enemy, and importEnemies()/importEnemyPacks()/importEnemyPatrols()/importMapPOIs() - along with
     * the hash write itself - all still run for real, unstubbed. That also removes the need to relax
     * lazy-loading below: the only violation (Npc::npcHealths read without eager-loading) lived entirely
     * inside the now-stubbed method.
     */
    #[Test]
    public function importMappingVersionFromMDT_givenImportSucceeds_stampsTheNewMappingVersionWithTheMdtMappingHash(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon     = Dungeon::query()->where('key', 'throne_of_the_tides')->firstOrFail();
        $gameVersion = GameVersion::query()->findOrFail($dungeon->getCurrentMappingVersion()->game_version_id);

        $mappingService = $this->app->make(MappingServiceInterface::class);

        $expectedHash = $this->app->make(MDTMappingImportServiceInterface::class)->getMDTMappingHash($dungeon);

        $mappingImportService = $this->getMockBuilderPublic(MDTMappingImportService::class)
            ->setConstructorArgs([
                $this->app->make(CacheServiceInterface::class),
                $this->app->make(CoordinatesServiceInterface::class),
                $this->app->make(MDTMappingImportServiceLoggingInterface::class),
            ])
            ->onlyMethods(['importNpcsDataFromMDT'])
            ->getMock();

        // importDungeon() writes dungeons.mdt_id - the only side effect left outside the new mapping
        // version once importNpcsDataFromMDT() is stubbed out - so it can be restored below.
        $originalDungeonMdtId = $dungeon->mdt_id;

        $newMappingVersion = null;

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
            $newMappingVersion?->delete();

            $dungeon->update(['mdt_id' => $originalDungeonMdtId]);
        }
    }

    /**
     * #3755: importNpcs() used to catch a per-NPC exception, log it, and move on to the next NPC - so a
     * genuine save failure never reached importMappingVersionFromMDT()'s outer catch, the new mapping
     * version still got stamped with the fresh mdt_mapping_hash, and the next run saw "no change detected"
     * and skipped retrying forever, exactly like #3737's symptom but through a different door.
     *
     * The failure is forced via a mocked MDTMappingImportServiceLoggingInterface that throws from
     * importNpcsUpdateExistingNpc() - a log call inside importNpcs()'s per-NPC try block, right after the
     * new mapping version's NpcEnemyForces row is created for it. That exercises the real per-item catch at
     * the exact point #3755 describes, without needing to fabricate a genuine NPC-save failure.
     *
     * importNpcsDataFromMDT() is stubbed out for the same reason the success-path test above stubs it: it
     * writes to shared Npc/NpcHealth/NpcSpell/NpcDungeon rows that are not scoped to the new mapping version,
     * so a mapping-version delete cannot revert them. throne_of_the_tides already has every NPC MDT reports
     * for it seeded, so importNpcs()'s re-fetch of the dungeon's NPCs still finds a real NPC - with count > 0
     * - to reach the per-NPC try block that is being forced to fail.
     */
    #[Test]
    public function importMappingVersionFromMDT_givenPerNpcSaveThrows_failsTheWholeImportInsteadOfSilentlyStampingTheHash(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon     = Dungeon::query()->where('key', 'throne_of_the_tides')->firstOrFail();
        $gameVersion = GameVersion::query()->findOrFail($dungeon->getCurrentMappingVersion()->game_version_id);

        $mappingVersionIdsBefore       = MappingVersion::query()->where('dungeon_id', $dungeon->id)->pluck('id')->sort()->values()->all();
        $currentMappingVersionIdBefore = $this->highestVersionMappingVersionId($dungeon, $gameVersion);

        $mappingService = $this->app->make(MappingServiceInterface::class);

        $forcedFailureMessage = 'Forced failure to test partial-failure accumulation (#3755)';

        /** @var MDTMappingImportServiceLoggingInterface&MockObject $mockLog */
        $mockLog = $this->createMockPublic(MDTMappingImportServiceLoggingInterface::class);
        $mockLog->method('importNpcsUpdateExistingNpc')
            ->willThrowException(new RuntimeException($forcedFailureMessage));

        $mappingImportService = $this->getMockBuilderPublic(MDTMappingImportService::class)
            ->setConstructorArgs([
                $this->app->make(CacheServiceInterface::class),
                $this->app->make(CoordinatesServiceInterface::class),
                $mockLog,
            ])
            ->onlyMethods(['importNpcsDataFromMDT'])
            ->getMock();

        // importDungeon() writes dungeons.mdt_id - the only side effect left outside the new mapping version
        // once importNpcsDataFromMDT() is stubbed out - so it can be restored below.
        $originalDungeonMdtId = $dungeon->mdt_id;

        try {
            // Act
            try {
                $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $gameVersion, true);

                $this->fail('Expected the import to throw.');
            } catch (MDTMappingImportPartialFailureException $exception) {
                $this->assertNotEmpty($exception->getFailures(), 'The partial-failure exception must carry the failures that caused it.');
                $this->assertSame($forcedFailureMessage, $exception->getFailures()[0]->getMessage());
                $this->assertSame(
                    $exception->getFailures()[0],
                    $exception->getPrevious(),
                    'The first accumulated failure must be preserved as the previous exception so its original stack trace is not lost.',
                );
            }

            // Assert
            /** @var Dungeon $freshDungeon */
            $freshDungeon = Dungeon::query()->findOrFail($dungeon->id);

            $this->assertSame(
                $currentMappingVersionIdBefore,
                $this->highestVersionMappingVersionId($freshDungeon, $gameVersion),
                'A partially-failed import must not change the dungeon\'s current mapping version.',
            );

            $this->assertSame(
                $mappingVersionIdsBefore,
                MappingVersion::query()->where('dungeon_id', $dungeon->id)->pluck('id')->sort()->values()->all(),
                'A partially-failed import must not leave a half-built mapping version behind, even though ' .
                'every import step ran to completion.',
            );
        } finally {
            // Same cascade-safe cleanup as the crash-recovery test above, in case the fix regresses.
            MappingVersion::destroy(
                MappingVersion::query()
                    ->where('dungeon_id', $dungeon->id)
                    ->whereNotIn('id', $mappingVersionIdsBefore)
                    ->pluck('id'),
            );

            $dungeon->update(['mdt_id' => $originalDungeonMdtId]);
        }
    }

    /**
     * A direct, game-version-scoped query, deliberately not Dungeon::getCurrentMappingVersion() - that
     * method falls back through the caller's own game version, then the default game version, then (if
     * still unmatched) the highest mapping version ID across ALL game versions. Comparing "before" and
     * "after" through that fallback chain risks silently comparing two different game versions if either
     * side happens to miss the scoped lookup.
     */
    private function highestVersionMappingVersionId(Dungeon $dungeon, GameVersion $gameVersion): ?int
    {
        return MappingVersion::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('game_version_id', $gameVersion->id)
            ->orderByDesc('version')
            ->value('id');
    }
}
