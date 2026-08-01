<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\EnemyForcesCheckpoint;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcHealth;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3757: MDTMappingImportService::importMappingVersionFromMDT() used to resolve its "predecessor" mapping
 * version via Dungeon::getCurrentMappingVersion(), which silently falls back to an AMBIENT/unrelated game
 * version's mapping version whenever the dungeon has zero mapping versions for the game version actually being
 * imported (e.g. a dungeon's first-ever import for a newly-added game version). That mistagged the new mapping
 * version with the wrong game_version_id/version AND cloned checkpoints/enemies/patrols/POIs from the wrong
 * game version's data. A genuinely first-ever import for a game version must be tagged with THAT game version
 * and start with nothing cloned.
 *
 * Runs the real import pipeline (real Lua parsing via MDTDungeon, like MDTNpcMappingCoverageTest) rather than
 * stubbing it, because MDTMappingImportService constructs its MDTDungeon with `new` rather than resolving it
 * from the container - there is no DI seam to intercept, and the bug lived in the resolution step itself
 * (Dungeon::getCurrentMappingVersion() vs getCurrentMappingVersionForGameVersion()), not in what the downstream
 * clone/import calls do with whatever they're handed. This class is therefore scoped to ONLY what genuinely
 * needs the real pipeline - the game_version_id/version scoping of importMappingVersionFromMDT()'s own
 * predecessor resolution. The facade_enabled/FloorUnion/timer_max_seconds inheritance behaviour of
 * MappingService::createNewMappingVersionFromMDTMapping() itself is covered separately in
 * MappingServiceCreateNewMappingVersionFromMDTMappingTest, which calls it directly and needs no Lua at all -
 * exercising it here too would only add more real Lua-driven NPC/dungeon row mutations to the shared seeded
 * test DB (mirroring the concern that keeps MDTMappingImportEnemyForcesCheckpointTest off the real pipeline)
 * without covering anything the direct test doesn't already cover.
 */
#[Group('UsesLua')]
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportGameVersionScopingTest extends PublicTestCase
{
    #[Test]
    public function importMappingVersionFromMDT_givenGameVersionWithNoExistingMappingVersion_createsMappingVersionForThatGameVersionWithNothingClonedFromAnotherGameVersion(): void
    {
        // Arrange
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        /** @var GameVersion $targetGameVersion */
        $targetGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_BETA)->firstOrFail();
        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $dungeon = $this->getMdtDungeonWithRetailCheckpointAndNoMappingForGameVersion($targetGameVersion, $retailGameVersion);

        $retailMappingVersion  = $dungeon->getCurrentMappingVersionForGameVersion($retailGameVersion);
        $retailCheckpointCount = $retailMappingVersion->enemyForcesCheckpoints()->count();

        // importNpcsDataFromMDT() saves an NpcHealth row keyed by (npc_id, game_version_id) for every MDT NPC -
        // unlike everything else the import creates, these are NOT scoped to (and therefore not cascade-deleted
        // by) the mapping version, so the ids that already exist for the target game version have to be
        // snapshotted up front and anything new cleaned up by hand afterwards.
        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);
        $mdtNpcIds               = $mdtDungeon->getMDTNPCs()->map(static fn($mdtNpc) => $mdtNpc->getId())->all();
        $preExistingNpcHealthIds = NpcHealth::query()
            ->where('game_version_id', $targetGameVersion->id)
            ->whereIn('npc_id', $mdtNpcIds)
            ->pluck('id')
            ->all();

        $dungeon->setRelation('npcs', $dungeon->npcs()->get());

        $newMappingVersion = null;

        try {
            // Act

            // importNpcsDataFromMDT() lazy-loads Npc::$npcHealths (App\Models\Npc.php:353,
            // getHealthByGameVersion()) without eager-loading it first - the mapping-import pipeline is designed
            // to run from the scheduler/CLI in production, where Model::preventLazyLoading()'s violation handler
            // only logs (see [[project_autoeager_no_lazyviolation]]); it THROWS in dev/test. This is a
            // pre-existing gap in the import pipeline, unrelated to #3757, not something to paper over with a
            // production code change here - so switch off exactly the guard that gets in the way, for the Act
            // step only.
            //
            // This used to flip the whole application environment to 'production' instead (the way the
            // mapping:save runbook does via `APP_ENV=production`). That also made runningUnitTests() answer
            // false, so StructuredLogging::resolveChannel() selected 'stderr' and the import dumped ~1000
            // ANSI-coloured log lines straight into the CI test output - see #3782.
            $preventsLazyLoading = Model::preventsLazyLoading();
            Model::preventLazyLoading(false);

            try {
                $newMappingVersion = $mappingImportService->importMappingVersionFromMDT(
                    $mappingService,
                    $dungeon,
                    $targetGameVersion,
                    // Not force-imported: this is the path every real scheduled mapping bump takes, and it's the
                    // one that used to dereference a null predecessor's ->mdt_mapping_hash and fatal.
                    false,
                );
            } finally {
                Model::preventLazyLoading($preventsLazyLoading);
            }

            // Assert
            $this->assertSame(
                $targetGameVersion->id,
                $newMappingVersion->game_version_id,
                'The new mapping version must be tagged with the requested game version, not an ambient/unrelated one.',
            );
            $this->assertSame(
                1,
                $newMappingVersion->version,
                'A dungeon\'s first mapping version for a game version must start at version 1, not continue an unrelated game version\'s counter.',
            );

            $this->assertSame(
                0,
                EnemyForcesCheckpoint::query()->where('mapping_version_id', $newMappingVersion->id)->count(),
                'A genuinely first-ever import for a game version has no predecessor to clone checkpoints from.',
            );

            $this->assertSame(
                $retailCheckpointCount,
                $retailMappingVersion->enemyForcesCheckpoints()->count(),
                'Importing a different game version must not mutate the unrelated (retail) game version\'s mapping version.',
            );

            // facade_enabled/FloorUnion/timer_max_seconds inheritance on the null-predecessor path is covered
            // directly (and without any Lua) in MappingServiceCreateNewMappingVersionFromMDTMappingTest.
        } finally {
            // Clean up by (dungeon, target game version), not just $newMappingVersion: the precondition above
            // guarantees the dungeon had ZERO mapping versions for $targetGameVersion before this test ran, so
            // ANY row found for that pair afterwards was created by this test - including the case where
            // importMappingVersionFromMDT() inserts the mapping version row and THEN throws later in its own
            // pipeline (as it did here on the npcHealths lazy-load violation before the env flip was added),
            // which leaves the local $newMappingVersion variable unassigned even though the DB row exists.
            MappingVersion::query()
                ->where('dungeon_id', $dungeon->id)
                ->where('game_version_id', $targetGameVersion->id)
                ->get()
                ->each(static fn(MappingVersion $mappingVersion) => $mappingVersion->delete());

            NpcHealth::query()
                ->where('game_version_id', $targetGameVersion->id)
                ->whereIn('npc_id', $mdtNpcIds)
                ->whereNotIn('id', $preExistingNpcHealthIds)
                ->delete();
        }
    }

    /**
     * A dungeon that already has (a) a retail mapping version with a checkpoint that has members - so the pre-fix
     * bug would have something wrong to leak - and (b) zero mapping versions for $gameVersion, so importing it
     * is a genuine "first ever" case. Also requires facade_enabled, matching
     * MappingServiceCreateNewMappingVersionFromMDTMappingTest's selection criteria for consistency, even though
     * this class no longer asserts on the facade geometry itself.
     */
    private function getMdtDungeonWithRetailCheckpointAndNoMappingForGameVersion(
        GameVersion $gameVersion,
        GameVersion $retailGameVersion,
    ): Dungeon {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->with(['mappingVersions', 'floors'])
            ->get()
            ->first(static function (Dungeon $dungeon) use ($gameVersion, $retailGameVersion): bool {
                if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                    return false;
                }

                if ($dungeon->mappingVersions->where('game_version_id', $gameVersion->id)->isNotEmpty()) {
                    return false;
                }

                $retailMappingVersion = $dungeon->mappingVersions
                    ->where('game_version_id', $retailGameVersion->id)
                    ->sortByDesc('version')
                    ->first();

                return $retailMappingVersion !== null
                    && $retailMappingVersion->facade_enabled
                    && $retailMappingVersion->enemyForcesCheckpoints()->has('enemies')->exists();
            });

        if ($dungeon === null) {
            $this->fail('No MDT-importable, facade-enabled dungeon found with a retail checkpoint (with members) and no mapping version for the target game version.');
        }

        return $dungeon;
    }
}
